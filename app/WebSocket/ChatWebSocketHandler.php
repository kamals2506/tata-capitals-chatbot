<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\LiveChatSessionModel;
use App\Models\LiveChatMessageModel;
use App\Models\AgentModel;

/**
 * ChatWebSocketHandler — Real-time WebSocket handler for human-agent live chat.
 *
 * Uses error_log() instead of CI4's log_message() to avoid CI4 Logger
 * bootstrap dependencies in the standalone Ratchet process.
 */
class ChatWebSocketHandler implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;
    protected array $chatRooms       = [];
    protected array $agentConnections = [];
    protected array $connectionMeta  = [];

    protected LiveChatSessionModel $sessionModel;
    protected LiveChatMessageModel $messageModel;
    protected AgentModel $agentModel;

    public function __construct(
        LiveChatSessionModel $sessionModel = null,
        LiveChatMessageModel $messageModel = null,
        AgentModel $agentModel = null
    ) {
        $this->clients      = new \SplObjectStorage();
        $this->sessionModel = $sessionModel ?? new LiveChatSessionModel();
        $this->messageModel = $messageModel ?? new LiveChatMessageModel();
        $this->agentModel   = $agentModel   ?? new AgentModel();
    }

    // =========================================================================
    // Ratchet Lifecycle
    // =========================================================================

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $this->connectionMeta[$conn->resourceId] = [
            'role'          => null,
            'chat_id'       => null,
            'agent_id'      => null,
            'authenticated' => false,
            'connected_at'  => time(),
        ];
        $this->scheduleAuthTimeout($conn);
        error_log('[WS] New connection #' . $conn->resourceId);
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $meta = $this->connectionMeta[$conn->resourceId] ?? null;

        if ($meta && $meta['authenticated'] && $meta['role'] === 'agent' && $meta['agent_id']) {
            try {
                $this->agentModel->setOnline((int) $meta['agent_id'], 0);
            } catch (\Throwable $e) {
                error_log('[WS] Failed to mark agent offline: ' . $e->getMessage());
            }
        }

        foreach ($this->chatRooms as $chatId => $storage) {
            if ($storage->contains($conn)) {
                $storage->detach($conn);
            }
            if ($storage->count() === 0) {
                unset($this->chatRooms[$chatId]);
            }
        }

        if ($meta && $meta['agent_id'] !== null) {
            $agentId = (int) $meta['agent_id'];
            if (isset($this->agentConnections[$agentId]) && $this->agentConnections[$agentId] === $conn) {
                unset($this->agentConnections[$agentId]);
            }
        }

        $this->clients->detach($conn);
        unset($this->connectionMeta[$conn->resourceId]);
        error_log('[WS] Connection #' . $conn->resourceId . ' closed');
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);

        if (!is_array($data) || !isset($data['type'])) {
            $from->send(json_encode(['type' => 'error', 'code' => 4000, 'message' => 'Invalid message format.']));
            return;
        }

        $type = $data['type'];
        $meta = $this->connectionMeta[$from->resourceId] ?? null;

        if (!($meta['authenticated'] ?? false) && $type !== 'subscribe') {
            return; // silently ignore unauthenticated non-subscribe messages
        }

        switch ($type) {
            case 'subscribe':    $this->handleSubscribe($from, $data); break;
            case 'message':      $this->handleMessage($from, $data);   break;
            case 'typing_start':
            case 'typing_stop':  $this->handleTyping($from, $data);    break;
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        error_log(sprintf('[WS] Error on #%d: %s in %s:%d', $conn->resourceId, $e->getMessage(), $e->getFile(), $e->getLine()));
        $conn->close();
    }

    // =========================================================================
    // Token Validation
    // =========================================================================

    public function validateToken(string $token)
    {
        $dotPos = strpos($token, '.');
        if ($dotPos === false) return false;

        $payload = substr($token, 0, $dotPos);
        $sig     = substr($token, $dotPos + 1);

        if ($payload === '' || $sig === '') return false;

        $secret   = getenv('APP_SECRET') ?: 'allcargo_ws_secret';
        $expected = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $sig)) return false;

        $decoded = json_decode(base64_decode($payload, true), true);
        if (!is_array($decoded)) return false;
        if (!isset($decoded['exp']) || $decoded['exp'] < time()) return false;

        return $decoded;
    }

    // =========================================================================
    // Handlers
    // =========================================================================

    protected function handleSubscribe(ConnectionInterface $conn, array $data): void
    {
        $tokenData = $this->validateToken((string) ($data['token'] ?? ''));

        if ($tokenData === false) {
            $conn->send(json_encode(['type' => 'error', 'code' => 4001, 'message' => 'Unauthorized']));
            $conn->close();
            return;
        }

        $role = $tokenData['role'] ?? ($data['role'] ?? null);

        if ($role === 'customer') {
            $chatId = (int) ($tokenData['chat_id'] ?? 0);
            if ($chatId <= 0) { $conn->send(json_encode(['type'=>'error','code'=>4001,'message'=>'Unauthorized'])); $conn->close(); return; }

            if (!isset($this->chatRooms[$chatId])) $this->chatRooms[$chatId] = new \SplObjectStorage();
            $this->chatRooms[$chatId]->attach($conn);

            $this->connectionMeta[$conn->resourceId] = array_merge(
                $this->connectionMeta[$conn->resourceId] ?? [],
                ['role' => 'customer', 'chat_id' => $chatId, 'agent_id' => null, 'authenticated' => true]
            );
            error_log("[WS] Customer subscribed to chat #{$chatId}");

        } elseif ($role === 'agent') {
            $agentId = (int) ($tokenData['agent_id'] ?? 0);
            if ($agentId <= 0) { $conn->send(json_encode(['type'=>'error','code'=>4001,'message'=>'Unauthorized'])); $conn->close(); return; }

            $this->agentConnections[$agentId] = $conn;
            $this->connectionMeta[$conn->resourceId] = array_merge(
                $this->connectionMeta[$conn->resourceId] ?? [],
                ['role' => 'agent', 'chat_id' => null, 'agent_id' => $agentId, 'authenticated' => true]
            );
            error_log("[WS] Agent #{$agentId} subscribed");

        } else {
            $conn->send(json_encode(['type' => 'error', 'code' => 4001, 'message' => 'Unauthorized']));
            $conn->close();
            return;
        }

        $this->cancelAuthTimeout($conn);
    }

    /**
     * Reconnect the database if the connection has gone away.
     * MySQL drops idle connections after wait_timeout (default 8 hours,
     * but shared hosts often set it to 30-300 seconds).
     */
    protected function ensureDbConnection(): void
    {
        try {
            $db = \CodeIgniter\Database\Config::connect();
            // ping() returns false if connection is dead
            if (! $db->connID || ! @mysqli_ping($db->connID)) {
                $db->reconnect();
            }
        } catch (\Throwable $e) {
            try {
                \CodeIgniter\Database\Config::connect()->reconnect();
            } catch (\Throwable $e2) {
                error_log('[WS] DB reconnect failed: ' . $e2->getMessage());
            }
        }
    }

    protected function handleMessage(ConnectionInterface $conn, array $data): void
    {
        $this->ensureDbConnection();
        $meta    = $this->connectionMeta[$conn->resourceId] ?? null;
        $chatId  = (int) ($data['chat_id'] ?? 0);
        $message = trim($data['message'] ?? '');

        if (!$meta || !$meta['authenticated'] || $chatId <= 0 || $message === '') return;

        if ($meta['role'] === 'customer') {
            if ((int) $meta['chat_id'] !== $chatId) return;
            $sender = 'customer';
        } elseif ($meta['role'] === 'agent') {
            $session = $this->sessionModel->find($chatId);
            if (!$session || (int) $session['agent_id'] !== (int) $meta['agent_id']) return;
            $sender = 'agent';
        } else {
            return;
        }

        try {
            $saved = $this->messageModel->addMessage($chatId, $sender, $message);
        } catch (\Throwable $e) {
            error_log('[WS] Failed to persist message: ' . $e->getMessage());
            $conn->send(json_encode(['type' => 'error', 'code' => 5000, 'message' => 'Failed to save message.']));
            return;
        }

        $this->broadcastToRoom($chatId, [
            'type'       => 'message',
            'chat_id'    => $chatId,
            'id'         => $saved['id'],
            'sender'     => $saved['sender'],
            'message'    => $saved['message'],
            'created_at' => $saved['created_at'],
        ]);
    }

    protected function handleTyping(ConnectionInterface $conn, array $data): void
    {
        $this->ensureDbConnection();
        $meta   = $this->connectionMeta[$conn->resourceId] ?? null;
        $chatId = (int) ($data['chat_id'] ?? ($meta['chat_id'] ?? 0));
        $type   = $data['type'] ?? '';

        if (!$meta || !$meta['authenticated'] || $chatId <= 0) return;

        $payload = json_encode(['type' => $type, 'chat_id' => $chatId, 'sender' => $data['sender'] ?? $meta['role']]);

        if ($meta['role'] === 'customer') {
            $session = $this->sessionModel->find($chatId);
            if ($session && !empty($session['agent_id']) && isset($this->agentConnections[(int)$session['agent_id']])) {
                $this->agentConnections[(int)$session['agent_id']]->send($payload);
            }
        } elseif ($meta['role'] === 'agent') {
            if (isset($this->chatRooms[$chatId])) {
                foreach ($this->chatRooms[$chatId] as $roomConn) {
                    if ($roomConn === $conn) continue;
                    $roomMeta = $this->connectionMeta[$roomConn->resourceId] ?? null;
                    if ($roomMeta && $roomMeta['role'] === 'customer') $roomConn->send($payload);
                }
            }
        }
    }

    // =========================================================================
    // Broadcast Helpers
    // =========================================================================

    public function broadcastToRoom(int $chatId, array $payload): void
    {
        if (!isset($this->chatRooms[$chatId])) return;
        $encoded = json_encode($payload);
        foreach ($this->chatRooms[$chatId] as $conn) $conn->send($encoded);
    }

    public function broadcastToAgents(array $payload): void
    {
        $encoded = json_encode($payload);
        foreach ($this->agentConnections as $conn) $conn->send($encoded);
    }

    // =========================================================================
    // Auth Timeout (best-effort)
    // =========================================================================

    protected function scheduleAuthTimeout(ConnectionInterface $conn): void
    {
        try {
            $wrapped = $conn->wrappedConn ?? null;
            $loop    = $wrapped ? ($wrapped->loop ?? null) : null;
            if ($loop && method_exists($loop, 'addTimer')) {
                $rid   = $conn->resourceId;
                $timer = $loop->addTimer(5, function () use ($conn, $rid) {
                    $meta = $this->connectionMeta[$rid] ?? null;
                    if ($meta && !$meta['authenticated']) {
                        $conn->send(json_encode(['type'=>'error','code'=>4001,'message'=>'Authentication timeout.']));
                        $conn->close();
                    }
                });
                $this->connectionMeta[$conn->resourceId]['_timer'] = $timer;
            }
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    protected function cancelAuthTimeout(ConnectionInterface $conn): void
    {
        $timer = $this->connectionMeta[$conn->resourceId]['_timer'] ?? null;
        if ($timer === null) return;
        try {
            $wrapped = $conn->wrappedConn ?? null;
            $loop    = $wrapped ? ($wrapped->loop ?? null) : null;
            if ($loop && method_exists($loop, 'cancelTimer')) $loop->cancelTimer($timer);
        } catch (\Throwable $e) {}
        unset($this->connectionMeta[$conn->resourceId]['_timer']);
    }
}
