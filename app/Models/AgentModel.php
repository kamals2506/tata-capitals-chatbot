<?php

namespace App\Models;

use CodeIgniter\Model;

class AgentModel extends Model
{

    protected $table='live_chat_agents';

    protected $primaryKey='id';

    protected $returnType='array';

    protected $allowedFields=[

        'agent_name',

        'email',

        'password',

        'is_online',
	'Active'

    ];

    /**
     * Set an agent's online status.
     *
     * @param int $agentId  The agent's primary key.
     * @param int $status   1 = online, 0 = offline.
     * @return bool         True on success, false on failure.
     */
    public function setOnline(int $agentId, int $status): bool
    {
        return $this->db
            ->table($this->table)
            ->where('id', $agentId)
            ->update(['is_online' => $status]);
    }

    /**
     * Return the number of agents currently marked online.
     *
     * @return int
     */
    public function getOnlineCount(): int
    {
        return (int) $this->db
            ->table($this->table)
            ->where('is_online', 1)
            ->countAllResults();
    }

    /**
     * Verify agent credentials against the stored bcrypt hash.
     *
     * @param string $email
     * @param string $password  Plain-text password to verify.
     * @return array|null       Full agent row on success, null on failure.
     */
    public function verifyCredentials(string $email, string $password): ?array
{
    $agent = $this->where([
        'email'  => $email,
        'Active' => 1
    ])->first();

    if ($agent === null) {
        return null;
    }

    if (! password_verify($password, $agent['password'])) {
        return null;
    }

    return $agent;
}

}