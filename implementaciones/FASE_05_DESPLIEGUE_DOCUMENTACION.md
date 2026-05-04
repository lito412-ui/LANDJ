# Fase 05 - Despliegue y Documentación

Objetivo: dejar el CRM instalable, ejecutable y documentado para entrega.

## Estado actual
- Avance estimado: `90%`
- Ya implementado: entorno Docker funcional, documentación completa (README, arquitectura, contratos API, validaciones, seguridad, seguimiento de fases), todos los módulos documentados incluyendo hosting (dominios, correo), backups, estadísticas, configuración (layout aside/main), dropdown de usuario expandido, drag & drop en pipeline, CSS modular en 6 ficheros con cache-busting, tema oscuro WCAG AA.
- Pendiente: prueba end-to-end, revisión de seguridad final y tag de versión.

## Checklist de tareas
- [x] Preparar entorno (variables de entorno, credenciales, configuración DB, healthcheck)
- [x] Asegurar arranque reproducible (Docker Compose + servicio `migrate` automático)
- [x] Documentar instalación/ejecución paso a paso → [`README.md`](../README.md)
- [x] Documentar arquitectura (modelo de datos, 8 tablas, FKs, ENUMs) → [`implementaciones/README.md`](README.md)
- [x] Documentar contratos de API (todos los endpoints implementados) → [`CONTRATOS_API.md`](CONTRATOS_API.md)
- [x] Documentar validaciones por entidad → [`VALIDACIONES.md`](VALIDACIONES.md)
- [x] Documentar seguridad CSRF/XSS → [`SEGURIDAD_CSRF_XSS.md`](SEGURIDAD_CSRF_XSS.md)
- [x] Incluir guía de roles/permisos (qué puede hacer cada rol)
- [x] Actualizar documentación con todos los cambios: hosting (dominios/correo), backups, estadísticas, actividad reciente, configuración, tema oscuro WCAG AA, 13 módulos JS
- [x] Actualizar documentación con mejoras UX: dropdown expandido, configuración aside/main, drag & drop pipeline, CSS modular 6 ficheros, cache-busting JS/CSS
- [ ] Ejecutar smoke test end-to-end con un caso realista (login → crear contacto → crear lead → gestionar usuarios → dominio → backup)
- [ ] Revisar logs y manejo de errores (sin exponer secretos en producción)
- [ ] Revisar seguridad final (roles, validaciones, sanitización, headers)
- [ ] Preparar tag/versión y entregar artefactos finales

## Criterio de "Hecho"
Cualquier persona puede instalar y usar el CRM siguiendo la documentación, y el caso de uso principal funciona de extremo a extremo.

## Documentación del repositorio

| Documento | Descripción |
|-----------|-------------|
| [`README.md`](../README.md) | Instalación, URLs, árbol de carpetas y comandos |
| [`SEGUIMIENTO.md`](SEGUIMIENTO.md) | Seguimiento global por fases y backlog |
| [`implementaciones/README.md`](README.md) | Arquitectura, módulos JS, endpoints, estructura de secciones |
| [`CONTRATOS_API.md`](CONTRATOS_API.md) | Contratos REST de todos los endpoints |
| [`VALIDACIONES.md`](VALIDACIONES.md) | Validaciones por entidad (JS + PHP) |
| [`SEGURIDAD_CSRF_XSS.md`](SEGURIDAD_CSRF_XSS.md) | Cabeceras de seguridad y mecanismo CSRF |
| [`BUSQUEDA_FILTROS.md`](BUSQUEDA_FILTROS.md) | Diseño de búsqueda, filtros y paginación |
