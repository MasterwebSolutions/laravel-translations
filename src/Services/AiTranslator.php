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
        $this->model = config('translations.ai_model', 'gpt-5-mini');
        $this->maxTokens = (int) config('translations.ai_max_tokens', 4096);
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

        $systemPrompt = "You are a translation machine. You ONLY output the translated text. "
            . "No explanations, no alternatives, no quotes, no context. Just the translation.";

        $userPrompt = "Translate the following text to {$langName} ({$targetLang}). "
            . "Output ONLY the translated text, nothing else.";

        if ($context) {
            $userPrompt .= "\nContext: {$context}";
        }

        $userPrompt .= "\n\n{$text}";

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                ])
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'max_completion_tokens' => $this->maxTokens,
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
                ])
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a translation quality reviewer. Respond only with valid JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_completion_tokens' => $this->maxTokens,
                    'response_format' => ['type' => 'json_object'],
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
