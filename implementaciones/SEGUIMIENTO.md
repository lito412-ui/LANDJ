# Seguimiento - CRM

Usa este archivo como tablero. Marca cada tarea como `[x]` cuando esté completada.

## Resumen de avance (global)
- Peso total del proyecto: `100%`
- Fórmula: `avance_global = suma(avance_fase * peso_fase)`
- **Avance estimado actual: `81%`**
  - Fase 01: 100% × 15% = 15
  - Fase 02: 100% × 20% = 20
  - Fase 03: 100% × 40% = 40
  - Fase 04:   0% × 15% = 0
  - Fase 05:  60% × 10% = 6

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

## Fase 02 - Diseño y Arquitectura (Peso: 20% | Avance: 100%)
- [x] Modelar datos del MVP (tablas: `usuarios`, `contactos`, `leads`, `oportunidades`, `actividades`, `auditoria`)
- [x] Definir relaciones y reglas de integridad (FKs, ON DELETE, unicidad)
- [x] Definir esquema para auditoría (tabla `auditoria`, helper PHP tolerante a fallos, endpoint GET admin-only)
- [x] Diseñar flujo de autenticación (sesión PHP) y cierre de sesión
- [x] Diseñar RBAC (permisos por rol `usuario` / `administrador`)
- [x] Diseñar validaciones de entrada (tipo, formato, longitudes) y manejo de errores → [`VALIDACIONES.md`](VALIDACIONES.md)
- [x] Definir seguridad anti-CSRF/anti-XSS (encoding, tokens, cabeceras) → [`SEGURIDAD_CSRF_XSS.md`](SEGURIDAD_CSRF_XSS.md)
- [x] Prototipar pantallas principales (login, dashboard, listados, formularios, pipeline)
- [x] Definir mapa de navegación (de la vista principal a cada módulo)
- [x] Definir contratos de endpoints/acciones (rutas, payloads, respuestas y códigos de error) → [`CONTRATOS_API.md`](CONTRATOS_API.md)
- [x] Definir búsqueda/filtros (campos y paginación) → [`BUSQUEDA_FILTROS.md`](BUSQUEDA_FILTROS.md)
- [x] Preparar estrategia de archivos/adjuntos → excluido del MVP; diseño de tabla `adjuntos` + reglas documentadas en FASE_02 para fase posterior

## Fase 03 - Implementación y Desarrollo (Peso: 40% | Avance: 100%)

### Core (completado)
- [x] Preparar estructura del proyecto (carpetas por dominio, convenciones, rutas)
- [x] Configurar entorno Docker (Nginx + PHP-FPM + MySQL + migrate + phpMyAdmin + cAdvisor)
- [x] Corregir healthcheck MySQL y variable `MYSQL_ALLOW_EMPTY_PASSWORD`
- [x] Corregir bug de contraseña vacía en `conexion.php`
- [x] Crear esquema SQL completo del MVP con 6 tablas y FKs (`database/init.sql`)
- [x] Implementar sistema de migraciones idempotente (`database/migrate.php`)
- [x] Seed con datos de prueba vía migraciones (usuarios con Argon2id + datos CRM)
- [x] Implementar seguridad: CSRF tokens + cabeceras HTTP (CSP, X-Frame-Options) en `seguridad.php`
- [x] `fetchSeguro()` cliente: inyecta `X-CSRF-Token` automáticamente
- [x] `registrarAuditoria()` tolerante a fallos (captura su propia PDOException)
- [x] Añadir `try_files $uri =404` al bloque PHP de Nginx
- [x] Implementar autenticación (hash Argon2id, login, logout, sesión)
- [x] Implementar control de acceso RBAC en rutas y endpoints protegidos
- [x] Reescribir monitorización con métricas reales (CPU/RAM/Disco)
- [x] Implementar vista de perfil del usuario autenticado
- [x] Modularizar panel: `cpanel.php` + partials PHP por sección
- [x] Reorganizar assets por dominio (`css/site/`, `css/dashboard/`, `js/site/`, `js/dashboard/`)
- [x] JS del panel dividido en 6 módulos: `cpanel-core.js`, `cpanel-actividades.js`, `cpanel-contactos.js`, `cpanel-leads.js`, `cpanel-usuarios.js`, `cpanel-oportunidades.js`

### CRM (completado)
- [x] CRUD completo de contactos (API REST + módulo JS + validaciones JS+PHP + detalle lateral)
- [x] Búsqueda debounced y estado vacío en listado de contactos
- [x] Toast notifications y confirm modal reutilizables (variantes `danger`/`success`/`info`)
- [x] CRUD completo de leads (API REST + módulo JS + badges estado + búsqueda + filtro + detalle)
- [x] CRUD completo de gestión de usuarios — admin-only (API + JS + HTML + badges rol + autoprotección)
- [x] Conversión de lead a contacto (transacción atómica, detección email duplicado, auditoría en ambas entidades, botón deshabilitado si ya convertido)
- [x] Migración idempotente `002_leads_contacto_id.php` para columna `contacto_id` en tabla `leads`
- [x] Pipeline kanban de oportunidades: 5 etapas, cards, cambio de etapa con confirm, búsqueda + filtro
- [x] CRUD de actividades/notas ligadas a contactos, leads y oportunidades (`ActividadesWidget` compartido)
- [x] Búsqueda y filtros avanzados: panel colapsable con estado por módulo, badge de filtros activos, ordenación asc/desc y limpiar

### CRM (completado)
- [x] Paginación básica en listados (contactos y leads: server-side LIMIT/OFFSET, renderPaginacion compartido en core.js)
- [x] Permisos reflejados en la UI (secciones admin-only ocultas, badge de rol, guard en navegación JS)
- [x] Manejo de errores unificado (manejarApiError en core, monitorizacion.php formato estándar, get_user.php 401)
- [x] Sección Auditoría (UI completa: tabla con diff expandible, filtros por entidad y acción, paginación offset, módulo `cpanel-auditoria.js`)

## Fase 03 - UX mínima viable
- [x] Navegación consistente (menú, estados activos, dropdown de usuario)
- [x] Feedback visual en métricas del dashboard (tiempo real)
- [x] Vista de perfil con datos reales del usuario autenticado
- [x] Estados vacíos (`crm-empty`) y carga (`crm-loading`) en todos los listados CRM
- [x] Filtros avanzados con panel colapsable y badge de filtros activos
- [x] Permisos reflejados en la UI (secciones admin-only ocultas, badge de rol, guard en navegación)

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
- [x] Actualizar documentación con todos los cambios (estructura, BD, APIs, seguridad, bugs, paginación, errores, auditoría UI, permisos)
- [ ] Ejecutar prueba final end-to-end con un caso realista
- [ ] Tag/versión para entrega

## Posibles mejoras (post-MVP)

### Mejoras de alto impacto (prioridad alta)
- [ ] Dashboard comercial con métricas (impacto: alto | esfuerzo: medio)
- [ ] Importación/exportación CSV para contactos, leads y oportunidades (impacto: alto | esfuerzo: bajo)
- [ ] Notificaciones y recordatorios de tareas (impacto: alto | esfuerzo: medio)
- [ ] Historial/auditoría visible por entidad en el panel (impacto: alto | esfuerzo: medio)
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
- [x] Paginación básica (mayor valor de usabilidad pendiente con listados grandes)
- [x] Añadir permisos en la UI (mostrar/ocultar botones según rol)
- [ ] Endurecer login (rate limit, intentos fallidos, mensajes no reveladores)
- [ ] Pruebas manuales de flujos críticos (Fase 04)
