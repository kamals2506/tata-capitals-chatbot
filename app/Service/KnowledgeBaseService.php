<?php

namespace App\Libraries;

use App\Models\KnowledgeBaseModel;

/**
 * Builds intent-relevant context text from the `knowledge_base` MySQL table
 * to ground the Ollama chat response. Falls back to FULLTEXT keyword search
 * if the intent isn't mapped or returns no rows.
 *
 * Usage (already wired in ChatController::sendMessage()):
 *   $kbContextStr = $this->kb->buildContext($detectedIntent, $userText, $language);
 */
class KnowledgeBaseService
{
    private KnowledgeBaseModel $model;

    public function __construct()
    {
        $this->model = new KnowledgeBaseModel();
    }

    public function buildContext(string $intent, string $userText, string $language = 'en'): string
    {
        $rows = [];

        if ($intent !== '') {
            $rows = $this->model->getByIntent($intent, $language);
        }

        // Fallback to FULLTEXT keyword search if intent gave nothing
        if (empty($rows)) {
            $rows = $this->model->searchByText($userText, $language);
        }

        if (empty($rows)) {
            return '';
        }

        $chunks = [];
        foreach ($rows as $row) {
            $chunks[] = "{$row['title']}: {$row['content']}";
        }

        // Always append contact info as a safety net
        $contactRows = $this->model->getContactInfo($language);
        foreach ($contactRows as $row) {
            $chunks[] = "{$row['title']}: {$row['content']}";
        }

        return implode("\n\n", array_unique($chunks));
    }
}
