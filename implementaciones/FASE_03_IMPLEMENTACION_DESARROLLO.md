# Fase 03 - Implementación y Desarrollo

Objetivo: construir el CRM de forma incremental hasta el MVP funcionando end-to-end.

## Estado actual
- Avance estimado: `38%`
- Completado: infraestructura Docker, auth, esquema BD completo (5 tablas con FKs), seed con datos de prueba, monitorización real del sistema, vista de perfil, panel modularizado por partials PHP y assets organizados por dominio.
- Pendiente principal: módulos core CRM (CRUD de contactos, leads, oportunidades, actividades).

## Checklist de tareas (MVP primero)

### Infraestructura y base
- [x] Preparar estructura del proyecto (carpetas por dominio, convenciones, rutas)
- [x] Configurar entorno Docker (Nginx + PHP-FPM + MySQL + seed + phpMyAdmin + cAdvisor)
- [x] Corregir healthcheck MySQL (`--password=` en lugar de `-p`) y añadir `MYSQL_ALLOW_EMPTY_PASSWORD`
- [x] Corregir bug de contraseña vacía en `public/config/conexion.php` (usar `!== false` en vez de `?:`)
- [x] Crear esquema SQL completo del MVP con 5 tablas y FKs (`database/init.sql`)
- [x] Implementar seed con datos de prueba para todas las tablas (`database/seed.php`)

### Autenticación y sesión
- [x] Implementar autenticación (hash Argon2id, login, logout, sesión PHP)
- [x] Implementar control de acceso (RBAC) en rutas y endpoints protegidos
- [x] `api/get_user.php` devuelve `nombre`, `rol`, `email` y `created_at` desde BD

### Panel de control y estructura
- [x] Modularizar panel: `cpanel.html` → `cpanel.php` con includes PHP por sección
- [x] Crear partials independientes: `head.php`, `header.php`, `sidebar.php`
- [x] Crear 14 secciones bajo `modules/dashboard/partials/sections/`
- [x] Reorganizar assets por dominio (`css/site/`, `css/dashboard/`, `js/site/`, `js/dashboard/`)
- [x] Eliminar archivos obsoletos (`auth.js`, `script.js`, `cpanel.html` monolítico)

### Monitorización del sistema
- [x] Reescribir `api/monitorizacion.php` con métricas reales por plataforma
  - CPU: `wmic cpu get loadpercentage` en Windows; delta de `/proc/stat` (200 ms) en Linux
  - RAM: `wmic OS get FreePhysicalMemory,TotalVisibleMemorySize` en Windows; `/proc/meminfo` en Linux
  - Disco: `disk_total_space` / `disk_free_space` adaptado por SO
- [x] Simplificar `cpanel-script.js`: eliminar `FACTOR_AJUSTE`, `LIMITE_RAM_MB` y lógica de delta en cliente
- [x] Extraer helper `setMetrica()` para eliminar código duplicado

### Perfil de usuario
- [x] Vista `#perfil` en panel con avatar de iniciales, nombre, email, rol y fecha de registro
- [x] Accesible desde dropdown "Mi Perfil" en la cabecera
- [x] Estilos de perfil añadidos a `assets/css/dashboard/cpanel-style.css`

### CRM core (pendiente)
- [ ] Implementar CRUD base para contactos/clientes
- [ ] Pantalla/listado de contactos (filtros básicos)
- [ ] Formulario de contacto (crear/editar) con validación de servidor
- [ ] Vista de detalle de contacto
- [ ] Eliminación de contacto con confirmación e integridad referencial
- [ ] Implementar CRUD base para leads
- [ ] Pantalla/listado de leads (filtros básicos)
- [ ] Formulario de lead (crear/editar)
- [ ] Conversión de lead a contacto y/o oportunidad
- [ ] Pipeline de oportunidades — listado agrupado por etapa
- [ ] Movimiento de oportunidades entre etapas
- [ ] CRUD de actividades/notas ligadas a entidades
- [ ] Listado de actividades por entidad y por usuario
- [ ] Búsqueda y filtros en listados (campos acordados en Fase 02)
- [ ] Paginación básica en listados
- [ ] Validaciones de servidor consistentes (mensajes claros en UI)
- [ ] Manejo de errores unificado
- [ ] Auditoría de cambios (al menos cambios de estado e ediciones importantes)

## Checklist de UX (mínimo viable)
- [x] Navegación consistente (menú, estados activos, dropdown de usuario)
- [x] Feedback visual en métricas del dashboard (barras en tiempo real)
- [x] Vista de perfil con datos reales del usuario autenticado
- [ ] Estados vacíos (sin datos) y mensajes de carga en listados CRM
- [ ] Permisos reflejados en la UI (botones/acciones según rol)

## Criterio de "Hecho"
- El usuario puede: entrar, gestionar contactos, gestionar leads, mover oportunidades en el pipeline y crear actividades, con permisos correctos y feedback claro.
