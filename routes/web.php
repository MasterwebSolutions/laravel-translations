<?php

use Illuminate\Support\Facades\Route;
use Masterweb\Translations\Http\Controllers\TranslationController;
use Masterweb\Translations\Http\Controllers\TranslationMemoryController;

$prefix = config('translations.admin_prefix', 'admin/translations');
$middleware = config('translations.admin_middleware', ['web', 'auth']);

Route::prefix($prefix)->middleware($middleware)->group(function () {

    // Translations CRUD
    Route::get('/', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('/', [TranslationController::class, 'store'])->name('translations.store');
    Route::post('/sync', [TranslationController::class, 'syncTexts'])->name('translations.sync');
    Route::get('/coverage-stats', [TranslationController::class, 'coverageStats'])->name('translations.coverage_stats');
    Route::put('/{translation}', [TranslationController::class, 'update'])->name('translations.update');
    Route::delete('/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');
    Route::post('/{translation}/inline-update', [TranslationController::class, 'inlineUpdate'])->name('translations.inline_update');
    Route::post('/{translation}/clear-value', [TranslationController::class, 'clearValue'])->name('translations.clear_value');
    Route::post('/bulk-delete', [TranslationController::class, 'bulkDelete'])->name('translations.bulk_delete');

    // Language management
    Route::post('/add-language', [TranslationController::class, 'addLanguage'])->name('translations.add_language');
    Route::post('/remove-language', [TranslationController::class, 'removeLanguage'])->name('translations.remove_language');
    Route::post('/set-source-lang', [TranslationController::class, 'setSourceLanguage'])->name('translations.set_source_lang');

    // AI Translation
    Route::post('/ai-translate-key', [TranslationController::class, 'aiTranslateKey'])->name('translations.ai_translate_key');
    Route::post('/ai-translate-batch', [TranslationController::class, 'aiTranslateBatch'])->name('translations.ai_translate_batch');
    Route::post('/ai-translate-bulk', [TranslationController::class, 'aiTranslateBulk'])->name('translations.ai_translate_bulk');
    Route::post('/ai-translate-all', [TranslationController::class, 'aiTranslateAll'])->name('translations.ai_translate_all');
    Route::post('/ai-quality-check', [TranslationController::class, 'aiQualityCheck'])->name('translations.ai_quality_check');
    Route::post('/token-estimate', [TranslationController::class, 'tokenEstimate'])->name('translations.token_estimate');
    Route::get('/ai-usage-stats', [TranslationController::class, 'aiUsageStats'])->name('translations.ai_usage_stats');

    // Translation Memory
    Route::get('/memory', [TranslationMemoryController::class, 'index'])->name('translations.memory.index');
    Route::post('/memory', [TranslationMemoryController::class, 'store'])->name('translations.memory.store');
    Route::put('/memory/{memory}', [TranslationMemoryController::class, 'update'])->name('translations.memory.update');
    Route::delete('/memory/{memory}', [TranslationMemoryController::class, 'destroy'])->name('translations.memory.destroy');
    Route::post('/memory/bulk-delete', [TranslationMemoryController::class, 'bulkDelete'])->name('translations.memory.bulk_delete');
    Route::post('/memory/purge', [TranslationMemoryController::class, 'purge'])->name('translations.memory.purge');
    Route::get('/memory/stats', [TranslationMemoryController::class, 'stats'])->name('translations.memory.stats');
    Route::get('/memory/search', [TranslationMemoryController::class, 'search'])->name('translations.memory.search');
    Route::post('/memory/import', [TranslationMemoryController::class, 'importExisting'])->name('translations.memory.import');
    Route::get('/memory/config', [TranslationMemoryController::class, 'getConfig'])->name('translations.memory.config');
    Route::post('/memory/config', [TranslationMemoryController::class, 'setConfig'])->name('translations.memory.config.save');
});
