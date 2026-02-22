# Reglas Críticas - Laravel Translations Package

## REGLA 1: Protección contra 500
**Todo código que accede a BD y se ejecuta temprano DEBE tener try/catch.**

Archivos afectados:
- `src/helpers.php` — funciones `t()`, `t_raw()`, `available_languages()`
- `src/Models/SiteTranslation.php` — `getAllForLang()`, `clearCache()`
- `src/Models/TranslationSetting.php` — `loadAll()`

**Si se rompe esta regla:** TODA la app host dará error 500.

---

## REGLA 2: JS siempre inline
**El JavaScript de las vistas NUNCA debe estar en `@push('scripts')`.**

Debe estar como `<script>` directo en la sección de contenido.

**Si se rompe esta regla:** Los botones no funcionarán en layouts sin `@stack('scripts')`.

---

## REGLA 3: CSRF con fallback
**El CSRF token SIEMPRE debe tener fallback:**
```javascript
const CSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
             || '{{ csrf_token() }}';
```

**Si se rompe esta regla:** Error 419 en todos los AJAX en layouts sin meta tag CSRF.

---

## REGLA 4: Config con valores por defecto
**Toda llamada a `config()` DEBE tener segundo parámetro (default).**
```php
config('translations.admin_layout', 'translations::layouts.standalone')
config('translations.route_name_prefix', 'translations')
```

**Si se rompe esta regla:** Error si la app host no publicó la config.

---

## REGLA 5: Route names dinámicos
**Los nombres de ruta NUNCA se hardcodean. Siempre usar el prefijo de config.**
```php
// En routes/web.php
Route::get('/', [...])->name("{$namePrefix}.index");

// En vistas
route(config('translations.route_name_prefix', 'translations') . '.index')
```

**Si se rompe esta regla:** Conflictos de nombres con rutas de la app host.

---

## REGLA 6: No modificar migraciones publicadas
**Las migraciones existentes NO se modifican después de un release.**

Si se necesita cambiar schema → crear nueva migración.

**Si se rompe esta regla:** Apps host que ya migraron tendrán schema inconsistente.

---

## REGLA 7: Compatibilidad con Laravel 10/11/12
**Verificar que APIs de Laravel usadas existen en las 3 versiones.**

Dependencias en composer.json: `"illuminate/*": "^10.0|^11.0|^12.0"`

**Si se rompe esta regla:** El paquete no instalará en algunas versiones.

---

## REGLA 8: Actualizar cerebro después de cada cambio
**Después de modificar código, actualizar:**
1. `docs/core/architecture.json` — si cambian archivos/módulos
2. `docs/core/dependencies.json` — si cambian dependencias
3. `docs/core/data_flow.json` — si cambian flujos
4. `docs/management/changelog.json` — SIEMPRE
5. `docs/management/roadmap.json` — si se completa/agrega tarea
