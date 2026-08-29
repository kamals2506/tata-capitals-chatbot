<?php

namespace App\Libraries;

use App\Models\PlansModel;
use App\Models\ErrorCodeModel;
use App\Models\FaqModel;

class KnowledgeBaseService
{
    private PlansModel $plansModel;
    private ErrorCodeModel $errorCodeModel;
    private FaqModel $faqModel;

    public function __construct()
    {
        $this->plansModel     = new PlansModel();
        $this->errorCodeModel = new ErrorCodeModel();
        $this->faqModel       = new FaqModel();
    }

    /**
     * Get active plans, optionally filtered by type (monthly|yearly|sports).
     */
    public function getPlans(?string $type = null): array
    {
        return $type ? $this->plansModel->getByType($type) : $this->plansModel->getActive();
    }

    /**
     * Look up an error code.
     */
    public function getErrorCode(string $code): ?array
    {
        return $this->errorCodeModel->findByCode($code);
    }

    /**
     * Get FAQ entries by category.
     */
    public function getFaqByCategory(string $category): array
    {
        return $this->faqModel->getByCategory($category);
    }

    /**
     * Build a KB context string for injection into the Ollama prompt.
     * Only used for intents: recharge, plan-query, error-lookup.
     */
    public function buildContext(string $intent, string $query): string
    {
        $snippets = [];

        switch ($intent) {
            case 'plan-query':
                $type  = $this->detectPlanType($query);
                $plans = $this->getPlans($type);
                foreach (array_slice($plans, 0, 5) as $plan) {
                    $snippets[] = "Pack: {$plan['plan_name']} | Type: {$plan['plan_type']} | Price: ₹{$plan['price_inr']}/- | Channels: {$plan['channel_count']} | Validity: {$plan['validity_days']} days\n{$plan['description']}";
                }
                break;

            case 'recharge':
                $plans = $this->getPlans(null);
                foreach (array_slice($plans, 0, 5) as $plan) {
                    $snippets[] = "Recharge option: {$plan['plan_name']} - ₹{$plan['price_inr']}/- ({$plan['validity_days']} days, {$plan['channel_count']} channels)";
                }
                $snippets[] = "Recharge link: https://www.tatacapital.com/ | Helpline: 1800-208-6633";
                break;

            case 'error-lookup':
                // Try to extract error code from query
                if (preg_match('/\b(e\d{2,3}|error\s*\d+)\b/i', $query, $m)) {
                    $code  = preg_replace('/\D/', '', $m[0]);
                    $code  = 'E' . ltrim($code, '0') ?: 'E0';
                    $error = $this->getErrorCode($m[0]);
                    if ($error) {
                        $snippets[] = "Error Code: {$error['error_code']} - {$error['error_name']}\nCause: {$error['cause']}\nResolution:\n{$error['resolution_steps']}";
                    }
                }
                break;
        }

        return implode("\n---\n", $snippets);
    }

    /**
     * Detect plan type hint from a query string.
     */
    private function detectPlanType(string $query): ?string
    {
        $q = mb_strtolower($query, 'UTF-8');
        if (str_contains($q, 'sport') || str_contains($q, 'cricket') || str_contains($q, 'football')) {
            return 'sports';
        }
        if (str_contains($q, 'year') || str_contains($q, 'annual') || str_contains($q, 'साल')) {
            return 'yearly';
        }
        if (str_contains($q, 'month') || str_contains($q, 'महीना') || str_contains($q, 'monthly')) {
            return 'monthly';
        }
        return null;
    }
}
