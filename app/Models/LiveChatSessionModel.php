<?php

namespace App\Models;

use CodeIgniter\Model;

class LiveChatSessionModel extends Model
{
    protected $table = 'live_chat_sessions';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'session_id',
        'customer_name',
        'customer_mobile',
        'status',
        'dispostion',
        'remarks',
        'agent_id',
    ];

    /**
     * Create a new waiting chat session.
     */
    public function createChat(string $session, string $name, string $mobile): ?array
    {
        $this->insert([
            'session_id'       => $session,
            'customer_name'    => $name,
            'customer_mobile'  => $mobile,
            'status'           => 'waiting',
        ]);

        return $this->find($this->getInsertID());
    }

    /**
     * Return the most recent open (waiting or active) session for the given session_id,
     * or null when none exists.
     *
     * Requirements: 1.6, 2.1
     */
    public function findOpenBySessionId(string $sessionId): ?array
    {
        return $this
            ->where('session_id', $sessionId)
            ->whereIn('status', ['waiting', 'active'])
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Return all sessions currently in the waiting queue, oldest first.
     *
     * Requirements: 2.1
     */
    public function getWaitingChats(): array
    {
        return $this
            ->where('status', 'waiting')
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Return all active sessions across all agents, most recently updated first.
     *
     * Requirements: 2.2
     */
    public function getActiveChats(): array
    {
        return $this
            ->where('status', 'active')
            ->orderBy('updated_at', 'DESC')
            ->findAll();
    }

    /**
     * Return all active sessions assigned to a specific agent.
     *
     * Requirements: 2.2
     */
    public function getAgentChats(int $agentId): array
    {
        return $this
            ->where('status', 'active')
            ->where('agent_id', $agentId)
            ->findAll();
    }

    /**
     * Atomically assign an agent to a waiting chat.
     *
     * Updates status to 'active' and sets agent_id only when the current status
     * is 'waiting'. Returns false without modifying the row when the session is
     * already active or closed (Requirement 2.7 — "Chat already claimed").
     *
     * Requirements: 2.2, 2.7
     */
    public function assignAgent(int $chatId, int $agentId): bool
    {
        $affected = $this->db
            ->table($this->table)
            ->where('id', $chatId)
            ->where('status', 'waiting')
            ->update([
                'status'     => 'active',
                'agent_id'   => $agentId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $affected && $this->db->affectedRows() > 0;
    }

    /**
     * Mark a session as closed.
     *
     * Requirements: 2.3
     */
    public function closeChat(int $chatId): bool
    {
        return (bool) $this->update($chatId, [
            'status' => 'closed',
        ]);
    }
}