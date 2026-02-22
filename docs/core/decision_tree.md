# ÁRBOL DE DECISIONES - Laravel Translations Package

> **Propósito:** Guía para saber exactamente qué hacer ante cualquier petición relacionada con este paquete.

---

## 1. CLASIFICACIÓN INICIAL

```
¿Qué tipo de petición es?
│
├─► MODIFICAR FUNCIONALIDAD DEL PAQUETE
│   └─► Ir a: Sección 2
│
├─► CORREGIR BUG DE INTEGRACIÓN
│   └─► Ir a: Sección 3
│
├─► AGREGAR NUEVA FEATURE
│   └─► Ir a: Sección 4
│
├─► PROBLEMA EN APP HOST
│   └─► Ir a: Sección 5
│
└─► NO ESTOY SEGURO
    └─► PREGUNTAR al usuario antes de actuar
```

---

## 2. MODIFICAR FUNCIONALIDAD EXISTENTE

```
MODIFICAR FUNCIONALIDAD
│
├─► 1. ANTES DE CODIFICAR
│   ├─► Leer docs/core/architecture.json
│   │   └─► ¿Qué módulo se afecta?
│   ├─► Leer docs/core/dependencies.json
│   │   └─► ¿Qué otros archivos dependen de esto?
│   └─► ¿Cambia la API pública? (config keys, helpers, route names)
│       ├─► SÍ → Es BREAKING CHANGE. Documentar en changelog.
│       └─► NO → Proceder con cuidado
│
├─► 2. VERIFICAR IMPACTO
│   ├─► ¿Toca helpers.php (t, t_raw, available_languages)?
│   │   └─► CRITICAL: Afecta TODA la app host
│   ├─► ¿Toca config keys?
│   │   └─► CRITICAL: Apps host que ya publicaron config se rompen
│   ├─► ¿Toca migraciones?
│   │   └─► HIGH: No modificar migraciones existentes. Crear nuevas.
│   └─► ¿Toca vistas?
│       └─► MEDIUM: Verificar JS inline funciona sin @stack
│
├─► 3. IMPLEMENTAR
│   ├─► Mantener try/catch en código que accede BD
│   ├─► JS siempre inline (no @push)
│   ├─► Route names siempre con $namePrefix variable
│   └─► Layout/section siempre con config()
│
└─► 4. DESPUÉS
    ├─► Actualizar docs/core/architecture.json
    ├─► Actualizar docs/core/dependencies.json
    ├─► Agregar entrada en docs/management/changelog.json
    └─► Ejecutar pre_commit_checklist.md
```

---

## 3. CORREGIR BUG DE INTEGRACIÓN

```
BUG DE INTEGRACIÓN (app host reporta error)
│
├─► 1. DIAGNOSTICAR
│   ├─► ¿Es error 500?
│   │   ├─► ¿En páginas que usan t()? → Verificar tabla site_translations existe
│   │   ├─► ¿En /admin/translations? → Verificar layout configurado existe
│   │   └─► ¿En AJAX? → Verificar CSRF token disponible
│   │
│   ├─► ¿Botones no funcionan?
│   │   ├─► ¿JS se carga? → Verificar que está inline, no en @push
│   │   └─► ¿CSRF OK? → Verificar meta tag o fallback {{ csrf_token() }}
│   │
│   ├─► ¿No aparece en menús?
│   │   └─► ¿Usa <x-translations-menu-link />? → Verificar componente registrado
│   │
│   └─► ¿Route not found?
│       └─► Verificar route_name_prefix en config coincide con route() calls
│
├─► 2. VERIFICAR HEALTH CHECK
│   └─► GET /admin/translations/health → ver qué check falla
│
├─► 3. FIX
│   ├─► Aplicar fix MÍNIMO
│   ├─► Verificar que no rompe apps host existentes
│   └─► Agregar try/catch si el fix involucra acceso BD
│
└─► 4. DOCUMENTAR
    ├─► Agregar a docs/troubleshooting/common_errors.md
    └─► Agregar entrada en changelog.json
```

---

## 4. AGREGAR NUEVA FEATURE

```
NUEVA FEATURE
│
├─► 1. PLANIFICAR
│   ├─► ¿Existe en roadmap.json?
│   │   ├─► SÍ → Usar esa tarea
│   │   └─► NO → Agregar tarea primero
│   ├─► ¿Requiere nueva tabla?
│   │   └─► Crear migración NUEVA (nunca modificar existentes)
│   ├─► ¿Requiere nueva config key?
│   │   └─► Agregar a config/translations.php con valor por defecto
│   └─► ¿Requiere nueva ruta?
│       └─► Agregar en routes/web.php con $namePrefix
│
├─► 2. IMPLEMENTAR
│   ├─► Seguir patrones existentes del paquete
│   ├─► Controllers extienden Illuminate\Routing\Controller
│   ├─► Models usan fillable, no guarded
│   ├─► Vistas usan config() para layout/section/routes
│   └─► JS siempre inline con CSRF fallback
│
├─► 3. PRINCIPIOS DE PAQUETE LARAVEL
│   ├─► Todo configurable via config/translations.php
│   ├─► Nunca hard-codear paths de la app host
│   ├─► Usar namespaced views: 'translations::vista'
│   ├─► Usar route names con prefijo configurable
│   └─► Todo debe funcionar sin publicar nada (zero-config)
│
└─► 4. ACTUALIZAR CEREBRO
    ├─► architecture.json (nuevos archivos/módulos)
    ├─► dependencies.json (nuevas dependencias)
    ├─► data_flow.json (si hay nuevo flujo)
    ├─► roadmap.json (marcar completada)
    └─► changelog.json (nueva entrada)
```

---

## 5. PROBLEMA EN APP HOST

```
PROBLEMA EN APP HOST (no es bug del paquete)
│
├─► ¿El layout no tiene @yield('content')?
│   └─► Recomendar: cambiar content_section en config
│       o usar layout standalone
│
├─► ¿Conflicto de route names?
│   └─► Recomendar: cambiar route_name_prefix en config
│
├─► ¿No tiene TailwindCSS?
│   └─► Recomendar: usar layout standalone que trae Tailwind CDN
│       o publicar vistas y customizar
│
├─► ¿Middleware auth custom?
│   └─► Recomendar: ajustar admin_middleware en config
│
└─► ¿No sabe cómo empezar?
    └─► Recomendar: php artisan translations:install
```

---

## 6. CUÁNDO PREGUNTAR AL USUARIO

**SIEMPRE preguntar si:**
- El cambio afecta config keys existentes (breaking change)
- Hay múltiples formas de resolver el bug
- El fix podría romper apps host que ya usan el paquete
- Se necesita modificar migraciones existentes
- No está claro si es bug del paquete o de la app host

**NUNCA asumir:**
- Qué layout usa la app host
- Qué versión de Laravel usa
- Si tienen TailwindCSS
- Si el usuario ejecutó las migraciones
