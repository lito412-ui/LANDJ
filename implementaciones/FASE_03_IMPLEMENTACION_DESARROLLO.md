# Fase 03 - Implementación y Desarrollo

Objetivo: construir el CRM de forma incremental hasta el MVP funcionando end-to-end.

## Estado actual
- Avance estimado: `100%`
- Pendiente del MVP: ninguno. Fase 03 completa.

**Completado**: infraestructura Docker + migraciones, auth con CSRF/CSP, esquema BD completo (8 tablas), seed vía migraciones, monitorización real, perfil de usuario, panel modularizado (13 módulos JS). CRUD de contactos, leads, usuarios, oportunidades, actividades. Conversión lead→contacto atómica. Pipeline kanban con drag & drop HTML5. Búsqueda/filtros avanzados en todos los módulos. Paginación server-side. Auditoría con diff expandible. Bases de datos con estadísticas MySQL. Backups SQL. Dominios y cuentas de correo (hosting). Estadísticas CRM. Actividad reciente. Acciones rápidas. Tema claro/oscuro WCAG AA. Configuración de cuenta (layout aside/main). Dropdown de usuario expandido. CSS refactorizado en 6 módulos independientes.

---

## Checklist de tareas

### Infraestructura y base
- [x] Preparar estructura del proyecto (carpetas por dominio, convenciones, rutas)
- [x] Configurar entorno Docker (Nginx + PHP-FPM + MySQL + migrate + phpMyAdmin + cAdvisor)
- [x] Corregir healthcheck MySQL y variable `MYSQL_ALLOW_EMPTY_PASSWORD`
- [x] Crear esquema SQL completo del MVP con 6 tablas y FKs (`database/init.sql`)
- [x] Ampliar esquema con tablas `dominios` y `cuentas_correo` (migrations 005, 006)
- [x] Implementar sistema de migraciones idempotente (`database/migrate.php` + tabla `_migraciones`)
- [x] Seed con datos de prueba vía migraciones (usuarios Argon2id + datos CRM)
- [x] Añadir `try_files $uri =404` al bloque PHP de Nginx

### Seguridad
- [x] `public/config/seguridad.php`: cabeceras HTTP (CSP `script-src 'self'`, X-Frame-Options, Referrer-Policy), funciones CSRF (`csrfGenerar`, `csrfMeta`, `csrfValidar`)
- [x] `fetchSeguro()` en cliente: añade `X-CSRF-Token` automáticamente a POST/PUT/DELETE/PATCH
- [x] `registrarAuditoria()` tolerante a fallos: captura `PDOException` internamente
- [x] Event delegation con `data-edit`/`data-del` — sin handlers inline (CSP compliant)

### Autenticación y sesión
- [x] Autenticación: hash Argon2id, login, logout, sesión PHP
- [x] RBAC: control de acceso en rutas y endpoints protegidos
- [x] `api/get_user.php` devuelve `id_usuario`, `nombre`, `rol`, `email`, `created_at`

### Panel de control y estructura
- [x] `cpanel.php` con includes PHP por sección (partials)
- [x] `head.php` (con meta csrf-token), `header.php`, `sidebar.php`
- [x] Secciones PHP organizadas por dominio bajo `sections/`
- [x] 13 módulos JS IIFE con `_initialized` guard: `cpanel-core.js`, `cpanel-actividades.js`, `cpanel-contactos.js`, `cpanel-leads.js`, `cpanel-oportunidades.js`, `cpanel-estadisticas.js`, `cpanel-email.js`, `cpanel-dominios.js`, `cpanel-configuracion.js`, `cpanel-usuarios.js`, `cpanel-auditoria.js`, `cpanel-databases.js`, `cpanel-backups.js`
- [x] Cache-busting con `filemtime` en todos los `<script>` de `cpanel.php`
- [x] CSS refactorizado en 6 módulos independientes con cache-busting `filemtime` en `head.php`: `cpanel-base`, `cpanel-crm`, `cpanel-pipeline`, `cpanel-admin`, `cpanel-sistema`, `cpanel-dark`
- [x] Dropdown de usuario expandido: avatar de iniciales, nombre, email, badge de rol; sub-links a Datos de la cuenta y Cambiar contraseña; toggle de apariencia inline sincronizado con el header

### Dashboard y sistema
- [x] `api/monitorizacion.php` con métricas reales (CPU, RAM, Disco), actualización cada 3 s
- [x] Actividad reciente: `api/actividad_reciente.php` devuelve últimos 10 eventos con tiempo relativo; admins ven todos, usuarios solo los propios
- [x] Estadísticas CRM: `api/estadisticas.php` + `cpanel-estadisticas.js` — distribución de leads y oportunidades
- [x] Acciones rápidas del dashboard: `initQuickActions()` + `manejarAccionRapida(action)` con navegación programática `navegarA(sectionId)`
- [x] Vista de perfil (`#perfil`) con avatar de iniciales, nombre, email, rol y fecha

### Configuración de cuenta (`#configuracion`)
- [x] `api/configuracion.php`: `PUT` actualiza nombre/email; `PUT?accion=password` cambia contraseña con verificación
- [x] `cpanel-configuracion.js`: pre-rellena formulario con `perfilData`, medidor de fortaleza de contraseña, selector visual de tema
- [x] Layout aside/main: tarjeta de identidad sticky (avatar, nombre, email, badge de rol, metadatos), formularios Datos y Contraseña en paralelo, Apariencia y Seguridad en paralelo
- [x] `cargar(data)` público: pre-popula la tarjeta de resumen al autenticarse sin esperar a que el usuario abra la sección
- [x] Actualiza header (nombre) y tarjeta de identidad tras guardar sin recargar página

### Tema claro/oscuro
- [x] `initThemeToggle()` + `_aplicarTema()` en `cpanel-core.js`: toggle en cabecera, persiste en `localStorage`
- [x] `[data-theme="dark"]` completo en CSS con contraste WCAG AA
- [x] Badges de estado con clases semánticas CSS (sin `style=""` inline): `status-badge--activo/suspendido/pendiente/principal/subdominio/addon/parked`
- [x] Correcciones de contraste: nav-section-title, crm-empty, chart-label, quick-action-btn span
- [x] Overrides dark por sección: pipeline cards, backup stats, db summary, badges usuario, badges auditoría, configuración

### CRM — Contactos
- [x] CRUD base (API REST + módulo JS `Contactos`)
- [x] Búsqueda debounced (400 ms) server-side LIKE
- [x] Formulario crear/editar con panel animado (`.form-panel.active`)
- [x] Validaciones JS + PHP: nombre, apellidos, email, teléfono español, maxlength, counter notas
- [x] Vista de detalle lateral fija con actividades
- [x] Toast y confirm modal reutilizables
- [x] Auditoría en crear/editar/eliminar
- [x] Paginación server-side (LIMIT/OFFSET), `renderPaginacion` compartido

### CRM — Leads
- [x] CRUD completo con búsqueda, filtros (estado, origen, fechas, orden), paginación
- [x] Badges de estado semánticos (nuevo/contactado/calificado/convertido/descartado)
- [x] Panel de detalle lateral con actividades
- [x] Conversión lead → contacto: transacción atómica, detección email duplicado, auditoría en ambas entidades, botón deshabilitado si ya convertido
- [x] Auditoría en crear/editar/eliminar/convertir

### CRM — Oportunidades (Pipeline)
- [x] API: GET lista (JOIN contactos+leads), POST, PUT, PUT `?action=etapa`, DELETE
- [x] Kanban board con 5 columnas: prospecto/propuesta/negociación/ganada/perdida
- [x] Cards con título, valor (€ `Intl.NumberFormat`), contacto/lead asociado, fecha cierre
- [x] Cambio de etapa con modal `info` (azul), sin recargar toda la vista
- [x] Drag & drop HTML5 entre columnas: `dragstart`/`dragend` en cards, `dragover`/`dragleave`/`drop` en columnas; `_moverEtapaDirecto` actualiza vía API sin confirm
- [x] Búsqueda debounced + filtro por etapa + filtros avanzados (valor, fecha)
- [x] Auditoría en crear/editar/eliminar/mover etapa

### CRM — Actividades / Notas
- [x] API por entidad (`contacto_id` / `lead_id` / `oportunidad_id`)
- [x] `ActividadesWidget` IIFE compartido: init por `{ prefix, entityType, entityId }`
- [x] Formulario inline con tipo/descripción/fecha; edición y eliminación por id
- [x] Tipos: nota/llamada/reunión/tarea/email con iconos
- [x] Auditoría en crear/editar/eliminar

### CRM — Gestión de Usuarios (admin-only)
- [x] API REST CRUD completo con protecciones
- [x] Validación: nombre alfanumérico único, email único, contraseña mín 8 car. letras+números
- [x] Protecciones: no auto-eliminación, no eliminar último admin, no cambiar propio rol
- [x] Módulo JS `Usuarios`: badges de rol (`user-badge badge-administrador/badge-usuario`), etiqueta "Tú" (`user-yo-tag`)
- [x] Filtro por rol client-side; botón eliminar deshabilitado para cuenta propia

### Hosting — Dominios
- [x] `api/dominios.php`: CRUD completo con búsqueda/filtros (tipo, estado, orden, dir, paginación)
- [x] Validaciones: dominio (DOMAIN_RE), IP (IP_RE), tipo enum, estado enum
- [x] `cpanel-dominios.js`: badges semánticos para tipo y estado, `badgeSSL()` con `.ssl-on/.ssl-off`
- [x] CSP fix: `data-edit`/`data-del` con `querySelectorAll` post-render
- [x] Bug fixes: `registrarAuditoria` sin `$userId`; `` `ssl` `` backtick-escapado (reservada MySQL)

### Hosting — Cuentas de correo
- [x] `api/cuentas_correo.php`: CRUD completo con búsqueda/filtros (estado, orden, paginación)
- [x] `extraerDominio()`: extrae dominio del email automáticamente
- [x] `formatCuota()`: 0 → "Sin límite", ≥ 1024 MB → GB con 1 decimal
- [x] Validaciones: EMAIL_RE, cuota INT ≥ 0

### Panel — Auditoría (admin-only)
- [x] `api/auditoria.php` con filtros tabla/accion + COUNT total + whitelist tablas
- [x] `cpanel-auditoria.js`: tabla con diff expandible (before/after resaltados), filtros, paginación offset

### Panel — Bases de Datos (admin-only)
- [x] `api/databases.php`: `SHOW TABLE STATUS` con `SET SESSION information_schema_stats_expiry=0`
- [x] Tarjetas resumen (nombre BD, total tablas, total filas, tamaño); tabla con motor, filas, tamaño, colación, última modificación
- [x] Botón phpMyAdmin (enlace externo)

### Panel — Copias de Seguridad (admin-only)
- [x] `api/backups.php`: creación de dump `.sql`, listado, descarga y eliminación
- [x] `cpanel-backups.js`: confirmar antes de eliminar, `event delegation` con `data-del`
- [x] Acceso público a `confirmarCrear` para acción rápida del dashboard

---

## Criterio de "Hecho"
- El usuario puede: autenticarse, gestionar contactos/leads/oportunidades (con drag & drop en el kanban)/actividades, administrar dominios y cuentas de correo, configurar su cuenta y tema desde el layout aside/main o desde el dropdown del header, ver estadísticas y actividad reciente, con permisos correctos y feedback claro en ambos modos de color.
