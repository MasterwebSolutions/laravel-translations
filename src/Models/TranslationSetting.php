<?php

namespace Masterweb\Translations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TranslationSetting extends Model
{
    protected $table = 'translation_settings';
    protected $primaryKey = 'key_name';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['key_name', 'value'];

    protected static ?array $memoryCache = null;

    /**
     * Get a setting value from DB, falling back to config.
     */
    public static function get(string $key, string $default = ''): string
    {
        if (static::$memoryCache === null) {
            static::loadAll();
        }
        return static::$memoryCache[$key] ?? $default;
    }

    /**
     * Set a setting value in DB.
     */
    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(
            ['key_name' => $key],
            ['value' => $value]
        );
        static::clearCache();
    }

    protected static function loadAll(): void
    {
        static::$memoryCache = Cache::remember('mw_translation_settings', 300, function () {
            try {
                return static::pluck('value', 'key_name')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public static function clearCache(): void
    {
        static::$memoryCache = null;
        Cache::forget('mw_translation_settings');
    }
}
