# Laravel Translations

A self-contained, database-driven translation management system for Laravel with AI-powered translation, Translation Memory, and a full admin UI.

## Features

- **Database-driven translations** — All translations stored in `site_translations` table
- **Helper function `t()`** — Use `{{ t('nav.home', 'Home') }}` in Blade templates
- **Admin UI** — Full CRUD, inline editing, search, filtering by group
- **Multi-language management** — Add/remove languages dynamically from the admin panel
- **Template scanning** — Auto-detect `t()` calls in Blade templates and sync to DB
- **AI Translation** — Automatic translation via OpenAI-compatible APIs (configurable)
- **Translation Memory** — Reuse previous translations to save AI tokens and maintain consistency
- **Coverage stats** — See translation completion % per language
- **Quality check** — AI-powered translation quality review
- **Custom scanners** — Hook into your app's DB tables to extract translatable strings
- **Locale detection** — Middleware detects language from URL prefix, cookie, or default
- **Cache** — All translations cached (configurable TTL)

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## Installation

### 1. Install via Composer

**From GitHub (private repo):**

Add the repository to your app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/MasterwebSolutions/laravel-translations.git"
        }
    ]
}
```

Then install:

```bash
composer require masterweb/laravel-translations
```

**From local path (development):**

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-translations"
        }
    ]
}
```

### 2. Publish config

```bash
php artisan vendor:publish --tag=translations-config
```

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Configure

Edit `config/translations.php`:

```php
return [
    'source_language' => 'es',
    'available_languages' => ['es', 'en'],
    
    // Your admin layout (must have @yield('content') and @stack('scripts'))
    'admin_layout' => 'layouts.admin',
    
    // Admin route protection
    'admin_prefix' => 'admin/translations',
    'admin_middleware' => ['web', 'auth'],
    
    // AI (optional)
    'ai_enabled' => true,
    'ai_api_key' => env('TRANSLATIONS_AI_KEY'),
    'ai_model' => 'gpt-4o-mini',
];
```

### 5. Add locale middleware (optional)

In your `bootstrap/app.php` or route service provider:

```php
Route::middleware([\Masterweb\Translations\Http\Middleware\SetLocale::class])
    ->prefix('{locale?}')
    ->group(function () {
        // Your localized routes
    });
```

## Usage

### In Blade templates

```blade
{{-- Simple translation --}}
<h1>{{ t('hero.title', 'Welcome to our site') }}</h1>
<p>{{ t('hero.subtitle', 'The best solution for your needs') }}</p>

{{-- Navigation --}}
<a href="#solutions">{{ t('nav.solutions', 'Solutions') }}</a>
<a href="#pricing">{{ t('nav.pricing', 'Pricing') }}</a>

{{-- Footer --}}
<p>{{ t('footer.copyright', '© 2024 My Company') }}</p>
```

### Dot notation

The first segment is the **group**, the second is the **key**:

```
t('nav.home', 'Home')       → group: "nav", key: "home"
t('footer.desc', 'About')   → group: "footer", key: "desc"
t('hero.cta', 'Get Started') → group: "hero", key: "cta"
```

### Sync translations from templates

After adding new `t()` calls, sync them to the database:

```bash
# Via admin UI: click "Sync Templates" button
# Or via code:
\Masterweb\Translations\TranslationManager::syncTexts();
```

### Custom Scanners

To extract translatable strings from your app's database (e.g., products, categories):

```php
// app/Translations/ProductScanner.php
namespace App\Translations;

use Masterweb\Translations\Contracts\TranslationScanner;
use App\Models\Product;

class ProductScanner implements TranslationScanner
{
    public function scan(): array
    {
        $items = [];
        foreach (Product::all() as $product) {
            $items[] = [
                'group' => 'products',
                'key' => "product_{$product->id}_name",
                'fallback' => $product->name,
            ];
            $items[] = [
                'group' => 'products',
                'key' => "product_{$product->id}_description",
                'fallback' => $product->description,
            ];
        }
        return $items;
    }
}
```

Register it in `config/translations.php`:

```php
'custom_scanners' => [
    \App\Translations\ProductScanner::class,
],
```

### Translation Memory

Sync existing translations to memory for future reuse:

```bash
php artisan translations:memory-sync --force
```

Schedule it (in `routes/console.php` or `app/Console/Kernel.php`):

```php
Schedule::command('translations:memory-sync')->daily();
```

### Available Languages Helper

```blade
@foreach(available_languages() as $lang)
    <a href="/{{ $lang['code'] }}">
        {{ $lang['flag'] }} {{ $lang['name'] }}
    </a>
@endforeach
```

## Admin Panel

Access the admin panel at `/admin/translations` (configurable).

Features:
- **Translations page**: View all keys, inline edit, search, filter by group, add keys, AI translate
- **Translation Memory**: View stored memories, import from existing translations, purge, auto-sync config

## AI Translation Setup

1. Get an OpenAI API key (or any OpenAI-compatible API)
2. Add to your `.env`:

```env
TRANSLATIONS_AI_KEY=sk-your-api-key-here
TRANSLATIONS_AI_URL=https://api.openai.com/v1/chat/completions
TRANSLATIONS_AI_MODEL=gpt-4o-mini
```

3. Enable in config:

```php
'ai_enabled' => true,
```

## API Reference

All routes are prefixed with your configured `admin_prefix` (default: `admin/translations`).

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Translations admin page |
| POST | `/` | Create translation key |
| POST | `/sync` | Sync templates to DB |
| POST | `/ai-translate-key` | Translate single key with AI |
| POST | `/ai-translate-batch` | Translate batch (with memory) |
| POST | `/ai-translate-all` | Translate all missing |
| POST | `/add-language` | Add a language |
| POST | `/remove-language` | Remove a language |
| GET | `/memory` | Translation Memory page |
| POST | `/memory/import` | Import existing to memory |

## Customizing Views

```bash
php artisan vendor:publish --tag=translations-views
```

Views will be published to `resources/views/vendor/translations/`.

## License

MIT
