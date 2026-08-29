<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * MockCall Configuration
 * Ollama AI settings for Mock Call Assessment
 */
class MockCall extends BaseConfig
{
    /** Ollama API base URL */
    public string $ollamaUrl = 'http://59.144.28.139:11434/api';

    /** Ollama model to use */
    public string $model = 'llama3.2:3b';

    /** Request timeout in seconds */
    public int $timeout = 60;

    /** Max tokens per response */
    public int $maxTokens = 500;

    /** Temperature (0.0 - 1.0) — higher = more creative/unpredictable customer */
    public float $temperature = 0.7;
}
