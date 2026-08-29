<?php

namespace App\Models;

use CodeIgniter\Model;

class KnowledgeBaseModel extends Model
{
    protected $table            = 'knowledge_base';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'intent', 'category', 'title', 'content', 'keywords', 'language', 'is_active',
    ];

    /**
     * Get all active rows matching an intent (and language), ordered by id.
     */
    public function getByIntent(string $intent, string $language = 'en'): array
    {
        return $this->where('intent', $intent)
            ->where('language', $language)
            ->where('is_active', 1)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * FULLTEXT keyword search fallback across title/content/keywords.
     * Used when the detected intent isn't mapped or returns nothing.
     */
    public function searchByText(string $text, string $language = 'en', int $limit = 3): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        $builder = $this->db->table($this->table)
            ->select('*, MATCH(title, content, keywords) AGAINST (:term: IN NATURAL LANGUAGE MODE) as relevance')
            ->where('is_active', 1)
            ->where('language', $language)
            ->having('relevance >', 0)
            ->orderBy('relevance', 'DESC')
            ->limit($limit)
            ->binds(['term' => $text]);

        return $builder->get()->getResultArray();
    }

    /**
     * Always-included contact/support rows (category = 'contact'),
     * used as a safety net so the LLM never has to guess phone/email/website.
     */
    public function getContactInfo(string $language = 'en'): array
    {
        return $this->where('category', 'contact')
            ->where('language', $language)
            ->where('is_active', 1)
            ->findAll();
    }
}
