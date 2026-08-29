<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatMessageModel extends Model
{
    protected $table         = 'chat_messages';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['session_id', 'role', 'content', 'tts_url', 'created_at'];

    /**
     * Append a message to a session and return the new message ID.
     */
    public function appendMessage(int $sessionId, string $role, string $content, ?string $ttsUrl = null): int
    {
        $data = [
            'session_id' => $sessionId,
            'role'       => $role,
            'content'    => $content,
            'tts_url'    => $ttsUrl,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return (int) $this->insert($data, true);
    }

    /**
     * Get the last $limit messages for a session in chronological order.
     * Fetches DESC then reverses so oldest-first is returned to caller.
     */
    public function getHistory(int $sessionId, int $limit = 20): array
    {
        $rows = $this->where('session_id', $sessionId)
                     ->orderBy('created_at', 'DESC')
                     ->limit($limit)
                     ->findAll();

        return array_reverse($rows);
    }

    /**
     * Get all messages for a session ordered chronologically.
     */
    public function getBySessionId(int $sessionId): array
    {
        return $this->where('session_id', $sessionId)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
}
