# Pre-Commit Checklist - Laravel Translations Package

## Antes de hacer commit/push, verificar:

### Código
- [ ] `helpers.php`: t(), t_raw(), available_languages() tienen try/catch
- [ ] Vistas: JS es inline (no @push), CSRF tiene fallback
- [ ] Vistas: layout y section usan config() con defaults
- [ ] Vistas: route names usan prefijo de config
- [ ] Nuevos config keys tienen valores por defecto seguros
- [ ] Controllers extienden `Illuminate\Routing\Controller`
- [ ] Nuevas rutas usan `$namePrefix` variable
- [ ] No se modificaron migraciones existentes

### Compatibilidad
- [ ] Funciona con layout standalone (sin configurar nada)
- [ ] Funciona con layout custom de la app host
- [ ] `t()` retorna fallback si tabla no existe
- [ ] Health check `/health` pasa todos los checks

### Documentación (Cerebro)
- [ ] `docs/core/architecture.json` actualizado si cambian archivos/módulos
- [ ] `docs/core/dependencies.json` actualizado si cambian dependencias
- [ ] `docs/core/data_flow.json` actualizado si cambian flujos
- [ ] `docs/management/changelog.json` tiene nueva entrada
- [ ] `docs/management/roadmap.json` tiene tarea marcada como completada
- [ ] `docs/troubleshooting/common_errors.md` actualizado si se resolvió nuevo error

### README
- [ ] Instrucciones de instalación siguen siendo válidas
- [ ] API Reference refleja rutas actuales
- [ ] Troubleshooting cubre errores nuevos
