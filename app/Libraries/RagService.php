<?php

namespace App\Libraries;

class RagService
{
    private $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Search local knowledge base.
     *
     * This is intentionally MySQL based so no changes
     * are required on the remote Ollama server.
     */
    public function search(
        string $query,
        ?string $category = null,
        int $limit = 5
    ): array {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $builder = $this->db
            ->table('rag_documents')
            ->select('id, title, category, keywords, content');

        $builder->where('status', 'active');

        if ($category !== null && $category !== '') {
            $builder->groupStart()
                ->where('category', $category)
                ->orLike('category', $category)
                ->groupEnd();
        }

        /*
         * FULLTEXT search first.
         */
        $booleanQuery = $this->buildBooleanQuery($query);

        if ($booleanQuery !== '') {
            $builder->select(
                "MATCH(title, keywords, content) AGAINST("
                . $this->db->escape($booleanQuery)
                . " IN BOOLEAN MODE) AS relevance",
                false
            );

            $builder->where(
                "MATCH(title, keywords, content) AGAINST("
                . $this->db->escape($booleanQuery)
                . " IN BOOLEAN MODE) > 0",
                null,
                false
            );

            $builder->orderBy('relevance', 'DESC');
            $builder->limit($limit);

            $results = $builder->get()->getResultArray();

            if (!empty($results)) {
                return $results;
            }
        }

        /*
         * Fallback LIKE search.
         */
        $words = $this->extractKeywords($query);

        if (empty($words)) {
            return [];
        }

        $builder = $this->db
            ->table('rag_documents')
            ->select('id, title, category, keywords, content')
            ->where('status', 'active');

        if ($category !== null && $category !== '') {
            $builder->groupStart()
                ->where('category', $category)
                ->orLike('category', $category)
                ->groupEnd();
        }

        $builder->groupStart();

        foreach ($words as $word) {
            $builder->orLike('title', $word)
                ->orLike('keywords', $word)
                ->orLike('content', $word);
        }

        $builder->groupEnd();

        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }

    /**
     * Convert search results into Ollama context.
     */
    public function buildContext(
        string $query,
        ?string $category = null,
        int $limit = 5
    ): string {
        $results = $this->search(
            $query,
            $category,
            $limit
        );

        if (empty($results)) {
            return '';
        }

        $context = [];

        foreach ($results as $index => $row) {
            $context[] =
                "SOURCE " . ($index + 1) . "\n" .
                "TITLE: " . trim((string) $row['title']) . "\n" .
                "CATEGORY: " . trim((string) ($row['category'] ?? '')) . "\n" .
                "CONTENT:\n" .
                trim((string) $row['content']);
        }

        return implode("\n\n----------------------\n\n", $context);
    }

    private function buildBooleanQuery(string $query): string
    {
        $words = $this->extractKeywords($query);

        if (empty($words)) {
            return '';
        }

        return implode(' ', array_map(
            static fn($word) => '+' . $word . '*',
            $words
        ));
    }

    private function extractKeywords(string $query): array
    {
        $query = mb_strtolower($query);

        /*
         * Remove common Hinglish/English filler words.
         */
        $stopWords = [
            'the',
            'is',
            'are',
            'am',
            'a',
            'an',
            'and',
            'or',
            'of',
            'to',
            'for',
            'in',
            'on',
            'with',
            'what',
            'how',
            'why',
            'can',
            'could',
            'please',
            'tell',
            'me',
            'about',
            'mujhe',
            'mujko',
            'hai',
            'hain',
            'ka',
            'ki',
            'ke',
            'kya',
            'batao',
            'bataye',
            'chahiye',
            'karna',
            'hai',
            'mein',
            'main',
            'se',
            'ko',
            'par',
            'ye',
            'woh',
            'ek'
        ];

        $query = preg_replace(
            '/[^\p{L}\p{N}\s]/u',
            ' ',
            $query
        );

        $parts = preg_split(
            '/\s+/u',
            trim($query)
        );

        $result = [];

        foreach ($parts as $word) {
            $word = trim($word);

            if ($word === '') {
                continue;
            }

            if (mb_strlen($word) < 2) {
                continue;
            }

            if (in_array($word, $stopWords, true)) {
                continue;
            }

            $result[$word] = $word;
        }

        return array_values($result);
    }
}