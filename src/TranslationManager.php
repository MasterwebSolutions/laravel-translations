<?php

namespace Masterweb\Translations;

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\Models\TranslationSetting;
use Illuminate\Support\Facades\DB;

class TranslationManager
{
    /**
     * Get source (primary) language.
     */
    public static function getSourceLang(): string
    {
        return TranslationSetting::get('source_language', '')
            ?: config('translations.source_language', 'es');
    }

    /**
     * Set source language.
     */
    public static function setSourceLang(string $lang): void
    {
        TranslationSetting::set('source_language', $lang);
    }

    /**
     * Get configured languages. Source language is always first.
     */
    public static function getLanguages(): array
    {
        $src = self::getSourceLang();
        $raw = TranslationSetting::get('available_languages', '');

        if ($raw) {
            $langs = json_decode($raw, true);
            if (is_array($langs) && !empty($langs)) {
                if (!in_array($src, $langs)) array_unshift($langs, $src);
                return $langs;
            }
        }

        // Fallback to config
        $configLangs = config('translations.available_languages', [$src]);
        if (!in_array($src, $configLangs)) array_unshift($configLangs, $src);
        return $configLangs;
    }

    /**
     * Save languages list to DB.
     */
    public static function saveLanguages(array $langs): void
    {
        $src = self::getSourceLang();
        if (!in_array($src, $langs)) array_unshift($langs, $src);
        $langs = array_values(array_unique($langs));
        TranslationSetting::set('available_languages', json_encode($langs));
    }

    /**
     * Add a language.
     */
    public static function addLanguage(string $lang): void
    {
        $langs = self::getLanguages();
        if (!in_array($lang, $langs)) {
            $langs[] = $lang;
            self::saveLanguages($langs);
        }
    }

    /**
     * Remove a language (cannot remove source).
     */
    public static function removeLanguage(string $lang): bool
    {
        $src = self::getSourceLang();
        if ($lang === $src) return false;

        $langs = self::getLanguages();
        $langs = array_values(array_filter($langs, fn($l) => $l !== $lang));
        self::saveLanguages($langs);

        // Delete translations for removed language
        SiteTranslation::where('lang', $lang)->delete();
        SiteTranslation::clearCache();

        return true;
    }

    /**
     * Sync: scan templates for t() calls → populate source-lang entries.
     *
     * @return array{created: int, updated: int}
     */
    public static function syncTexts(): array
    {
        $src = self::getSourceLang();
        $allLangs = self::getLanguages();
        $created = 0;
        $updated = 0;

        // 1. Scan blade templates for t('group.key', 'fallback') calls
        $tCalls = self::scanTemplateTCalls();
        foreach ($tCalls as $item) {
            foreach ($allLangs as $lang) {
                $value = $lang === $src ? $item['fallback'] : '';
                $existing = SiteTranslation::where('group', $item['group'])
                    ->where('key', $item['key'])->where('lang', $lang)->first();
                if (!$existing) {
                    SiteTranslation::create([
                        'group' => $item['group'], 'key' => $item['key'],
                        'lang' => $lang, 'value' => $value,
                    ]);
                    $created++;
                } elseif ($lang === $src && $existing->value === '' && $item['fallback'] !== '') {
                    $existing->update(['value' => $item['fallback']]);
                    $updated++;
                }
            }
        }

        // 2. Run custom scanners
        $customScanners = config('translations.custom_scanners', []);
        foreach ($customScanners as $scannerClass) {
            if (!class_exists($scannerClass)) continue;
            $scanner = new $scannerClass();
            if (!$scanner instanceof \Masterweb\Translations\Contracts\TranslationScanner) continue;

            $items = $scanner->scan();
            foreach ($items as $item) {
                foreach ($allLangs as $lang) {
                    $value = $lang === $src ? ($item['fallback'] ?? '') : '';
                    $existing = SiteTranslation::where('group', $item['group'])
                        ->where('key', $item['key'])->where('lang', $lang)->first();
                    if (!$existing) {
                        SiteTranslation::create([
                            'group' => $item['group'], 'key' => $item['key'],
                            'lang' => $lang, 'value' => $value,
                        ]);
                        $created++;
                    } elseif ($lang === $src && $existing->value === '' && ($item['fallback'] ?? '') !== '') {
                        $existing->update(['value' => $item['fallback']]);
                        $updated++;
                    }
                }
            }
        }

        SiteTranslation::clearCache();
        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Scan blade templates for t('group.key', 'fallback') calls.
     */
    public static function scanTemplateTCalls(): array
    {
        $results = [];
        $scanPaths = config('translations.scan_paths', [resource_path('views')]);

        foreach ($scanPaths as $path) {
            if (!is_dir($path)) continue;

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') continue;
                $content = file_get_contents($file->getPathname());
                preg_match_all("/t\(\s*'([^']+)'\s*,\s*'([^']*)'\s*\)/", $content, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $parts = explode('.', $m[1], 2);
                    $group = count($parts) === 2 ? $parts[0] : '_root';
                    $key = count($parts) === 2 ? $parts[1] : $parts[0];
                    $results["{$group}.{$key}"] = ['group' => $group, 'key' => $key, 'fallback' => $m[2]];
                }
            }
        }

        return array_values($results);
    }

    /**
     * Get translation coverage stats per group.
     */
    public static function coverageStats(): array
    {
        $src = self::getSourceLang();
        $langs = self::getLanguages();
        $otherLangs = array_values(array_filter($langs, fn($l) => $l !== $src));

        $groups = SiteTranslation::where('lang', $src)->distinct()->pluck('group')->sort()->values();
        $stats = [];

        foreach ($groups as $group) {
            $totalKeys = SiteTranslation::where('group', $group)->where('lang', $src)->count();
            $missing = [];

            foreach ($otherLangs as $lang) {
                $translated = SiteTranslation::where('group', $group)
                    ->where('lang', $lang)
                    ->where('value', '!=', '')
                    ->count();
                $missing[$lang] = $totalKeys - $translated;
            }

            $stats[] = [
                'group' => $group,
                'total_keys' => $totalKeys,
                'missing_by_lang' => $missing,
                'total_missing' => array_sum($missing),
            ];
        }

        return $stats;
    }
}
