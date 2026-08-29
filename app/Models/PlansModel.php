<?php

namespace App\Models;

use CodeIgniter\Model;

class PlansModel extends Model
{
    protected $table         = 'plans';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'plan_type', 'plan_name', 'price_inr',
        'channel_count', 'validity_days', 'description', 'is_active',
    ];

    /**
     * Get all active plans, optionally filtered by type.
     */
    public function getActive(): array
    {
        return $this->where('is_active', 1)->findAll();
    }

    /**
     * Get active plans by type: monthly | yearly | sports
     */
    public function getByType(string $type): array
    {
        return $this->where('plan_type', $type)
                    ->where('is_active', 1)
                    ->findAll();
    }
}
