<?php

namespace Masterweb\Translations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteTranslation extends Model
{
    protected $fillable = ['group', 'key', 'lang', 'value'];

    public function getCacheTtl(): int
    {
        return config('translations.cache_ttl', 1800);
    }

    public function getCachePrefix(): string
    {
        return config('translations.cache_prefix', 'mw_trans_');
    }

    // ── Scopes ──

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeByLang($query, string $lang)
    {
        return $query->where('lang', $lang);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    // ── Static helpers ──

    /**
     * Get a single translation value.
     */
    public static function getValue(string $group, string $key, string $lang, string $default = ''): string
    {
        $all = static::getAllForLang($lang);
        return $all[$group][$key] ?? $default;
    }

    /**
     * Get all translations for a group+lang as key=>value array.
     */
    public static function getGroup(string $group, string $lang): array
    {
        $all = static::getAllForLang($lang);
        return $all[$group] ?? [];
    }

    /**
     * Get all translations for a lang, grouped by group.
     * Cached for configured TTL.
     */
    public static function getAllForLang(string $lang): array
    {
        $prefix = config('translations.cache_prefix', 'mw_trans_');
        $ttl = config('translations.cache_ttl', 1800);

        try {
            return Cache::remember($prefix . $lang, $ttl, function () use ($lang) {
                $rows = static::where('lang', $lang)->get(['group', 'key', 'value']);
                $result = [];
                foreach ($rows as $row) {
                    $result[$row->group][$row->key] = $row->value;
                }
                return $result;
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Clear all cached translations.
     */
    public static function clearCache(): void
    {
        $prefix = config('translations.cache_prefix', 'mw_trans_');

        try {
            // Clear cache for all languages that exist in DB
            $langs = static::distinct()->pluck('lang')->toArray();
            foreach ($langs as $lang) {
                Cache::forget($prefix . $lang);
            }
        } catch (\Throwable $e) {
            // Table may not exist yet
        }

        // Also clear common fallback codes
        foreach (['es', 'en', 'pt', 'de', 'fr', 'it', 'ja', 'zh'] as $lang) {
            Cache::forget($prefix . $lang);
        }
    }
}
