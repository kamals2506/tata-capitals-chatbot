<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatSessionModel extends Model
{
    protected $table         = 'chat_sessions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['session_uuid', 'status', 'voice_enabled', 'language'];

    /**
     * Create a new chat session and return the full row.
     */
    public function createSession(string $uuid, string $language = 'en'): array
    {
        $data = [
            'session_uuid'  => $uuid,
            'status'        => 'active',
            'voice_enabled' => 1,
            'language'      => in_array($language, ['en', 'hi'], true) ? $language : 'en',
        ];
        $id = $this->insert($data, true);

        return $this->find($id);
    }

    /**
     * Find a session by its UUID.
     */
    public function getByUuid(string $uuid): ?array
    {
        return $this->where('session_uuid', $uuid)->first();
    }

    /**
     * Mark a session as expired.
     */
    public function markExpired(int $id): bool
    {
        return $this->update($id, ['status' => 'expired']) !== false;
    }

    /**
     * Mark a session as escalated.
     */
    public function markEscalated(int $id): bool
    {
        return $this->update($id, ['status' => 'escalated']) !== false;
    }

    /**
     * Pure PHP time comparison — no additional DB call.
     * Returns true if the session has been inactive for >= 30 minutes.
     */
    public function isExpired(array $session): bool
    {
        if ($session['status'] !== 'active') {
            return true;
        }
        $updatedAt = strtotime($session['updated_at']);

        return (time() - $updatedAt) >= 1800;
    }

    /**
     * Update voice_enabled flag on a session.
     */
    public function setVoiceEnabled(int $id, bool $enabled): bool
    {
        return $this->update($id, ['voice_enabled' => $enabled ? 1 : 0]) !== false;
    }

    /**
     * Update language on a session.
     */
    public function setLanguage(int $id, string $lang): bool
    {
        $lang = in_array($lang, ['en', 'hi'], true) ? $lang : 'en';
        return $this->update($id, ['language' => $lang]) !== false;
    }
}
