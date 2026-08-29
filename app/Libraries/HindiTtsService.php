<?php

namespace App\Libraries;

class HindiTtsService
{
    private string $voice   = 'en-IN-NeerjaNeural';   // Indian English voice
    private const VOICE_EN  = 'en-IN-NeerjaNeural';
    private const VOICE_HI  = 'hi-IN-NeerjaNeural';
    private string $ttsDir;
    private string $ttsDirPublic;
    private int    $maxLength = 500;
    private int    $cleanupAge = 3600; // 1 hour in seconds

    // Injectable shell executor for testability
    private $shellExecutor;

    public function __construct(?callable $shellExecutor = null)
    {
        $this->ttsDir       = FCPATH . 'tts' . DIRECTORY_SEPARATOR;
        $this->ttsDirPublic = base_url('tts/');
        $this->shellExecutor = $shellExecutor ?? static function (string $cmd): void {
            shell_exec($cmd);
        };
    }

    /**
     * Clean text for TTS synthesis:
     * - Strips markdown symbols: * _ \ # [ ] ( )
     * - Collapses whitespace
     * - Caps at 500 characters (mb_substr — unicode safe)
     */
    public function cleanText(string $text): string
    {
        // Strip markdown bold/italic/code/headings before TTS
        $text = preg_replace('/\*\*(.*?)\*\*/u', '$1', $text);   // **bold**
        $text = preg_replace('/__(.*?)__/u',      '$1', $text);   // __bold__
        $text = preg_replace('/\*(.*?)\*/u',      '$1', $text);   // *italic*
        $text = preg_replace('/_(.*?)_/u',        '$1', $text);   // _italic_
        $text = preg_replace('/`([^`]+)`/u',      '$1', $text);   // `code`
        $text = preg_replace('/#+\s?/u',          '',   $text);   // ## headings

        // Remove remaining markdown punctuation characters
        $text = preg_replace('/[*_\\\\#\[\]()]/u', '', $text);

        // Collapse multiple whitespace/newlines into a single space
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        // Cap at 500 characters (unicode-safe)
        if (mb_strlen($text, 'UTF-8') > $this->maxLength) {
            $text = mb_substr($text, 0, $this->maxLength, 'UTF-8');
        }

        return $text;
    }

    /**
     * Synthesize text to MP3 using edge-tts CLI.
     * @param string $language 'en' or 'hi'
     * Returns ['url' => '...', 'file' => 'filename.mp3', 'path' => '/abs/path.mp3']
     * or null on failure.
     */
    public function synthesize(string $text, string $language = 'en'): ?array
    {
        $this->voice = ($language === 'hi') ? self::VOICE_HI : self::VOICE_EN;
        // Clean TTS files older than 1 hour on every call
        $this->cleanup();

        if (!$this->isAvailable()) {
            log_message('warning', '[TtsService] edge-tts binary not found.');
            return null;
        }

        $clean    = $this->cleanText($text);
        $filename = 'tts_' . time() . '_' . substr(md5(uniqid('', true)), 0, 8) . '.mp3';
        $filepath = $this->ttsDir . $filename;

        // Build edge-tts command
        $escapedText = escapeshellarg($clean);
        $escapedPath = escapeshellarg($filepath);
        $cmd = "edge-tts --voice {$this->voice} --text {$escapedText} --write-media {$escapedPath} 2>&1";

        ($this->shellExecutor)($cmd);

        // Validate output file
        if (!file_exists($filepath)) {
            log_message('error', '[TtsService] MP3 file not created: ' . $filepath);
            return null;
        }

        if (filesize($filepath) === 0) {
            log_message('error', '[TtsService] MP3 file is zero bytes: ' . $filepath);
            @unlink($filepath);
            return null;
        }

        return [
            'url'  => $this->ttsDirPublic . $filename,
            'file' => $filename,
            'path' => $filepath,
        ];
    }

    /**
     * Delete MP3 files in public/tts/ older than 1 hour.
     */
    public function cleanup(): void
    {
        if (!is_dir($this->ttsDir)) {
            return;
        }
        $cutoff = time() - $this->cleanupAge;
        foreach (glob($this->ttsDir . '*.mp3') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    /**
     * Check whether the edge-tts CLI binary is available on PATH.
     */
    public function isAvailable(): bool
    {
        $os  = strtoupper(substr(PHP_OS, 0, 3));
        $cmd = ($os === 'WIN') ? 'where edge-tts 2>NUL' : 'which edge-tts 2>/dev/null';
        $out = shell_exec($cmd);
        return !empty(trim((string) $out));
    }
}
