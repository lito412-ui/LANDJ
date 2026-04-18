# Fase 05 - Despliegue y Documentación

Objetivo: dejar el CRM instalable, ejecutable y documentado para entrega.

## Estado actual
- Avance estimado: `60%`
- Ya implementado: entorno Docker funcional, documentación de instalación/ejecución, arquitectura completa, contratos de API, seguridad, validaciones y seguimiento de fases actualizados.
- Pendiente: prueba end-to-end, revisión de seguridad final y tag de versión.

## Checklist de tareas
- [x] Preparar entorno (variables de entorno, credenciales, configuración DB, healthcheck)
- [x] Asegurar arranque reproducible (Docker Compose + servicio migrate automático)
- [x] Documentar instalación/ejecución paso a paso → [`README.md`](../README.md)
- [x] Documentar arquitectura (modelo de datos, 6 tablas, FKs, ENUMs)
- [x] Documentar contratos de API (todos los endpoints implementados y pendientes) → [`CONTRATOS_API.md`](CONTRATOS_API.md)
- [x] Documentar validaciones por entidad → [`VALIDACIONES.md`](VALIDACIONES.md)
- [x] Documentar seguridad CSRF/XSS → [`SEGURIDAD_CSRF_XSS.md`](SEGURIDAD_CSRF_XSS.md)
- [x] Incluir guía de roles/permisos (qué puede hacer cada rol)
- [x] Actualizar documentación con cambios de estructura, BD, APIs, bugs resueltos y decisiones técnicas
- [ ] Ejecutar smoke test end-to-end con un caso realista (login → crear contacto → crear lead → gestionar usuarios)
- [ ] Revisar logs y manejo de errores (sin exponer secretos en producción)
- [ ] Revisar seguridad final (roles, validaciones, sanitización, headers)
- [ ] Preparar tag/versión y entregar artefactos finales

## Criterio de "Hecho"
- Cualquier persona puede instalar y usar el CRM siguiendo la documentación, y el caso de uso principal funciona de extremo a extremo.

## Documentación del repositorio
- Instalación, URLs, árbol de carpetas y comandos: [`README.md`](../README.md)
- Seguimiento global por fases: [`SEGUIMIENTO.md`](SEGUIMIENTO.md)
- Contratos de API completos: [`CONTRATOS_API.md`](CONTRATOS_API.md)
- Validaciones por entidad: [`VALIDACIONES.md`](VALIDACIONES.md)
- Seguridad CSRF/XSS: [`SEGURIDAD_CSRF_XSS.md`](SEGURIDAD_CSRF_XSS.md)
- Búsqueda y filtros: [`BUSQUEDA_FILTROS.md`](BUSQUEDA_FILTROS.md)
