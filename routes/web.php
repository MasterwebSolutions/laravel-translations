<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Masterweb\Translations\Http\Controllers\TranslationController;
use Masterweb\Translations\Http\Controllers\TranslationMemoryController;

$prefix = config('translations.admin_prefix', 'admin/translations');
$middleware = config('translations.admin_middleware', ['web', 'auth']);
$namePrefix = config('translations.route_name_prefix', 'translations');

Route::prefix($prefix)->middleware($middleware)->group(function () use ($namePrefix) {

    // Health check (no auth needed for diagnostics)
    Route::get('/health', function () {
        if (!config('translations.health_check', true)) {
            return response()->json(['error' => 'Health check disabled'], 404);
        }

        $checks = [];

        // Check tables
        $tables = ['site_translations', 'translation_memories', 'translation_settings', 'ai_usage_logs'];
        foreach ($tables as $table) {
            try {
                $checks["table_{$table}"] = Schema::hasTable($table);
            } catch (\Throwable $e) {
                $checks["table_{$table}"] = false;
            }
        }

        // Check config
        $checks['config_loaded'] = config('translations.source_language') !== null;
        $checks['layout_configured'] = !empty(config('translations.admin_layout'));

        // Check layout exists
        $layout = config('translations.admin_layout', 'translations::layouts.standalone');
        try {
            $checks['layout_exists'] = view()->exists($layout);
        } catch (\Throwable $e) {
            $checks['layout_exists'] = false;
        }

        // Check AI
        $checks['ai_enabled'] = config('translations.ai_enabled', false);
        $checks['ai_key_set'] = !empty(config('translations.ai_api_key'));

        // Check helper function
        $checks['t_function_available'] = function_exists('t');

        $allOk = $checks['table_site_translations']
              && $checks['table_translation_settings']
              && $checks['config_loaded']
              && $checks['layout_exists']
              && $checks['t_function_available'];

        return response()->json([
            'status' => $allOk ? 'ok' : 'issues_found',
            'package' => 'masterweb/laravel-translations',
            'checks' => $checks,
            'admin_url' => url(config('translations.admin_prefix', 'admin/translations')),
        ], $allOk ? 200 : 500);
    })->withoutMiddleware(['auth'])->name("{$namePrefix}.health");

    // Translations CRUD
    Route::get('/', [TranslationController::class, 'index'])->name("{$namePrefix}.index");
    Route::post('/', [TranslationController::class, 'store'])->name("{$namePrefix}.store");
    Route::post('/sync', [TranslationController::class, 'syncTexts'])->name("{$namePrefix}.sync");
    Route::get('/coverage-stats', [TranslationController::class, 'coverageStats'])->name("{$namePrefix}.coverage_stats");
    Route::put('/{translation}', [TranslationController::class, 'update'])->name("{$namePrefix}.update");
    Route::delete('/{translation}', [TranslationController::class, 'destroy'])->name("{$namePrefix}.destroy");
    Route::post('/{translation}/inline-update', [TranslationController::class, 'inlineUpdate'])->name("{$namePrefix}.inline_update");
    Route::post('/{translation}/clear-value', [TranslationController::class, 'clearValue'])->name("{$namePrefix}.clear_value");
    Route::post('/bulk-delete', [TranslationController::class, 'bulkDelete'])->name("{$namePrefix}.bulk_delete");

    // Language management
    Route::post('/add-language', [TranslationController::class, 'addLanguage'])->name("{$namePrefix}.add_language");
    Route::post('/remove-language', [TranslationController::class, 'removeLanguage'])->name("{$namePrefix}.remove_language");
    Route::post('/set-source-lang', [TranslationController::class, 'setSourceLanguage'])->name("{$namePrefix}.set_source_lang");

    // AI Translation
    Route::post('/ai-translate-key', [TranslationController::class, 'aiTranslateKey'])->name("{$namePrefix}.ai_translate_key");
    Route::post('/ai-translate-batch', [TranslationController::class, 'aiTranslateBatch'])->name("{$namePrefix}.ai_translate_batch");
    Route::post('/ai-translate-bulk', [TranslationController::class, 'aiTranslateBulk'])->name("{$namePrefix}.ai_translate_bulk");
    Route::post('/ai-translate-all', [TranslationController::class, 'aiTranslateAll'])->name("{$namePrefix}.ai_translate_all");
    Route::post('/ai-quality-check', [TranslationController::class, 'aiQualityCheck'])->name("{$namePrefix}.ai_quality_check");
    Route::post('/token-estimate', [TranslationController::class, 'tokenEstimate'])->name("{$namePrefix}.token_estimate");
    Route::get('/ai-usage-stats', [TranslationController::class, 'aiUsageStats'])->name("{$namePrefix}.ai_usage_stats");

    // Translation Memory
    Route::get('/memory', [TranslationMemoryController::class, 'index'])->name("{$namePrefix}.memory.index");
    Route::post('/memory', [TranslationMemoryController::class, 'store'])->name("{$namePrefix}.memory.store");
    Route::put('/memory/{memory}', [TranslationMemoryController::class, 'update'])->name("{$namePrefix}.memory.update");
    Route::delete('/memory/{memory}', [TranslationMemoryController::class, 'destroy'])->name("{$namePrefix}.memory.destroy");
    Route::post('/memory/bulk-delete', [TranslationMemoryController::class, 'bulkDelete'])->name("{$namePrefix}.memory.bulk_delete");
    Route::post('/memory/purge', [TranslationMemoryController::class, 'purge'])->name("{$namePrefix}.memory.purge");
    Route::get('/memory/stats', [TranslationMemoryController::class, 'stats'])->name("{$namePrefix}.memory.stats");
    Route::get('/memory/search', [TranslationMemoryController::class, 'search'])->name("{$namePrefix}.memory.search");
    Route::post('/memory/import', [TranslationMemoryController::class, 'importExisting'])->name("{$namePrefix}.memory.import");
    Route::get('/memory/config', [TranslationMemoryController::class, 'getConfig'])->name("{$namePrefix}.memory.config");
    Route::post('/memory/config', [TranslationMemoryController::class, 'setConfig'])->name("{$namePrefix}.memory.config.save");
});
