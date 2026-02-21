<?php

namespace Masterweb\Translations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Models\TranslationMemory;
use Masterweb\Translations\Services\AiTranslator;
use Masterweb\Translations\TranslationManager;

class TranslationController extends Controller
{
    // ── Page ──

    public function index(Request $request)
    {
        $languages = TranslationManager::getLanguages();
        $sourceLang = TranslationManager::getSourceLang();
        $filterGroup = $request->query('group', '');

        $groups = SiteTranslation::where('lang', $sourceLang)->distinct()->pluck('group')->sort()->values();

        $query = SiteTranslation::where('lang', $sourceLang)->orderBy('group')->orderBy('key');
        if ($filterGroup) $query->where('group', $filterGroup);
        $sourceKeys = $query->get();

        $otherLangs = array_values(array_filter($languages, fn($l) => $l !== $sourceLang));
        $otherTranslations = [];
        if (!empty($otherLangs)) {
            $rows = SiteTranslation::whereIn('lang', $otherLangs);
            if ($filterGroup) $rows->where('group', $filterGroup);
            foreach ($rows->get() as $t) {
                $otherTranslations["{$t->group}.{$t->key}"][$t->lang] = $t;
            }
        }

        return view('translations::translations', compact(
            'languages', 'sourceLang', 'groups', 'sourceKeys', 'otherTranslations', 'filterGroup'
        ));
    }

    // ── CRUD ──

    public function store(Request $request)
    {
        $request->validate([
            'group' => 'required|string|max:50',
            'key' => 'required|string|max:100',
            'value' => 'nullable|string',
        ]);

        $src = TranslationManager::getSourceLang();
        $allLangs = TranslationManager::getLanguages();

        foreach ($allLangs as $lang) {
            $val = $lang === $src ? ($request->input('value', '') ?: '') : '';
            SiteTranslation::firstOrCreate(
                ['group' => $request->group, 'key' => $request->key, 'lang' => $lang],
                ['value' => $val]
            );
        }

        SiteTranslation::clearCache();
        Log::info("[Translations] Created key: {$request->group}.{$request->key}");

        if ($request->ajax()) return response()->json(['success' => true]);
        return back()->with('success', 'Translation created.');
    }

    public function update(Request $request, SiteTranslation $translation)
    {
        $request->validate(['value' => 'required|string']);
        $translation->update(['value' => $request->input('value')]);
        SiteTranslation::clearCache();

        if ($request->ajax()) return response()->json(['success' => true]);
        return back()->with('success', 'Translation updated.');
    }

    public function destroy(SiteTranslation $translation)
    {
        $translation->delete();
        SiteTranslation::clearCache();
        return back()->with('success', 'Translation deleted.');
    }

    public function inlineUpdate(Request $request, SiteTranslation $translation): JsonResponse
    {
        $request->validate(['value' => 'required|string']);
        $translation->update(['value' => $request->input('value')]);
        SiteTranslation::clearCache();
        return response()->json(['success' => true]);
    }

    public function clearValue(SiteTranslation $translation): JsonResponse
    {
        $src = TranslationManager::getSourceLang();
        if ($translation->lang === $src) {
            return response()->json(['success' => false, 'error' => 'Cannot clear source language text.']);
        }
        $translation->update(['value' => '']);
        SiteTranslation::clearCache();
        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);
        $src = TranslationManager::getSourceLang();
        $count = SiteTranslation::whereIn('id', $request->input('ids'))
            ->where('lang', '!=', $src)
            ->delete();
        SiteTranslation::clearCache();
        return response()->json(['success' => true, 'deleted' => $count]);
    }

    // ── Sync ──

    public function syncTexts(): JsonResponse
    {
        $result = TranslationManager::syncTexts();
        return response()->json(['success' => true, ...$result]);
    }

    // ── Coverage Stats ──

    public function coverageStats(): JsonResponse
    {
        $languages = TranslationManager::getLanguages();
        $src = TranslationManager::getSourceLang();
        $totalKeys = SiteTranslation::where('lang', $src)->count();
        $stats = [];

        foreach ($languages as $lang) {
            $count = SiteTranslation::where('lang', $lang)->where('value', '!=', '')->count();
            $stats[$lang] = [
                'count' => $count,
                'total' => $totalKeys,
                'percent' => $totalKeys > 0 ? round(($count / $totalKeys) * 100, 1) : 0,
            ];
        }

        return response()->json(['success' => true, 'stats' => $stats, 'total_keys' => $totalKeys]);
    }

    // ── Language management ──

    public function addLanguage(Request $request): JsonResponse
    {
        $request->validate(['lang' => 'required|string|min:2|max:10']);
        $lang = strtolower(trim($request->input('lang')));

        $langs = TranslationManager::getLanguages();
        if (in_array($lang, $langs)) {
            return response()->json(['success' => false, 'error' => "Language '{$lang}' already exists"]);
        }

        TranslationManager::addLanguage($lang);
        SiteTranslation::clearCache();
        Log::info("[Translations] Added language: {$lang}");

        return response()->json(['success' => true, 'languages' => TranslationManager::getLanguages()]);
    }

    public function removeLanguage(Request $request): JsonResponse
    {
        $request->validate(['lang' => 'required|string']);
        $lang = $request->input('lang');

        if (!TranslationManager::removeLanguage($lang)) {
            return response()->json(['success' => false, 'error' => 'Cannot remove source language']);
        }

        Log::info("[Translations] Removed language: {$lang}");
        return response()->json(['success' => true, 'languages' => TranslationManager::getLanguages()]);
    }

    public function setSourceLanguage(Request $request): JsonResponse
    {
        $request->validate(['lang' => 'required|string|min:2|max:10']);
        $lang = strtolower(trim($request->input('lang')));

        $langs = TranslationManager::getLanguages();
        if (!in_array($lang, $langs)) {
            return response()->json(['success' => false, 'error' => "Language '{$lang}' is not configured"]);
        }

        TranslationManager::setSourceLang($lang);
        SiteTranslation::clearCache();
        Log::info("[Translations] Source language changed to: {$lang}");

        return response()->json(['success' => true, 'source_lang' => $lang]);
    }

    // ── AI Translation ──

    public function aiTranslateKey(Request $request): JsonResponse
    {
        $request->validate(['group' => 'required|string', 'key' => 'required|string']);
        $group = $request->input('group');
        $key = $request->input('key');
        $targetLangs = $request->input('langs', []);

        $src = TranslationManager::getSourceLang();
        $source = SiteTranslation::where('group', $group)->where('key', $key)->where('lang', $src)->first();
        if (!$source) {
            return response()->json(['success' => false, 'error' => 'Source text not found']);
        }

        if (empty($targetLangs)) {
            $targetLangs = array_values(array_filter(TranslationManager::getLanguages(), fn($l) => $l !== $src));
        }

        $ai = new AiTranslator();
        $translated = 0;
        $totalTokens = 0;

        foreach ($targetLangs as $lang) {
            $existing = SiteTranslation::where('group', $group)->where('key', $key)->where('lang', $lang)->first();
            if ($existing && $existing->value !== '' && !$request->boolean('force')) continue;

            $result = $ai->translate($source->value, $lang);
            if ($result['success'] && !empty($result['text'])) {
                $val = trim($result['text']);
                SiteTranslation::updateOrCreate(
                    ['group' => $group, 'key' => $key, 'lang' => $lang],
                    ['value' => $val]
                );
                $translated++;
                $totalTokens += $result['usage']['total_tokens'] ?? 0;
                $this->logAiUsage('translate_key', $result['usage'], 1, "{$group}.{$key} → {$lang}");
            }
        }

        SiteTranslation::clearCache();
        return response()->json(['success' => true, 'translated' => $translated, 'tokens_used' => $totalTokens]);
    }

    public function aiTranslateBatch(Request $request): JsonResponse
    {
        $request->validate(['keys' => 'required|array|min:1|max:10']);
        $keys = $request->input('keys');
        $force = $request->boolean('force');

        $src = TranslationManager::getSourceLang();
        $targetLangs = array_values(array_filter(TranslationManager::getLanguages(), fn($l) => $l !== $src));

        $ai = new AiTranslator();
        $translated = 0;
        $fromMemory = 0;
        $errors = 0;
        $totalTokens = 0;

        foreach ($keys as $k) {
            $source = SiteTranslation::where('group', $k['group'])
                ->where('key', $k['key'])->where('lang', $src)->first();
            if (!$source || $source->value === '') continue;

            foreach ($targetLangs as $lang) {
                $existing = SiteTranslation::where('group', $source->group)
                    ->where('key', $source->key)->where('lang', $lang)->first();
                if ($existing && $existing->value !== '' && !$force) continue;

                // Check Translation Memory first
                if (config('translations.memory_enabled', true)) {
                    $memory = TranslationMemory::findExact($source->value, $src, $lang);
                    if ($memory) {
                        SiteTranslation::updateOrCreate(
                            ['group' => $source->group, 'key' => $source->key, 'lang' => $lang],
                            ['value' => $memory->target_text]
                        );
                        $fromMemory++;
                        $translated++;
                        continue;
                    }
                }

                // AI translate
                $result = $ai->translate($source->value, $lang);
                if ($result['success'] && !empty($result['text'])) {
                    $val = trim($result['text']);
                    SiteTranslation::updateOrCreate(
                        ['group' => $source->group, 'key' => $source->key, 'lang' => $lang],
                        ['value' => $val]
                    );
                    $translated++;
                    $totalTokens += $result['usage']['total_tokens'] ?? 0;
                    $this->logAiUsage('translate_batch', $result['usage'], 1, "{$source->group}.{$source->key} → {$lang}");

                    // Save to Translation Memory
                    if (config('translations.memory_enabled', true)) {
                        TranslationMemory::remember($source->value, $val, $src, $lang, "{$source->group}.{$source->key}");
                    }
                } else {
                    $errors++;
                }
            }
        }

        SiteTranslation::clearCache();
        return response()->json([
            'success' => true, 'translated' => $translated,
            'from_memory' => $fromMemory, 'errors' => $errors,
            'tokens_used' => $totalTokens,
        ]);
    }

    public function aiTranslateBulk(Request $request): JsonResponse
    {
        $request->validate(['langs' => 'required|array|min:1']);
        $targetLangs = $request->input('langs');
        $group = $request->input('group', '');
        $force = $request->boolean('force');

        $src = TranslationManager::getSourceLang();
        $query = SiteTranslation::where('lang', $src);
        if ($group) $query->where('group', $group);
        $sourceKeys = $query->get();

        if ($sourceKeys->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No source keys found']);
        }

        $ai = new AiTranslator();
        $translated = 0;
        $errors = 0;
        $totalTokens = 0;

        foreach ($sourceKeys as $source) {
            foreach ($targetLangs as $lang) {
                if ($lang === $src) continue;
                $existing = SiteTranslation::where('group', $source->group)
                    ->where('key', $source->key)->where('lang', $lang)->first();
                if ($existing && $existing->value !== '' && !$force) continue;

                $result = $ai->translate($source->value, $lang);
                if ($result['success'] && !empty($result['text'])) {
                    $val = trim($result['text']);
                    SiteTranslation::updateOrCreate(
                        ['group' => $source->group, 'key' => $source->key, 'lang' => $lang],
                        ['value' => $val]
                    );
                    $translated++;
                    $totalTokens += $result['usage']['total_tokens'] ?? 0;
                    $this->logAiUsage('translate_bulk', $result['usage'], 1, "{$source->group}.{$source->key} → {$lang}");
                } else {
                    $errors++;
                }
            }
        }

        SiteTranslation::clearCache();
        return response()->json(['success' => true, 'translated' => $translated, 'errors' => $errors, 'tokens_used' => $totalTokens]);
    }

    public function aiTranslateAll(Request $request): JsonResponse
    {
        $src = TranslationManager::getSourceLang();
        $request->merge(['langs' => array_values(array_filter(TranslationManager::getLanguages(), fn($l) => $l !== $src))]);
        return $this->aiTranslateBulk($request);
    }

    public function aiQualityCheck(Request $request): JsonResponse
    {
        $request->validate(['lang' => 'required|string']);
        $lang = $request->input('lang');

        $translations = SiteTranslation::where('lang', $lang)->where('value', '!=', '')->limit(50)->get();
        if ($translations->isEmpty()) {
            return response()->json(['success' => false, 'error' => "No translations for {$lang}"]);
        }

        $ai = new AiTranslator();
        $batch = $translations->map(fn($t) => "{$t->group}.{$t->key}: {$t->value}")->implode("\n");
        $result = $ai->qualityCheck($batch);

        return response()->json(['success' => $result['success'], 'issues' => $result['issues'] ?? [], 'checked' => $translations->count()]);
    }

    // ── Token estimate ──

    public function tokenEstimate(Request $request): JsonResponse
    {
        $src = TranslationManager::getSourceLang();
        $langs = array_values(array_filter(TranslationManager::getLanguages(), fn($l) => $l !== $src));
        $group = $request->input('group', '');

        $query = SiteTranslation::where('lang', $src);
        if ($group) $query->where('group', $group);

        $sources = $query->get();
        $totalChars = $sources->sum(fn($s) => mb_strlen($s->value));

        $needTranslation = 0;
        foreach ($sources as $s) {
            foreach ($langs as $l) {
                $exists = SiteTranslation::where('group', $s->group)
                    ->where('key', $s->key)->where('lang', $l)
                    ->where('value', '!=', '')->exists();
                if (!$exists) $needTranslation++;
            }
        }

        $tokensPerCall = 50 + (int)($totalChars / max($sources->count(), 1) / 4) * 2;
        $estimatedTokens = $needTranslation * $tokensPerCall;

        return response()->json([
            'success' => true,
            'texts_count' => $sources->count(),
            'target_langs' => count($langs),
            'need_translation' => $needTranslation,
            'estimated_tokens' => $estimatedTokens,
        ]);
    }

    // ── AI Usage Stats ──

    public function aiUsageStats(): JsonResponse
    {
        $today = DB::table('ai_usage_logs')
            ->whereDate('created_at', today())
            ->selectRaw('SUM(total_tokens) as total, COUNT(*) as calls')
            ->first();

        $allTime = DB::table('ai_usage_logs')
            ->selectRaw('SUM(total_tokens) as total, COUNT(*) as calls')
            ->first();

        return response()->json([
            'success' => true,
            'today' => ['tokens' => (int)($today->total ?? 0), 'calls' => (int)($today->calls ?? 0)],
            'all_time' => ['tokens' => (int)($allTime->total ?? 0), 'calls' => (int)($allTime->calls ?? 0)],
        ]);
    }

    // ── Internal ──

    private function logAiUsage(string $action, array $usage, int $items = 1, string $details = ''): void
    {
        try {
            DB::table('ai_usage_logs')->insert([
                'action' => $action,
                'model' => config('translations.ai_model', 'gpt-4o-mini'),
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens' => $usage['total_tokens'] ?? 0,
                'items_count' => $items,
                'details' => $details,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail - logging shouldn't break translations
        }
    }
}
