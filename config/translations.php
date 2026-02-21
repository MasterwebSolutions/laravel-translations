<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Source Language
    |--------------------------------------------------------------------------
    | The primary language of your application. All other languages will be
    | translated FROM this language. Can be overridden at runtime via the
    | admin panel (stored in translation_settings table).
    */
    'source_language' => 'es',

    /*
    |--------------------------------------------------------------------------
    | Available Languages
    |--------------------------------------------------------------------------
    | Languages available in your application. The source language should
    | always be first. Can be managed dynamically via the admin panel.
    */
    'available_languages' => ['es', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    | Configure the admin panel routes for translation management.
    */
    'admin_prefix' => 'admin/translations',
    'admin_middleware' => ['web', 'auth'],
    'admin_layout' => 'layouts.app',  // Your app's admin layout

    /*
    |--------------------------------------------------------------------------
    | Locale Detection
    |--------------------------------------------------------------------------
    | How to detect the user's preferred language.
    | The SetLocale middleware reads from: URL prefix > cookie > default.
    */
    'locale_prefix' => true,   // Use /{locale}/... URL prefix
    'locale_cookie' => 'lang', // Cookie name for language preference
    'locale_cookie_days' => 365,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => 1800, // 30 minutes
    'cache_prefix' => 'mw_trans_',

    /*
    |--------------------------------------------------------------------------
    | AI Translation (Optional)
    |--------------------------------------------------------------------------
    | Enable AI-powered automatic translation. Supports OpenAI-compatible APIs.
    */
    'ai_enabled' => false,
    'ai_api_key' => env('TRANSLATIONS_AI_KEY', ''),
    'ai_api_url' => env('TRANSLATIONS_AI_URL', 'https://api.openai.com/v1/chat/completions'),
    'ai_model' => env('TRANSLATIONS_AI_MODEL', 'gpt-4o-mini'),
    'ai_max_tokens' => 2000,

    /*
    |--------------------------------------------------------------------------
    | Translation Memory
    |--------------------------------------------------------------------------
    | Reuse previous translations to save AI tokens and maintain consistency.
    */
    'memory_enabled' => true,
    'memory_auto_sync' => false,
    'memory_sync_interval_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Template Scanning
    |--------------------------------------------------------------------------
    | Directories to scan for t() calls when syncing translations.
    */
    'scan_paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Scanners
    |--------------------------------------------------------------------------
    | Register custom scanner classes to extract translatable strings from
    | your application's database tables (e.g., products, categories).
    | Each scanner must implement Masterweb\Translations\Contracts\TranslationScanner
    */
    'custom_scanners' => [
        // \App\Translations\ProductScanner::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Metadata
    |--------------------------------------------------------------------------
    | Display names and flags for languages. Add more as needed.
    */
    'language_meta' => [
        'es' => ['flag' => '🇪🇸', 'name' => 'Español'],
        'en' => ['flag' => '🇺🇸', 'name' => 'English'],
        'pt' => ['flag' => '🇧🇷', 'name' => 'Português'],
        'de' => ['flag' => '🇩🇪', 'name' => 'Deutsch'],
        'fr' => ['flag' => '🇫🇷', 'name' => 'Français'],
        'it' => ['flag' => '🇮🇹', 'name' => 'Italiano'],
        'ja' => ['flag' => '🇯🇵', 'name' => '日本語'],
        'zh' => ['flag' => '🇨🇳', 'name' => '中文'],
        'ko' => ['flag' => '🇰🇷', 'name' => '한국어'],
        'ru' => ['flag' => '🇷🇺', 'name' => 'Русский'],
        'ar' => ['flag' => '🇸🇦', 'name' => 'العربية'],
        'hi' => ['flag' => '🇮🇳', 'name' => 'हिन्दी'],
        'nl' => ['flag' => '🇳🇱', 'name' => 'Nederlands'],
        'sv' => ['flag' => '🇸🇪', 'name' => 'Svenska'],
        'pl' => ['flag' => '🇵🇱', 'name' => 'Polski'],
        'tr' => ['flag' => '🇹🇷', 'name' => 'Türkçe'],
    ],
];
