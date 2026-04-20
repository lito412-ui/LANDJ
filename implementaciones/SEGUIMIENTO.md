# Seguimiento - CRM

Usa este archivo como tablero. Marca cada tarea como `[x]` cuando esté completada.

## Resumen de avance (global)
- Peso total del proyecto: `100%`
- Fórmula: `avance_global = suma(avance_fase * peso_fase)`
- **Avance estimado actual: `89%`**
  - Fase 01: 100% × 15% = 15
  - Fase 02: 100% × 20% = 20
  - Fase 03: 100% × 40% = 40
  - Fase 04:   0% × 15% =  0
  - Fase 05:  80% × 10% =  8 (↑ desde 60%)

## Pesos por fase
- Fase 01 - Análisis y Planificación: `15%`
- Fase 02 - Diseño y Arquitectura: `20%`
- Fase 03 - Implementación y Desarrollo: `40%`
- Fase 04 - Pruebas y Validación: `15%`
- Fase 05 - Despliegue y Documentación: `10%`

---

## Fase 01 - Análisis y Planificación (Peso: 15% | Avance: 100%)
- [x] Definir alcance del MVP (que entra / que no entra)
- [x] Definir actores y roles (admin, usuario)
- [x] Escribir historias de usuario (epics) con criterios de aceptación → [`MVP_HISTORIAS_Y_CRITERIOS.md`](MVP_HISTORIAS_Y_CRITERIOS.md)
- [x] Definir requisitos no funcionales (seguridad, rendimiento, auditoría)
- [x] Definir entidades principales del CRM (alto nivel)
- [x] Plan de entregables (documentos, prototipos y versiones)

---

## Fase 02 - Diseño y Arquitectura (Peso: 20% | Avance: 100%)
- [x] Modelar datos del MVP (tablas: `usuarios`, `contactos`, `leads`, `oportunidades`, `actividades`, `auditoria`)
- [x] Ampliar modelo con tablas de hosting: `dominios`, `cuentas_correo`
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
- [x] Preparar estrategia de archivos/adjuntos → excluido del MVP; diseño de tabla `adjuntos` documentado en FASE_02

---

## Fase 03 - Implementación y Desarrollo (Peso: 40% | Avance: 100%)

### Core e infraestructura (completado)
- [x] Preparar estructura del proyecto (carpetas por dominio, convenciones, rutas)
- [x] Configurar entorno Docker (Nginx + PHP-FPM + MySQL + migrate + phpMyAdmin + cAdvisor)
- [x] Crear esquema SQL completo del MVP con 6 tablas y FKs (`database/init.sql`)
- [x] Implementar sistema de migraciones idempotente (`database/migrate.php`)
- [x] Seed con datos de prueba vía migraciones (usuarios Argon2id + datos CRM)
- [x] Implementar seguridad: CSRF tokens + cabeceras HTTP (CSP, X-Frame-Options) en `seguridad.php`
- [x] `fetchSeguro()` cliente: inyecta `X-CSRF-Token` automáticamente
- [x] `registrarAuditoria()` tolerante a fallos (captura su propia PDOException)
- [x] Implementar autenticación (hash Argon2id, login, logout, sesión)
- [x] Implementar control de acceso RBAC en rutas y endpoints protegidos
- [x] Monitorización con métricas reales (CPU/RAM/Disco), actualización cada 3 s
- [x] Vista de perfil del usuario autenticado
- [x] Panel modularizado: `cpanel.php` + partials PHP por sección + assets por dominio

### CRM — módulos principales (completado)
- [x] CRUD completo de contactos (API REST + módulo JS + validaciones JS+PHP + detalle lateral)
- [x] CRUD completo de leads (búsqueda, filtros, badges estado, conversión lead→contacto atómica)
- [x] CRUD completo de gestión de usuarios — admin-only (badges rol, autoprotección, protección último admin)
- [x] Pipeline kanban de oportunidades: 5 etapas, cambio de etapa con confirm, filtros avanzados
- [x] CRUD de actividades/notas ligadas a contactos, leads y oportunidades (`ActividadesWidget` compartido)
- [x] Búsqueda y filtros avanzados en todos los módulos (panel colapsable, badge activos, ordenación asc/desc)
- [x] Paginación server-side con `renderPaginacion` compartido en todos los listados CRM

### cPanel — módulos hosting (completado)
- [x] CRUD completo de dominios (`dominios.php` API + `cpanel-dominios.js` + `domains.php` HTML)
  - Tipo: principal/subdominio/addon/parked; estado: activo/pendiente/suspendido; IP; SSL toggle
  - Filtros avanzados, paginación, validación de dominio y formato IP
  - Corregido: `registrarAuditoria` llamada sin `$userId`; `` `ssl` `` backtick-escapado (reservado MySQL)
  - CSP compliant: event delegation con `data-edit`/`data-del` (sin onclick inline)
- [x] CRUD completo de cuentas de correo (`cuentas_correo.php` API + `cpanel-email.js` + `email.php` HTML)
  - Cuota en MB (0 = ilimitada); formato ≥ 1024 MB → GB; dominio extraído automáticamente del email
  - Filtros avanzados, paginación, validación email

### Panel — secciones de sistema (completado)
- [x] Sección Auditoría: tabla con diff expandible, filtros por entidad y acción, paginación offset
- [x] Sección Bases de Datos: estadísticas MySQL en tiempo real con `SHOW TABLE STATUS`, botón phpMyAdmin
- [x] Sección Copias de Seguridad: creación, descarga y eliminación de backups `.sql` (`cpanel-backups.js`)
- [x] Sección Estadísticas: métricas CRM (leads por estado, oportunidades por etapa, valor potencial)
- [x] Configuración de cuenta: editar nombre/email, cambiar contraseña con verificación, selector de tema

### UX y UI (completado)
- [x] Tema claro/oscuro: toggle en cabecera, `localStorage`, WCAG AA en ambos modos
  - Contrast fixes: `.nav-section-title`, `.crm-empty`, `.chart-label`, `.quick-action-btn span`
  - Status badges con clases CSS semánticas (sin `style=""` inline) para compatibilidad dark mode
  - Overrides por sección: pipeline cards, backup stats, db summary, badges usuario/auditoría
- [x] Actividad reciente en dashboard: últimos 10 eventos de auditoría con tiempo relativo
- [x] Acciones rápidas del dashboard: backup, nueva cuenta de correo
- [x] Navegación programática `navegarA(sectionId)` + `initQuickActions()`
- [x] Permisos en UI: secciones admin-only ocultas, badge de rol en header, guard en JS
- [x] Estados vacíos (`crm-empty`) y carga (`crm-loading`) en todos los listados
- [x] Toast notifications (success/error/info) y confirm modal reutilizables con variantes

---

## Fase 04 - Pruebas y Validación (Peso: 15% | Avance: 0%)
- [ ] Plan de pruebas (funcionales y de regresión)
- [ ] Pruebas manuales de flujos críticos (login → CRUD → pipeline)
- [ ] Pruebas de seguridad básicas (inyección, XSS, CSRF, roles)
- [ ] Revisar accesibilidad básica (navegación por teclado, contraste WCAG)
- [ ] Cerrar brechas de usabilidad (formularios, mensajes, estados vacíos)

---

## Fase 05 - Despliegue y Documentación (Peso: 10% | Avance: 80%)
- [x] Preparar entorno (Docker, healthcheck, variables corregidas)
- [x] Documentar instalación/ejecución (README del proyecto)
- [x] Documentar arquitectura (modelo de datos + flujos)
- [x] Actualizar documentación con todos los cambios (Fase 03 completa + módulos hosting + UX dark mode)
- [ ] Ejecutar prueba final end-to-end con un caso realista
- [ ] Tag/versión para entrega

---

## Posibles mejoras (post-MVP)

### Mejoras de alto impacto (prioridad alta)
- [ ] Importación/exportación CSV para contactos, leads y oportunidades (impacto: alto | esfuerzo: bajo)
- [ ] Notificaciones y recordatorios de tareas (impacto: alto | esfuerzo: medio)
- [ ] Búsqueda global unificada (impacto: alto | esfuerzo: medio)
- [ ] Kanban drag-and-drop real para pipeline (impacto: medio-alto | esfuerzo: medio)

### Mejoras técnicas (prioridad media)
- [ ] Tests automatizados mínimos (smoke + integración) (impacto: alto | esfuerzo: medio)
- [~] Hardening de seguridad (rate limit, bloqueo por intentos, CSRF ✓, CSP ✓) (impacto: alto | esfuerzo: medio)
- [ ] API REST versionada para integraciones futuras (impacto: medio-alto | esfuerzo: medio-alto)
- [ ] Logging estructurado para incidencias (impacto: medio | esfuerzo: medio)

### Mejoras de producto (prioridad media-baja)
- [ ] Módulo de etiquetas y segmentación de contactos (impacto: medio | esfuerzo: medio)
- [ ] Informes exportables (PDF/CSV) por periodo (impacto: medio | esfuerzo: bajo)
- [ ] Multilenguaje básico (impacto: medio-bajo | esfuerzo: medio)
- [ ] Plantillas de email y seguimiento de envíos (impacto: medio | esfuerzo: alto)

### Siguiente iteración recomendada
- [ ] Endurecer login (rate limit, intentos fallidos, mensajes no reveladores)
- [ ] Pruebas manuales de flujos críticos (Fase 04)
- [ ] Tag de versión para entrega final
