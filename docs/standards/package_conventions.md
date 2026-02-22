# Convenciones del Paquete - Laravel Translations

## Namespace
- Base: `Masterweb\Translations\`
- Controllers: `Masterweb\Translations\Http\Controllers\`
- Models: `Masterweb\Translations\Models\`
- Middleware: `Masterweb\Translations\Http\Middleware\`
- Services: `Masterweb\Translations\Services\`
- Console: `Masterweb\Translations\Console\`
- Contracts: `Masterweb\Translations\Contracts\`
- View Components: `Masterweb\Translations\View\Components\`

## Nombres

| Tipo | Convención | Ejemplo |
|------|-----------|---------|
| Clases | PascalCase | `TranslationController` |
| Métodos | camelCase | `syncTexts()`, `getLanguages()` |
| Variables | camelCase | `$sourceLang`, `$targetLangs` |
| Config keys | snake_case | `admin_layout`, `route_name_prefix` |
| Route names | dot.notation con prefijo | `translations.index`, `translations.memory.store` |
| DB tables | snake_case plural | `site_translations`, `translation_memories` |
| DB columns | snake_case | `source_lang`, `key_name` |
| Migrations | Laravel timestamp convention | `2024_01_01_000001_create_site_translations_table` |
| Views | kebab-case | `translation-memory.blade.php` |
| View namespace | `translations::` | `translations::translations` |

## Patrones del Paquete

### Models
- Usar `$fillable`, nunca `$guarded = []`
- Métodos estáticos para queries frecuentes (`getValue`, `findExact`, `remember`)
- Cache en models que se consultan mucho (`SiteTranslation`, `TranslationSetting`)
- **SIEMPRE** try/catch en métodos que acceden BD y se usan temprano (helpers)

### Controllers
- Extender `Illuminate\Routing\Controller` (no `App\Http\Controllers\Controller`)
- Retornar `JsonResponse` para endpoints AJAX
- Retornar `back()->with()` para formularios normales
- Verificar `$request->ajax()` para decidir tipo de respuesta

### Vistas
- `@extends(config('translations.admin_layout', 'translations::layouts.standalone'))`
- `@section(config('translations.content_section', 'content'))`
- JS **siempre inline** (nunca `@push('scripts')`)
- CSRF: `document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'`
- Route names dinámicos: `route(config('translations.route_name_prefix', 'translations') . '.index')`

### Config
- **Todo** configurable via `config/translations.php`
- **Todo** con valor por defecto seguro
- Usar `env()` solo para secretos (API keys)
- Nuevas keys = documentar en glossary.json

### Rutas
- Todas dentro de grupo con `$prefix` y `$middleware` de config
- Route names con `$namePrefix` variable
- Health check excluye `auth` middleware

### Migraciones
- **Nunca** modificar migraciones existentes en releases publicados
- Crear nueva migración para cambios de schema
- Tablas con prefijo descriptivo (`site_translations`, no `translations`)
