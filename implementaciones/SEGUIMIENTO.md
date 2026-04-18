# Seguimiento - CRM

Usa este archivo como tablero. Marca cada tarea como `[x]` cuando esté completada.

## Resumen de avance (global)
- Peso total del proyecto: `100%`
- Fórmula: `avance_global = suma(avance_fase * peso_fase)`
- **Avance estimado actual: `65%`**

## Pesos por fase
- Fase 01 - Análisis y Planificación: `15%`
- Fase 02 - Diseño y Arquitectura: `20%`
- Fase 03 - Implementación y Desarrollo: `40%`
- Fase 04 - Pruebas y Validación: `15%`
- Fase 05 - Despliegue y Documentación: `10%`

## Fase 01 - Análisis y Planificación (Peso: 15% | Avance: 100%)
- [x] Definir alcance del MVP (que entra / que no entra)
- [x] Definir actores y roles (admin, usuario)
- [x] Escribir historias de usuario (epics) con criterios de aceptación → [`MVP_HISTORIAS_Y_CRITERIOS.md`](MVP_HISTORIAS_Y_CRITERIOS.md)
- [x] Definir requisitos no funcionales (seguridad, rendimiento, auditoría)
- [x] Definir entidades principales del CRM (alto nivel)
- [x] Plan de entregables (documentos, prototipos y versiones)

## Fase 02 - Diseño y Arquitectura (Peso: 20% | Avance: 60%)
- [x] Modelar datos del MVP (tablas: `usuarios`, `contactos`, `leads`, `oportunidades`, `actividades`)
- [x] Definir relaciones y reglas de integridad (FKs, ON DELETE, unicidad)
- [x] Definir esquema para auditoría (tabla `auditoria`, helper PHP, endpoint GET admin-only)
- [x] Diseñar flujo de autenticación (sesión PHP) y cierre de sesión
- [x] Diseñar RBAC (permisos por rol `usuario` / `administrador`)
- [x] Diseñar validaciones de entrada (tipo, formato, longitudes) y manejo de errores → `implementaciones/VALIDACIONES.md`
- [x] Definir seguridad anti-CSRF/anti-XSS (encoding, tokens, cabeceras) → `implementaciones/SEGURIDAD_CSRF_XSS.md`
- [x] Prototipar pantallas principales (login, dashboard, listados, formularios, pipeline)
- [x] Definir mapa de navegación (de la vista principal a cada módulo)
- [x] Definir contratos de endpoints/acciones (rutas, payloads, respuestas y códigos de error) → `implementaciones/CONTRATOS_API.md`
- [x] Definir búsqueda/filtros (campos y paginación) → `implementaciones/BUSQUEDA_FILTROS.md`
- [x] Preparar estrategia de archivos/adjuntos → excluido del MVP; diseño de tabla `adjuntos` + reglas documentadas en FASE_02 para fase posterior

## Fase 03 - Implementación y Desarrollo (Peso: 40% | Avance: 38%)
- [x] Preparar estructura del proyecto (carpetas por dominio, convenciones, rutas)
- [x] Configurar entorno Docker (Nginx + PHP-FPM + MySQL + seed + phpMyAdmin + cAdvisor)
- [x] Corregir healthcheck MySQL y variable `MYSQL_ALLOW_EMPTY_PASSWORD`
- [x] Corregir bug de contraseña vacía en `conexion.php`
- [x] Crear esquema SQL completo del MVP con 5 tablas y FKs (`database/init.sql`)
- [x] Implementar seed con datos de prueba para todas las tablas
- [x] Implementar autenticación (hash Argon2id, login, logout, sesión)
- [x] Implementar control de acceso RBAC en rutas y endpoints protegidos
- [x] Reescribir monitorización con métricas reales (CPU/RAM/Disco)
- [x] Implementar vista de perfil del usuario autenticado
- [x] Modularizar panel: `cpanel.php` + partials PHP por sección (14 secciones)
- [x] Reorganizar assets por dominio (`css/site/`, `css/dashboard/`, `js/site/`, `js/dashboard/`)
- [x] Implementar CRUD base para contactos/clientes (REST API + módulo JS)
- [x] Implementar pantalla/listado de contactos con búsqueda debounced
- [x] Implementar formulario crear/editar con panel animado
- [x] Implementar eliminación con confirmación modal e integridad referencial
- [x] Implementar validaciones JS (tiempo real) + PHP (servidor): nombre/apellidos, email, teléfono español, maxlength por campo, counter de notas
- [ ] Implementar vista de detalle de contacto
- [ ] Implementar CRUD base para leads
- [ ] Implementar pantalla/listado de leads (filtros básicos)
- [ ] Implementar pantalla/formulario de lead (crear/editar)
- [ ] Implementar conversión de lead a contacto y/o oportunidad
- [ ] Implementar pipeline de oportunidades (etapas y movimiento)
- [ ] Implementar CRUD de actividades/notas ligadas a entidades
- [ ] Implementar búsqueda y filtros en listados
- [ ] Implementar paginación básica en listados
- [ ] Implementar validaciones de servidor y manejo de errores consistente

## Fase 03 - UX mínima viable
- [x] Navegación consistente (menú, estados activos, dropdown de usuario)
- [x] Feedback visual en métricas del dashboard (tiempo real)
- [x] Vista de perfil con datos reales del usuario autenticado
- [x] Estados vacíos (sin datos) y mensajes de carga en listados CRM (contactos)
- [ ] Permisos reflejados en la UI (botones/acciones según rol)

## Fase 04 - Pruebas y Validación (Peso: 15% | Avance: 0%)
- [ ] Plan de pruebas (funcionales y de regresión)
- [ ] Pruebas manuales de flujos críticos (login → CRUD → pipeline)
- [ ] Pruebas de seguridad básicas (inyección, XSS, CSRF, roles)
- [ ] Revisar accesibilidad básica (navegación por teclado, contraste)
- [ ] Cerrar brechas de usabilidad (formularios, mensajes, estados vacíos)

## Fase 05 - Despliegue y Documentación (Peso: 10% | Avance: 60%)
- [x] Preparar entorno (Docker, healthcheck, variables corregidas)
- [x] Documentar instalación/ejecución (README del proyecto)
- [x] Documentar arquitectura (modelo de datos + flujos)
- [x] Actualizar documentación con todos los cambios de estructura y esquema BD
- [ ] Ejecutar prueba final end-to-end con un caso realista
- [ ] Tag/versión para entrega

## Posibles mejoras (post-MVP)

### Mejoras de alto impacto (prioridad alta)
- [ ] Dashboard comercial con métricas (impacto: alto | esfuerzo: medio)
- [ ] Importación/exportación CSV para contactos, leads y oportunidades (impacto: alto | esfuerzo: bajo)
- [ ] Notificaciones y recordatorios de tareas (impacto: alto | esfuerzo: medio)
- [ ] Historial/auditoría visible por entidad (impacto: alto | esfuerzo: medio)
- [ ] Búsqueda global unificada (impacto: alto | esfuerzo: medio)

### Mejoras técnicas (prioridad media)
- [ ] API REST versionada para integraciones futuras (impacto: medio-alto | esfuerzo: medio-alto)
- [ ] Tests automatizados mínimos (smoke + integración) (impacto: alto | esfuerzo: medio)
- [~] Hardening de seguridad (rate limit, bloqueo por intentos, CSRF tokens ✓, CSP ✓) (impacto: alto | esfuerzo: medio)
- [ ] Logging estructurado para incidencias y soporte (impacto: medio | esfuerzo: medio)

### Mejoras de producto (prioridad media-baja)
- [ ] Módulo de etiquetas y segmentación de contactos (impacto: medio | esfuerzo: medio)
- [ ] Plantillas de email y seguimiento de envíos (impacto: medio | esfuerzo: medio)
- [ ] Kanban drag-and-drop real para pipeline (impacto: medio-alto | esfuerzo: medio)
- [ ] Informes exportables (PDF/CSV) por periodo (impacto: medio | esfuerzo: bajo)
- [ ] Multilenguaje y personalización básica de interfaz (impacto: medio-bajo | esfuerzo: medio)

## Siguiente iteración recomendada
- [ ] Implementar CRUD de contactos (base del CRM)
- [ ] Implementar CRUD de leads + conversión a oportunidad
- [ ] Endurecer login (rate limit, intentos fallidos, mensajes no reveladores)
