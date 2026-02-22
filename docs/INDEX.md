# ÍNDICE MAESTRO - Laravel Translations Package

> **Quick Reference:** Consulta este archivo para saber qué leer según tu necesidad.

---

## CEREBRO (Estado del Proyecto)

| Necesito saber... | Consultar |
|-------------------|-----------|
| Qué archivos/clases existen | `docs/core/architecture.json` |
| Qué depende de qué | `docs/core/dependencies.json` |
| Cómo fluyen los datos | `docs/core/data_flow.json` |
| Qué tareas hay pendientes | `docs/management/roadmap.json` |
| En qué fase está el proyecto | `docs/management/project_state.json` |
| Qué cambios se han hecho | `docs/management/changelog.json` |
| Términos del dominio | `docs/core/glossary.json` |

---

## ESTÁNDARES (Cómo hacer las cosas)

| Necesito saber... | Consultar |
|-------------------|-----------|
| Convenciones de código del paquete | `docs/standards/package_conventions.md` |
| Reglas críticas obligatorias | `docs/standards/critical_rules.md` |
| Checklist antes de commit | `docs/standards/pre_commit_checklist.md` |

---

## VERIFICACIÓN (Evitar errores)

| Necesito... | Consultar |
|-------------|-----------|
| No alucinar / verificar antes de actuar | `docs/core/verification_protocol.md` |
| Árbol de decisiones (qué hacer) | `docs/core/decision_tree.md` |
| Resolver errores comunes de integración | `docs/troubleshooting/common_errors.md` |

---

## ESTRUCTURA DEL PAQUETE

```
laravel-translations/
├── config/
│   └── translations.php          # Config publicable
├── database/
│   └── migrations/               # 4 migraciones auto-cargadas
├── resources/
│   └── views/
│       ├── layouts/standalone.blade.php   # Layout propio (fallback)
│       ├── components/menu-link.blade.php # Componente de navegación
│       ├── translations.blade.php         # Admin UI principal
│       └── translation-memory.blade.php   # UI de Translation Memory
├── routes/
│   └── web.php                   # Rutas admin (prefijo configurable)
├── src/
│   ├── Console/
│   │   ├── InstallCommand.php    # php artisan translations:install
│   │   └── MemorySyncCommand.php # php artisan translations:memory-sync
│   ├── Contracts/
│   │   └── TranslationScanner.php # Interface para scanners custom
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── TranslationController.php       # CRUD + AI + idiomas
│   │   │   └── TranslationMemoryController.php # Memory CRUD + import
│   │   └── Middleware/
│   │       └── SetLocale.php     # Detección de idioma
│   ├── Models/
│   │   ├── SiteTranslation.php   # Modelo principal de traducciones
│   │   ├── TranslationMemory.php # Memoria de traducción
│   │   └── TranslationSetting.php # Settings key-value en BD
│   ├── Services/
│   │   └── AiTranslator.php      # Cliente OpenAI para traducción
│   ├── View/
│   │   └── Components/
│   │       └── MenuLink.php      # Componente Blade para menú
│   ├── TranslationManager.php    # Lógica central (idiomas, sync, cobertura)
│   ├── TranslationsServiceProvider.php # Registro del paquete en Laravel
│   └── helpers.php               # Funciones globales: t(), t_raw(), available_languages()
├── docs/                         # CEREBRO - Documentación viva del proyecto
├── base/                         # Templates genéricos del cerebro (no usar directamente)
├── composer.json
└── README.md
```

---

## INICIO RÁPIDO

### Para Cascade (IA):
1. **SIEMPRE** leer este INDEX primero
2. Consultar `architecture.json` antes de modificar código
3. Consultar `decision_tree.md` para saber cómo actuar
4. Seguir `verification_protocol.md` antes de cada cambio
5. Actualizar cerebro después de cada cambio

### Para Humanos:
1. Revisar `roadmap.json` para ver tareas pendientes
2. Revisar `changelog.json` para ver historial
3. Usar `common_errors.md` para troubleshooting de integración
