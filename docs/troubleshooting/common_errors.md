# Errores Comunes de Integración - Laravel Translations

> Consultar este archivo ANTES de intentar resolver un error desde cero.

---

## ERR-001: Error 500 en páginas que usan t()

**Síntoma:** La app host da 500 en cualquier página que use `{{ t('key', 'fallback') }}`

**Causa:** La tabla `site_translations` no existe (migraciones no ejecutadas)

**Solución:**
```bash
php artisan migrate
# O verificar con:
php artisan translations:install
```

**Prevención:** `t()` tiene try/catch que retorna fallback. Si sigue dando 500, verificar que `src/helpers.php` tiene el try/catch.

---

## ERR-002: Botones del admin no hacen nada

**Síntoma:** Sync Templates, AI Translate, Add Language, etc. no responden al click

**Causa posible 1:** JS no se carga porque el layout usa `@push`/`@stack` y no `<script>` inline

**Solución:** Las vistas ya usan JS inline. Si se publicaron vistas anteriores, republicar:
```bash
php artisan vendor:publish --tag=translations-views --force
```

**Causa posible 2:** CSRF token no disponible → error 419 silencioso

**Solución:** Verificar que el layout tiene `<meta name="csrf-token" content="{{ csrf_token() }}">` en `<head>`. Si no, las vistas usan `{{ csrf_token() }}` como fallback automático.

---

## ERR-003: Admin panel da 500 al acceder

**Síntoma:** GET /admin/translations retorna error 500

**Causa posible 1:** Layout configurado no existe

**Diagnóstico:**
```bash
curl http://tu-app.test/admin/translations/health
# Verificar check "layout_exists"
```

**Solución:** Cambiar `admin_layout` en config a `'translations::layouts.standalone'` o al layout correcto de la app.

**Causa posible 2:** Layout existe pero no tiene `@yield('content')`

**Solución:** Ajustar `content_section` en config al nombre correcto del `@yield()` del layout.

---

## ERR-004: No aparece en el menú de la aplicación

**Síntoma:** El paquete funciona pero no hay link en la navegación del host

**Causa:** El paquete no se auto-inyecta en menús. Requiere acción del desarrollador.

**Solución:** Agregar en el template de navegación:
```blade
<x-translations-menu-link />
{{-- O con label custom: --}}
<x-translations-menu-link label="Traducciones" />
{{-- O un link simple: --}}
<a href="{{ route('translations.index') }}">Traducciones</a>
```

---

## ERR-005: Conflicto de nombres de ruta

**Síntoma:** `Route [translations.index] not defined` o conflicto con rutas de la app host

**Causa:** La app host tiene rutas con el mismo nombre

**Solución:** Cambiar `route_name_prefix` en `config/translations.php`:
```php
'route_name_prefix' => 'mw_translations', // En vez de 'translations'
```

---

## ERR-006: Traducciones no se actualizan (cache)

**Síntoma:** Se edita una traducción en admin pero `t()` sigue retornando el valor viejo

**Causa:** Cache no se limpió correctamente

**Solución:**
```bash
php artisan cache:clear
```

**O programáticamente:**
```php
\Masterweb\Translations\Models\SiteTranslation::clearCache();
```

---

## ERR-007: AI Translation falla silenciosamente

**Síntoma:** Click en "AI Translate" pero no traduce nada

**Diagnóstico:**
1. Verificar `ai_enabled => true` en config
2. Verificar `TRANSLATIONS_AI_KEY` en `.env`
3. Revisar logs: `storage/logs/laravel.log` (buscar "AI translation")

**Causa común:** API key inválida o modelo no disponible

**Solución:** Verificar en health check:
```bash
curl http://tu-app.test/admin/translations/health
# Verificar checks: ai_enabled, ai_key_set
```

---

## ERR-008: Middleware SetLocale causa redirect loop

**Síntoma:** La app redirige infinitamente al usar el middleware

**Causa:** El middleware redirige a `/{defaultLang}` pero la ruta no acepta el prefijo

**Solución:** Asegurarse de que las rutas están dentro del grupo con `{locale?}`:
```php
Route::middleware([\Masterweb\Translations\Http\Middleware\SetLocale::class])
    ->prefix('{locale?}')
    ->group(function () {
        // Rutas aquí
    });
```

---

## ERR-009: Error "Class not found" al instalar

**Síntoma:** `Class 'Masterweb\Translations\TranslationsServiceProvider' not found`

**Causa:** Composer autoload no se regeneró

**Solución:**
```bash
composer dump-autoload
```

---

## ERR-010: Admin UI sin estilos (se ve feo)

**Síntoma:** La página carga pero sin CSS/estilos

**Causa posible 1:** Usando layout standalone pero sin acceso a CDN

**Solución:** El standalone layout usa `https://cdn.tailwindcss.com`. Verificar acceso a internet.

**Causa posible 2:** Usando layout custom que no tiene TailwindCSS

**Solución:** El admin UI usa clases Tailwind. Opciones:
1. Usar layout standalone (incluye Tailwind CDN)
2. Agregar Tailwind al layout custom
3. Publicar vistas y customizar CSS
