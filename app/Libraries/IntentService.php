<?php

namespace App\Libraries;

class IntentService
{
    /**
     * Classify the intent of a user message.
     *
     * Returns one of:
     *   recharge | plan-query | complaint | error-lookup |
     *   remote-troubleshoot | no-signal | technical | escalation | general
     */
    public function classify(string $userMessage, array $history = []): string
    {
        $msg = mb_strtolower($userMessage, 'UTF-8');

        // Escalation — check first (highest priority)
        if ($this->matchesAny($msg, [
            'agent', 'human', 'manav', 'मानव', 'इंसान', 'call me', 'callback',
            'baat karo', 'बात करो', 'helpline', 'transfer', 'escalate',
            'operator', 'representative', 'support agent',
        ])) {
            return 'escalation';
        }

        // Error code lookup — e.g. "E04", "Error 16", "error code"
        if (preg_match('/\b(error\s*code|e\d{2,3}|error\s+\d+)\b/i', $userMessage) ||
            $this->matchesAny($msg, ['error code', 'एरर', 'error'])) {
            return 'error-lookup';
        }

        // Remote troubleshooting
        if ($this->matchesAny($msg, [
            'remote', 'रिमोट', 'remote not working', 'remote kaam nahi',
            'remote band', 'remote control', 'tv remote',
        ])) {
            return 'remote-troubleshoot';
        }

        // No signal
        if ($this->matchesAny($msg, [
            'no signal', 'signal nahi', 'सिग्नल नहीं', 'blank screen',
            'black screen', 'koi signal nahi', 'signal loss', 'signal lost',
            'dish', 'antenna', 'no picture', 'screen black',
        ])) {
            return 'no-signal';
        }

        // Complaint
        if ($this->matchesAny($msg, [
            'complaint', 'शिकायत', 'complain', 'problem report', 'issue report',
            'register complaint', 'lodge complaint', 'darz karo', 'shikayat',
        ])) {
            return 'complaint';
        }

        // Recharge
        if ($this->matchesAny($msg, [
            'recharge', 'रिचार्ज', 'top up', 'topup', 'balance', 'renew',
            'expiry', 'subscription renew', 'pack renew', 'due date',
        ])) {
            return 'recharge';
        }

        // Plan query
        if ($this->matchesAny($msg, [
            'plan', 'pack', 'पैक', 'channel list', 'monthly', 'yearly',
            'annual', 'sports pack', 'channel pack', 'subscription',
            'how many channels', 'kitne channel', 'price', 'cost',
        ])) {
            return 'plan-query';
        }

        // Technical support (catch-all for technical issues)
        if ($this->matchesAny($msg, [
            'not working', 'kaam nahi', 'काम नहीं', 'problem', 'issue',
            'technical', 'freeze', 'hang', 'slow', 'audio', 'video',
            'picture quality', 'buffering', 'channel', 'setup box', 'stb',
            'set top box', 'repair', 'technician',
        ])) {
            return 'technical';
        }

        return 'general';
    }

    /**
     * Check if a message contains any of the given keywords.
     */
    private function matchesAny(string $msg, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($msg, mb_strtolower($kw, 'UTF-8'))) {
                return true;
            }
        }
        return false;
    }
}
