<?php

namespace App\Models;

use CodeIgniter\Model;

class ErrorCodeModel extends Model
{
    protected $table         = 'error_codes';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'error_code', 'error_name', 'category', 'cause', 'resolution_steps',
    ];

    /**
     * Find an error code record (case-insensitive).
     */
    public function findByCode(string $code): ?array
    {
        return $this->where('UPPER(error_code)', strtoupper(trim($code)))->first();
    }

    /**
     * Get all error codes in a category.
     */
    public function getByCategory(string $category): array
    {
        return $this->where('category', $category)->findAll();
    }
}
