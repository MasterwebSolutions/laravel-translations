# PROTOCOLO DE VERIFICACIÓN - Laravel Translations Package

## REGLA FUNDAMENTAL
**NUNCA asumir. SIEMPRE verificar.**

---

## 1. ANTES DE MODIFICAR CÓDIGO DEL PAQUETE

### Checklist Obligatorio
```
□ ¿Leí el archivo completo con read_file?
□ ¿Consulté docs/core/architecture.json para entender el módulo?
□ ¿Consulté docs/core/dependencies.json para ver qué se afecta?
□ ¿El cambio mantiene compatibilidad con apps host existentes?
□ ¿El código que accede BD tiene try/catch?
```

### Reglas específicas del paquete:
```
□ ¿helpers.php sigue teniendo try/catch en t(), t_raw(), available_languages()?
□ ¿Las vistas usan config() para layout, section y route names?
□ ¿El JS es inline (NO en @push)?
□ ¿El CSRF tiene fallback con {{ csrf_token() }}?
□ ¿Las rutas usan $namePrefix variable?
□ ¿Los nuevos config keys tienen valores por defecto seguros?
```

---

## 2. ANTES DE AGREGAR ARCHIVOS NUEVOS

### Verificar:
```
□ ¿El archivo NO existe ya? (usar find_by_name)
□ ¿Sigue el namespace Masterweb\Translations\?
□ ¿Se registra en ServiceProvider si es necesario?
□ ¿Se agrega a architecture.json y dependencies.json?
```

---

## 3. REGLAS DE ORO DEL PAQUETE

### Nunca:
- Modificar migraciones existentes (crear nuevas)
- Hard-codear route names (usar $namePrefix)
- Poner JS en @push (siempre inline)
- Asumir que la app host tiene Tailwind/CSRF meta/layout específico
- Acceder BD sin try/catch en código que se ejecuta temprano

### Siempre:
- Valores por defecto en TODA config key
- try/catch en helpers.php y Models estáticos
- Layout fallback: 'translations::layouts.standalone'
- CSRF fallback: `{{ csrf_token() }}`
- Route names con prefijo configurable

---

## 4. VERIFICACIÓN POST-CAMBIO

### Después de cada modificación:
```
□ ¿Actualicé docs/core/architecture.json?
□ ¿Actualicé docs/core/dependencies.json?
□ ¿Actualicé docs/core/data_flow.json (si cambió un flujo)?
□ ¿Agregué entrada en docs/management/changelog.json?
□ ¿Marqué tarea como completada en docs/management/roadmap.json?
```

---

## 5. CHECKLIST PRE-RELEASE

Antes de hacer push de una versión:
```
□ php artisan translations:install funciona en app limpia
□ /admin/translations/health retorna status: ok
□ t('test.key', 'fallback') retorna 'fallback' antes de migrar
□ t('test.key', 'fallback') retorna valor de BD después de migrar
□ Admin UI carga con layout standalone
□ Admin UI carga con layout custom de la app host
□ Botones Sync/Add Language/AI Translate funcionan
□ <x-translations-menu-link /> renderiza correctamente
```

---

## MANTRA
```
LEER → ENTENDER → VERIFICAR → ACTUAR → DOCUMENTAR
```
