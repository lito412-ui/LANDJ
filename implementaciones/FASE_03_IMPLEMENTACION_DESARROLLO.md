# Fase 03 - Implementación y Desarrollo

Objetivo: construir el CRM de forma incremental hasta el MVP funcionando end-to-end.

## Estado actual
- Avance estimado: `88%`
- Completado: infraestructura Docker + sistema de migraciones, auth con CSRF/CSP, esquema BD completo (6 tablas), seed vía migraciones, monitorización real, vista de perfil, panel modularizado (partials PHP + assets por dominio), CRUD completo de contactos, leads, usuarios y oportunidades. Conversión lead→contacto (transacción atómica). Pipeline kanban de oportunidades con 5 etapas, cambio de etapa con confirm. Modal de confirmación con variantes `danger`/`success`/`info`. CRUD de actividades/notas ligadas a entidades. JS dividido en 6 módulos independientes. Búsqueda y filtros avanzados en los 3 listados CRM (empresa, origen, valor range, fechas, orden/dir con estado en objeto por módulo).
- Pendiente principal: paginación, permisos en UI.

## Checklist de tareas (MVP primero)

### Infraestructura y base
- [x] Preparar estructura del proyecto (carpetas por dominio, convenciones, rutas)
- [x] Configurar entorno Docker (Nginx + PHP-FPM + MySQL + migrate + phpMyAdmin + cAdvisor)
- [x] Corregir healthcheck MySQL y variable `MYSQL_ALLOW_EMPTY_PASSWORD`
- [x] Corregir bug de contraseña vacía en `public/config/conexion.php`
- [x] Crear esquema SQL completo del MVP con 6 tablas y FKs (`database/init.sql`)
- [x] Implementar sistema de migraciones (`database/migrate.php` + `database/migrations/`)
- [x] Seed con datos de prueba vía migraciones (`002_seed_usuarios.php`, `003_seed_crm.sql`)
- [x] Migración `002_leads_contacto_id.php`: añade columna `contacto_id` + FK a tabla `leads` de forma idempotente (compatible con volúmenes Docker persistentes)
- [x] Añadir `try_files $uri =404` al bloque PHP de Nginx (seguridad + comportamiento correcto)

### Seguridad
- [x] Implementar `public/config/seguridad.php`: cabeceras HTTP (CSP, X-Frame-Options, Referrer-Policy), funciones CSRF (`csrfGenerar`, `csrfMeta`, `csrfValidar`)
- [x] `fetchSeguro()` en cliente: añade `X-CSRF-Token` automáticamente a POST/PUT/DELETE/PATCH
- [x] `registrarAuditoria()` tolerante a fallos: captura `PDOException` internamente para no bloquear operaciones si la tabla no existe

### Autenticación y sesión
- [x] Implementar autenticación (hash Argon2id, login, logout, sesión PHP)
- [x] Implementar control de acceso (RBAC) en rutas y endpoints protegidos
- [x] `api/get_user.php` devuelve `id_usuario`, `nombre`, `rol`, `email`, `created_at`

### Panel de control y estructura
- [x] Modularizar panel: `cpanel.php` con includes PHP por sección
- [x] Partials independientes: `head.php` (con meta csrf-token), `header.php`, `sidebar.php`
- [x] Secciones PHP bajo `modules/dashboard/partials/sections/`
- [x] Assets organizados por dominio (`css/site/`, `css/dashboard/`, `js/site/`, `js/dashboard/`)
- [x] JS del panel dividido en 6 módulos independientes: `cpanel-core.js`, `cpanel-actividades.js`, `cpanel-contactos.js`, `cpanel-leads.js`, `cpanel-usuarios.js`, `cpanel-oportunidades.js`

### Monitorización del sistema
- [x] `api/monitorizacion.php` con métricas reales (CPU, RAM, Disco) multiplataforma
- [x] `setMetrica()` helper en cliente para eliminar duplicación

### Perfil de usuario
- [x] Vista `#perfil` con avatar de iniciales, nombre, email, rol y fecha de registro
- [x] Accesible desde dropdown "Mi Perfil" en cabecera

### CRM — Contactos
- [x] CRUD base (API REST + módulo JS `Contactos`)
- [x] Listado con búsqueda debounced (400ms) server-side LIKE
- [x] Formulario crear/editar con panel animado
- [x] Eliminación con confirmación modal e integridad referencial
- [x] Validación JS en tiempo real + PHP servidor: nombre, apellidos, email, teléfono español, maxlength, counter notas
- [x] Vista de detalle (panel lateral fijo `position:fixed, top:64px`, z-index 1100, movido a `<body>` por JS)
- [x] Toast notifications (success/error/info) y confirm modal reutilizables
- [x] Auditoría en crear/editar/eliminar

### CRM — Leads
- [x] CRUD base (API REST + módulo JS `Leads` + sección HTML)
- [x] Listado con búsqueda debounced y filtro por estado (combinados con `URLSearchParams`)
- [x] Formulario crear/editar con validaciones JS + PHP
- [x] Badges de estado con color semántico (nuevo/contactado/calificado/convertido/descartado)
- [x] Panel de detalle lateral (mismo patrón que contactos)
- [x] Auditoría en crear/editar/eliminar
- [x] Estado vacío `crm-empty` en tbody cuando no hay resultados

### CRM — Gestión de Usuarios (admin-only)
- [x] API REST `usuarios.php` con CRUD completo (GET lista/detalle, POST, PUT, DELETE)
- [x] Validación PHP: nombre alfanumérico único, email único, contraseña mín 8 car. letras+números
- [x] Protecciones: no auto-eliminación, no eliminar último admin, no cambiar propio rol
- [x] Módulo JS `Usuarios`: listado, badges de rol (administrador/usuario), etiqueta "Tú" en fila propia
- [x] Filtro por rol client-side
- [x] Botón eliminar deshabilitado para cuenta propia
- [x] Formulario con contraseña obligatoria en creación, opcional en edición
- [x] Estilos globales reutilizados: `crm-toolbar`, `crm-search`, `data-table`, `btn-icon`, `crm-avatar`
- [x] Auditoría en crear/editar/eliminar

### CRM — Oportunidades (Pipeline)
- [x] API REST `oportunidades.php`: GET lista/detalle (JOIN contactos+leads), POST, PUT, PUT `?action=etapa`, DELETE
- [x] Validación PHP: título obligatorio max 150, descripción max 500, valor decimal ≥ 0, etapa enum, fecha YYYY-MM-DD
- [x] Módulo JS `Oportunidades`: kanban board con 5 columnas (prospecto/propuesta/negociación/ganada/perdida)
- [x] Cards con título, valor formateado (€ `Intl.NumberFormat`), contacto/lead asociado, fecha cierre
- [x] Formulario crear/editar con panel animado (título, valor, etapa, fecha cierre, descripción)
- [x] Panel detalle lateral: campos, etapa badge, botones de cambio de etapa con confirm
- [x] Cambio de etapa (`PUT ?action=etapa`) con modal `info` (azul), actualización inmediata sin recargar detalle
- [x] Búsqueda debounced (400 ms) + filtro por etapa en toolbar
- [x] Columnas vacías con estado `pipeline-empty`; spinner de carga inicial
- [x] Auditoría en crear/editar/eliminar/mover etapa
- [x] Sidebar: enlace "Pipeline" en sección CRM

### CRM core — fixes transversales
- [x] Conversión de lead a contacto (transacción atómica: INSERT contactos + UPDATE leads, rollback on error, detección email duplicado, auditoría en ambas entidades)
- [x] Modal de confirmación reutilizable con variantes `danger` (rojo), `success` (verde), `info` (azul)
- [x] Fix `detalleId` capturado en `const id` antes de llamar `cerrarDetalle()` en Contactos, Leads y Oportunidades

### CRM — Actividades / Notas
- [x] API REST `actividades.php`: GET por entidad (`contacto_id` / `lead_id` / `oportunidad_id`), GET por id, POST, PUT, DELETE
- [x] Validación PHP: tipo enum (`nota/llamada/reunion/tarea/email`), descripción obligatoria max 500, fecha YYYY-MM-DD opcional, al menos una FK requerida
- [x] `ActividadesWidget` IIFE compartido: init por `{ prefix, entityType, entityId }`, formulario inline con tipo/descripción/fecha, edición y eliminación por id
- [x] Widget integrado en paneles de detalle de Contactos (`det`), Leads (`ldet`) y Oportunidades (`odet`)
- [x] Lista con iconos por tipo y fecha formateada; estado vacío y spinner de carga
- [x] Auditoría en crear/editar/eliminar
- [x] HTML de actividades añadido a secciones de leads y contactos (paneles detalle)

### CRM core — Búsqueda y filtros avanzados
- [x] Estado de búsqueda por módulo: objeto `_estado` con todos los params (buscar, filtros, orden, dir)
- [x] `cargar()` construye `URLSearchParams` desde `_estado`, sin leer el DOM directamente
- [x] Panel de filtros avanzados colapsable por módulo (toggle con badge de filtros activos)
- [x] Contactos: filtro `empresa` (LIKE), `desde`/`hasta` (DATE range), `orden` (nombre/empresa/created_at), `dir`
- [x] Leads: filtro `origen` (LIKE), `desde`/`hasta`, `orden` (nombre/estado/created_at), `dir`
- [x] Oportunidades: filtro `valor_min`/`valor_max`, `cierre_desde`/`cierre_hasta`, `orden` (titulo/valor/etapa/fecha_cierre/created_at), `dir`. Buscar extendido a `descripcion`
- [x] Botón "Limpiar" resetea filtros avanzados manteniendo búsqueda principal y filtro de estado/etapa
- [x] Botón de dirección (asc/desc) con icono reactivo
- [x] API: validación whitelist de columnas ordenables; fechas validadas con regex; valores numéricos validados antes de bindear

### CRM core (pendiente)
- [ ] Paginación básica en listados
- [ ] Manejo de errores unificado y consistente

## Checklist de UX (mínimo viable)
- [x] Navegación consistente (menú, estados activos, dropdown de usuario)
- [x] Feedback visual en métricas del dashboard (barras en tiempo real)
- [x] Vista de perfil con datos reales del usuario autenticado
- [x] Estados vacíos (`crm-empty`) y mensajes de carga (`crm-loading`) en todos los listados
- [x] Toast notifications y confirm modal reutilizables en todas las secciones CRM
- [ ] Permisos reflejados en la UI (botones/acciones según rol en contactos y leads)

## Criterio de "Hecho"
- El usuario puede: entrar, gestionar contactos, leads y usuarios, mover oportunidades en el pipeline y crear actividades, con permisos correctos y feedback claro.
