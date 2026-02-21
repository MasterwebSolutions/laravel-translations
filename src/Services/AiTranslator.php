<?php

namespace Masterweb\Translations\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiTranslator
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('translations.ai_api_key', '');
        $this->apiUrl = config('translations.ai_api_url', 'https://api.openai.com/v1/chat/completions');
        $this->model = config('translations.ai_model', 'gpt-4o-mini');
        $this->maxTokens = config('translations.ai_max_tokens', 2000);
    }

    /**
     * Check if AI translation is available.
     */
    public function isEnabled(): bool
    {
        return config('translations.ai_enabled', false) && !empty($this->apiKey);
    }

    /**
     * Translate text to a target language.
     *
     * @return array{success: bool, text: string, usage: array}
     */
    public function translate(string $text, string $targetLang, ?string $context = null): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'text' => '', 'usage' => [], 'error' => 'AI translation is disabled'];
        }

        $langNames = config('translations.language_meta', []);
        $langName = $langNames[$targetLang]['name'] ?? strtoupper($targetLang);

        $systemPrompt = "You are a professional translator. Translate the given text to {$langName} ({$targetLang}). "
            . "Rules:\n"
            . "- Return ONLY the translated text, no explanations.\n"
            . "- Preserve HTML tags, variables like :name or {name}, and markdown formatting.\n"
            . "- Maintain the same tone and register.\n"
            . "- Do not translate brand names or technical terms unless there's a standard translation.";

        if ($context) {
            $systemPrompt .= "\n- Context: {$context}";
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $text],
                    ],
                    'max_tokens' => $this->maxTokens,
                    'temperature' => 0.3,
                ]);

            if (!$response->successful()) {
                Log::warning('AI translation failed', ['status' => $response->status(), 'body' => $response->body()]);
                return ['success' => false, 'text' => '', 'usage' => [], 'error' => 'API error: ' . $response->status()];
            }

            $data = $response->json();
            $translated = $data['choices'][0]['message']['content'] ?? '';
            $usage = $data['usage'] ?? [];

            return [
                'success' => true,
                'text' => trim($translated),
                'usage' => [
                    'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                    'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    'total_tokens' => $usage['total_tokens'] ?? 0,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('AI translation exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'text' => '', 'usage' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Check translation quality.
     *
     * @return array{success: bool, issues: array}
     */
    public function qualityCheck(string $translations): array
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'issues' => [], 'error' => 'AI is disabled'];
        }

        $prompt = "Analyze these translations for quality. For each one with issues (grammar, tone, phrasing), "
            . "return a JSON array of objects with: key, issue, suggestion. Return [] if all good.\n\n" . $translations;

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a translation quality reviewer. Respond only with valid JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => $this->maxTokens,
                    'temperature' => 0.2,
                ]);

            if (!$response->successful()) {
                return ['success' => false, 'issues' => [], 'error' => 'API error'];
            }

            $text = $response->json('choices.0.message.content', '[]');
            $issues = json_decode($text, true) ?? [];

            return ['success' => true, 'issues' => $issues];
        } catch (\Throwable $e) {
            return ['success' => false, 'issues' => [], 'error' => $e->getMessage()];
        }
    }
}
