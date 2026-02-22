<?php

namespace Masterweb\Translations\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Models\TranslationMemory;
use Masterweb\Translations\Models\TranslationSetting;
use Masterweb\Translations\TranslationManager;

class TranslationMemoryController extends Controller
{
    public function index()
    {
        $memories = TranslationMemory::orderByDesc('updated_at')->paginate(50);
        $stats = TranslationMemory::getStats();
        return view('translations::translation-memory', compact('memories', 'stats'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'source_text' => 'required|string',
            'target_text' => 'required|string',
            'source_lang' => 'required|string|max:10',
            'target_lang' => 'required|string|max:10',
            'context' => 'nullable|string|max:100',
        ]);

        $memory = TranslationMemory::remember(
            $request->source_text, $request->target_text,
            $request->source_lang, $request->target_lang,
            $request->context
        );

        return response()->json(['success' => true, 'memory' => $memory]);
    }

    public function update(Request $request, TranslationMemory $memory): JsonResponse
    {
        $request->validate([
            'target_text' => 'required|string',
            'context' => 'nullable|string|max:100',
        ]);

        $memory->update([
            'target_text' => $request->target_text,
            'context' => $request->context,
        ]);

        return response()->json(['success' => true, 'memory' => $memory]);
    }

    public function destroy(TranslationMemory $memory): JsonResponse
    {
        $memory->delete();
        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        TranslationMemory::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true]);
    }

    public function stats(): JsonResponse
    {
        return response()->json(TranslationMemory::getStats());
    }

    public function purge(): JsonResponse
    {
        $count = TranslationMemory::count();
        TranslationMemory::truncate();
        return response()->json(['success' => true, 'deleted' => $count]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        $results = TranslationMemory::where('source_text', 'like', "%{$q}%")
            ->orWhere('target_text', 'like', "%{$q}%")
            ->orderByDesc('usage_count')
            ->limit(50)->get();
        return response()->json(['results' => $results]);
    }

    public function importExisting(): JsonResponse
    {
        $sourceLang = TranslationManager::getSourceLang();
        $targetLangs = array_values(array_filter(
            TranslationManager::getLanguages(), fn($l) => $l !== $sourceLang
        ));

        if (empty($targetLangs)) {
            return response()->json(['success' => false, 'error' => 'No target languages configured']);
        }

        $sources = SiteTranslation::where('lang', $sourceLang)->where('value', '!=', '')->get();
        $imported = 0;
        $skipped = 0;

        foreach ($sources as $src) {
            foreach ($targetLangs as $tLang) {
                $target = SiteTranslation::where('group', $src->group)
                    ->where('key', $src->key)->where('lang', $tLang)
                    ->where('value', '!=', '')->first();

                if (!$target) { $skipped++; continue; }

                $hash = hash('sha256', mb_strtolower(trim($src->value)));
                if (TranslationMemory::where('source_hash', $hash)
                    ->where('source_lang', $sourceLang)->where('target_lang', $tLang)->exists()) {
                    $skipped++; continue;
                }

                TranslationMemory::remember($src->value, $target->value, $sourceLang, $tLang, "{$src->group}.{$src->key}");
                $imported++;
            }
        }

        TranslationSetting::set('memory_last_sync', now()->toIso8601String());

        return response()->json([
            'success' => true, 'imported' => $imported,
            'skipped' => $skipped, 'total_memories' => TranslationMemory::count(),
        ]);
    }

    public function getConfig(): JsonResponse
    {
        return response()->json([
            'auto_sync_enabled' => TranslationSetting::get('memory_auto_sync', '0') === '1',
            'sync_interval_hours' => (int) (TranslationSetting::get('memory_sync_interval', '') ?: config('translations.memory_sync_interval_hours', 24)),
            'last_sync' => TranslationSetting::get('memory_last_sync', '') ?: null,
        ]);
    }

    public function setConfig(Request $request): JsonResponse
    {
        $request->validate([
            'auto_sync_enabled' => 'required|boolean',
            'sync_interval_hours' => 'required|integer|min:1|max:720',
        ]);

        TranslationSetting::set('memory_auto_sync', $request->boolean('auto_sync_enabled') ? '1' : '0');
        TranslationSetting::set('memory_sync_interval', (string) $request->input('sync_interval_hours'));

        return response()->json(['success' => true]);
    }
}
