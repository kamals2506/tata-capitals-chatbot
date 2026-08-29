<?php

namespace App\Models;

use CodeIgniter\Model;

class FaqModel extends Model
{
    protected $table         = 'faq';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['question', 'answer', 'category', 'is_active'];

    /**
     * Get all active FAQ entries.
     */
    public function getActive(): array
    {
        return $this->where('is_active', 1)->findAll();
    }

    /**
     * Get active FAQ entries filtered by category.
     */
    public function getByCategory(string $category): array
    {
        return $this->where('category', $category)
                    ->where('is_active', 1)
                    ->findAll();
    }
}
