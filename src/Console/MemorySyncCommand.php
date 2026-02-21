<?php

namespace Masterweb\Translations\Console;

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Models\TranslationMemory;
use Masterweb\Translations\Models\TranslationSetting;
use Masterweb\Translations\TranslationManager;
use Illuminate\Console\Command;

class MemorySyncCommand extends Command
{
    protected $signature = 'translations:memory-sync {--force : Run even if auto-sync is disabled}';
    protected $description = 'Sync existing translations into translation memory for backup/reuse';

    public function handle(): int
    {
        $autoEnabled = TranslationSetting::get('memory_auto_sync', '0') === '1'
            || config('translations.memory_auto_sync', false);
        $intervalHours = (int) (TranslationSetting::get('memory_sync_interval', '')
            ?: config('translations.memory_sync_interval_hours', 24));
        $lastSync = TranslationSetting::get('memory_last_sync', '');

        if (!$this->option('force') && !$autoEnabled) {
            $this->info('Auto-sync is disabled. Use --force to run anyway.');
            return 0;
        }

        if (!$this->option('force') && $lastSync) {
            $lastSyncTime = \Carbon\Carbon::parse($lastSync);
            $hoursSince = now()->diffInHours($lastSyncTime);
            if ($hoursSince < $intervalHours) {
                $this->info("Last sync was {$hoursSince}h ago (interval: {$intervalHours}h). Skipping.");
                return 0;
            }
        }

        $this->info('Starting translation memory sync...');

        $sourceLang = TranslationManager::getSourceLang();
        $langs = TranslationManager::getLanguages();
        $targetLangs = array_values(array_filter($langs, fn($l) => $l !== $sourceLang));

        if (empty($targetLangs)) {
            $this->warn('No target languages configured.');
            return 0;
        }

        $sources = SiteTranslation::where('lang', $sourceLang)
            ->where('value', '!=', '')
            ->get();

        $imported = 0;
        $skipped = 0;

        foreach ($sources as $src) {
            foreach ($targetLangs as $tLang) {
                $target = SiteTranslation::where('group', $src->group)
                    ->where('key', $src->key)
                    ->where('lang', $tLang)
                    ->where('value', '!=', '')
                    ->first();

                if (!$target) {
                    $skipped++;
                    continue;
                }

                $hash = hash('sha256', mb_strtolower(trim($src->value)));
                $exists = TranslationMemory::where('source_hash', $hash)
                    ->where('source_lang', $sourceLang)
                    ->where('target_lang', $tLang)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                TranslationMemory::remember(
                    $src->value,
                    $target->value,
                    $sourceLang,
                    $tLang,
                    "{$src->group}.{$src->key}"
                );
                $imported++;
            }
        }

        TranslationSetting::set('memory_last_sync', now()->toIso8601String());

        $this->info("Done. Imported: {$imported}, Skipped: {$skipped}, Total: " . TranslationMemory::count());

        return 0;
    }
}
