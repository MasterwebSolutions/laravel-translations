<?php

use Masterweb\Translations\Models\SiteTranslation;
use Masterweb\Translations\TranslationManager;

if (!function_exists('t')) {
    /**
     * Get a translation from the database (site_translations table).
     * Uses dot notation: t('nav.home', 'Home')
     * First segment = group, second segment = key.
     */
    function t(string $key, string $default = ''): string
    {
        try {
            $locale = app()->getLocale();
            $parts = explode('.', $key, 2);

            if (count($parts) === 2) {
                $value = SiteTranslation::getValue($parts[0], $parts[1], $locale, $default);
            } else {
                $value = SiteTranslation::getValue('_root', $key, $locale, $default);
            }

            return $value !== '' ? $value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('t_raw')) {
    /**
     * Get a raw (array/mixed) value from translations.
     * JSON strings are decoded automatically.
     */
    function t_raw(string $key, $default = null)
    {
        try {
            $locale = app()->getLocale();
            $parts = explode('.', $key, 2);

            if (count($parts) === 2) {
                $value = SiteTranslation::getValue($parts[0], $parts[1], $locale, '');
            } else {
                $value = SiteTranslation::getValue('_root', $key, $locale, '');
            }

            if ($value === '') {
                return $default;
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return $value;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('available_languages')) {
    /**
     * Get configured languages with metadata (flag, name).
     * Returns array of ['code' => 'es', 'flag' => '🇪🇸', 'name' => 'Español']
     */
    function available_languages(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        try {
            $langMeta = config('translations.language_meta', []);
            $codes = TranslationManager::getLanguages();

            $cache = [];
            foreach ($codes as $code) {
                $meta = $langMeta[$code] ?? ['flag' => '🌐', 'name' => strtoupper($code)];
                $cache[] = ['code' => $code, 'flag' => $meta['flag'], 'name' => $meta['name']];
            }
            return $cache;
        } catch (\Throwable $e) {
            $langMeta = config('translations.language_meta', []);
            $codes = config('translations.available_languages', ['es', 'en']);
            $cache = [];
            foreach ($codes as $code) {
                $meta = $langMeta[$code] ?? ['flag' => '🌐', 'name' => strtoupper($code)];
                $cache[] = ['code' => $code, 'flag' => $meta['flag'], 'name' => $meta['name']];
            }
            return $cache;
        }
    }
}
