<?php

namespace App\Libraries;

class OllamaService
{
    private string $baseUrl;
    private string $model;
    private int $timeout;

    public const FALLBACK_AI    = 'Sorry, the service is temporarily unavailable. Please call our helpline at 1800-208-6633 for assistance.';
    public const FALLBACK_AI_HI = 'क्षमा करें, अभी सेवा उपलब्ध नहीं है। कृपया हेल्पलाइन 1800-208-6633 पर संपर्क करें।';

    private const SYSTEM_PROMPT_EN = <<<'PROMPT'
You are a friendly and helpful customer support assistant for Tata Capital, a leading financial services company in India.

Always respond in clear, simple English. Keep replies concise — 3 to 4 sentences maximum.

Only answer questions related to Tata Capital services, loans, EMIs, payments, accounts, financial products, and customer support.

Your top priority is to understand and resolve the customer's issue. If you cannot resolve the issue, advise the customer to contact Tata Capital's official helpline or connect with a human customer support agent.

Do not provide information about Tata Play, DTH, satellite TV, recharge, TV plans, or unrelated services.

PROMPT;

    private const SYSTEM_PROMPT_HI = <<<'PROMPT'
तुम Tata Capital के ग्राहक सेवा सहायक हो। केवल Tata Capital से संबंधित सवालों के जवाब दो।
सरल हिंदी (देवनागरी या Hinglish) में जवाब दो। अधिकतम 3-4 वाक्य में संक्षिप्त जवाब दो।
Tata Capital वित्तीय सेवाएँ और लोन से जुड़ी सेवाएँ प्रदान करता है।
हेल्पलाइन: 1800-208-6633 | वेबसाइट: https://www.tatacapital.com/
ग्राहक की समस्या को समझकर सही और स्पष्ट जानकारी देना तुम्हारी प्राथमिकता है।
अगर तुम समस्या हल नहीं कर सकते, तो हेल्पलाइन या मानव एजेंट से संपर्क करने की सलाह दो।
PROMPT;

    public function __construct()
    {
        $this->baseUrl = 'http://59.144.28.139:11434';
        $this->model   = 'gemma3:4b';
        $this->timeout = 90;
    }

    /**
     * Send a chat request to Ollama and return the assistant response text.
     *
     * @param array  $history     Array of ['role' => 'user|assistant', 'content' => '...'] (max 20)
     * @param string $userMessage The current user message
     * @param array  $kbContext   Optional KB snippets to inject (max 5 entries)
     * @param string $language    'en' (default) or 'hi'
     */
    public function chat(array $history, string $userMessage, array $kbContext = [], string $language = 'en'): string
    {
        $systemPrompt = ($language === 'hi') ? self::SYSTEM_PROMPT_HI : self::SYSTEM_PROMPT_EN;
        $fallback     = ($language === 'hi') ? self::FALLBACK_AI_HI   : self::FALLBACK_AI;

        // Build messages array: system → history → user
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($history as $msg) {
            $messages[] = [
                'role'    => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        // Append KB context block to user message
        $finalUserMessage = $userMessage;
        if (!empty($kbContext)) {
            $contextBlock = "\n\n[Reference Information]\n" . implode("\n---\n", array_slice($kbContext, 0, 5));
            $finalUserMessage .= $contextBlock;
        }

        $messages[] = ['role' => 'user', 'content' => $finalUserMessage];

        $payload = json_encode([
            'model'    => $this->model,
            'messages' => $messages,
            'stream'   => false,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($this->baseUrl . '/api/chat');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $response === false) {
            log_message('error', '[OllamaService] cURL error: ' . $curlError);
            return $fallback;
        }

        return $this->parseResponse($response, $fallback);
    }

    /**
     * Parse the Ollama /api/chat JSON response and extract message.content.
     */
    public function parseResponse(string $raw, string $fallback = ''): string
    {
        if ($fallback === '') $fallback = self::FALLBACK_AI;
        $data = json_decode($raw, true);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !isset($data['message']['content']) ||
            trim($data['message']['content']) === ''
        ) {
            log_message('error', '[OllamaService] Malformed or empty response: ' . substr($raw, 0, 300));
            return $fallback;
        }

        return trim($data['message']['content']);
    }
}
