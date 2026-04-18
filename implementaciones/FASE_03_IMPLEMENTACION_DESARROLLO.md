# Fase 03 - Implementación y Desarrollo

Objetivo: construir el CRM de forma incremental hasta el MVP funcionando end-to-end.

## Estado actual
- Avance estimado: `62%`
- Completado: infraestructura Docker + sistema de migraciones, auth con CSRF/CSP, esquema BD completo (6 tablas), seed vía migraciones, monitorización real, vista de perfil, panel modularizado (partials PHP + assets por dominio), CRUD completo de contactos (validaciones JS+PHP, detalle lateral, toast/confirm), CRUD completo de leads (badges de estado, búsqueda + filtro, detalle lateral), CRUD completo de gestión de usuarios (admin-only, roles, autoprotección).
- Pendiente principal: oportunidades (pipeline), actividades/notas, búsqueda/filtros avanzados, paginación.

## Checklist de tareas (MVP primero)

### Infraestructura y base
- [x] Preparar estructura del proyecto (carpetas por dominio, convenciones, rutas)
- [x] Configurar entorno Docker (Nginx + PHP-FPM + MySQL + migrate + phpMyAdmin + cAdvisor)
- [x] Corregir healthcheck MySQL y variable `MYSQL_ALLOW_EMPTY_PASSWORD`
- [x] Corregir bug de contraseña vacía en `public/config/conexion.php`
- [x] Crear esquema SQL completo del MVP con 6 tablas y FKs (`database/init.sql`)
- [x] Implementar sistema de migraciones (`database/migrate.php` + `database/migrations/`)
- [x] Seed con datos de prueba vía migraciones (`002_seed_usuarios.php`, `003_seed_crm.sql`)
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

### CRM core (pendiente)
- [ ] Conversión de lead a contacto y/o oportunidad
- [ ] Pipeline de oportunidades — listado agrupado por etapa
- [ ] Movimiento de oportunidades entre etapas
- [ ] CRUD de actividades/notas ligadas a entidades
- [ ] Listado de actividades por entidad y por usuario
- [ ] Búsqueda y filtros avanzados en listados (campos acordados en Fase 02)
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
