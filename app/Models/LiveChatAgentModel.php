<?php
namespace App\Models;
use CodeIgniter\Model;

class LiveChatAgentModel extends Model
{
    protected $table         = 'live_chat_agents';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'agent_name',
        'email',
        'password',
        'is_online',
        'Active'
    ];

    protected $validationRules = [
        'agent_name' => 'required|min_length[3]|max_length[100]',
        'email'      => 'required|valid_email',
    ];

    protected $validationMessages = [
        'email' => [
            'required'    => 'Email is required.',
            'valid_email' => 'Please enter a valid email address.',
        ],
    ];

    public function setOnline(int $agentId, bool $online): bool
    {
        return $this->update($agentId, ['is_online' => $online ? 1 : 0]);
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $builder = $this->where('email', $email);
        if ($ignoreId) {
            $builder->where('id !=', $ignoreId);
        }
        return $builder->countAllResults() > 0;
    }
}