# Implementaciones CRM — Guía de proyecto

Este directorio centraliza planificación, seguimiento y operación del proyecto.

## Contenido de esta carpeta

| Archivo | Descripción |
|---------|-------------|
| `SEGUIMIENTO.md` | Avance global por fases y backlog de mejoras |
| `MVP_HISTORIAS_Y_CRITERIOS.md` | Epics, criterios Given/When/Then, matriz de roles y RNF |
| `FASE_01_ANALISIS_PLANIFICACION.md` | Alcance y definición funcional |
| `FASE_02_DISENO_ARQUITECTURA.md` | Diseño técnico, modelo de datos y contratos |
| `FASE_03_IMPLEMENTACION_DESARROLLO.md` | Checklist de implementación completo |
| `FASE_04_PRUEBAS_VALIDACION.md` | Checklist de calidad y seguridad |
| `FASE_05_DESPLIEGUE_DOCUMENTACION.md` | Checklist de entrega y despliegue |
| `CONTRATOS_API.md` | Contratos REST de todos los endpoints |
| `VALIDACIONES.md` | Reglas de validación JS + PHP por entidad |
| `BUSQUEDA_FILTROS.md` | Diseño de búsqueda, filtros y paginación |
| `SEGURIDAD_CSRF_XSS.md` | Cabeceras de seguridad y mecanismo CSRF |

---

## Arquitectura actual del proyecto

### Stack y componentes

| Capa | Tecnología |
|------|------------|
| Frontend | HTML, CSS, JavaScript vanilla (IIFE modules) |
| Backend | PHP 8.3-FPM + PDO |
| Base de datos | MySQL 8.4 — 19 tablas de dominio + `_migraciones` |
| Servidor web | Nginx Alpine |
| Entorno | Docker Compose |
| Herramientas | phpMyAdmin (BD visual), cAdvisor (métricas contenedores) |

### Servicios Docker

| Servicio | Imagen | Puerto | Descripción |
|----------|--------|--------|-------------|
| `web` | nginx:alpine | 91 | Sirve `public/`; raíz en `http://localhost:91` |
| `php` | php:8.3-fpm-alpine | — | Ejecuta PHP-FPM; variables `DB_*` inyectadas por Docker |
| `db` | mysql:8.4 | 3307 | Inicializado con `database/init.sql` |
| `migrate` | (build local) | — | Runner de migraciones al arrancar |
| `cron` | (build local) | — | Ejecuta `recordatorios_facturas.php` + `generar_facturas_recurrentes.php` cada `CRON_INTERVALO_SEGUNDOS` (3600 por defecto); espera a que `migrate` termine con éxito |
| `phpmyadmin` | phpmyadmin:latest | 8082 | Gestión visual de BD |
| `cadvisor` | gcr.io/cadvisor/cadvisor | 8080 | Monitorización de contenedores |

---

### Flujo de autenticación

1. El usuario abre `public/modules/site/login.html`.
2. El formulario envía credenciales por POST a `public/auth/login.php`.
3. `login.php` valida contra la tabla `usuarios` con `password_verify` (Argon2id).
4a. **Sin 2FA**: crea `$_SESSION['user_id']`, `$_SESSION['nombre']`, `$_SESSION['rol']` con `session_regenerate_id` y redirige al panel.
4b. **Con 2FA activo**: genera OTP de 6 dígitos, lo almacena en BD con expiración 10 min, envía email (PHPMailer), guarda `$_SESSION['2fa_pending']` y redirige a `public/auth/verify-2fa.php`.
5. `verify-2fa.php` muestra pantalla OTP con countdown de 10 min y opción de reenvío. Valida el código con `hash_equals`. Máx 3 intentos antes de bloqueo (redirige a login con `?error=2fa_bloqueado`). En caso de éxito, `session_regenerate_id` y redirige al panel.
6. `public/modules/dashboard/cpanel.php` genera CSRF token, comprueba sesión e incluye los partials.
7. `public/api/get_user.php` devuelve sesión activa en JSON.
8. `public/auth/logout.php` destruye la sesión y redirige al login.

---

### Modelo de datos (19 tablas de dominio)

```text
usuarios             ← cuentas con rol (usuario/administrador), hash Argon2id y columnas 2FA
                       (two_factor_enabled, two_factor_code, two_factor_expires_at, two_factor_attempts)
contactos            → FK usuarios (creado_por)
leads                → FK usuarios, FK contactos (si convertido)
oportunidades        → FK contactos, FK leads, FK usuarios (asignado_a, creado_por)
productos            → FK usuarios (creado_por), FK proveedores (proveedor_id, opcional, ON DELETE SET NULL);
                       catalogo de productos/servicios (codigo, precio, IVA, stock)
proveedores          → FK usuarios (creado_por); nombre, nif (unico), email, telefono, direccion, activo
modulos_visibilidad  → FK usuarios (actualizado_por); catalogo de 15 modulos ocultables por rol (visible bool)
avisos               → FK usuarios (creado_por); mensaje de un admin a otros usuarios (titulo, mensaje, tipo)
avisos_destinatarios → FK avisos + FK usuarios (PK compuesta); leido_at nulable por destinatario
presupuestos         → FK contactos, FK usuarios (creado_por), FK facturas (factura_id, si se convirtio)
                       token_confirmacion/token_confirmado_at (confirmacion publica sin login)
presupuesto_lineas   → FK presupuestos (ON DELETE CASCADE), conceptos y totales por linea
facturas             → FK contactos, FK usuarios (creado_por), FK facturas_recurrentes (recurrente_id, opcional),
                       numeracion FAC-YYYY-0001, recordatorio_enviado_at (recordatorios de cobro automaticos)
factura_lineas       → FK facturas (ON DELETE CASCADE), conceptos y totales por linea
facturas_recurrentes → FK contactos, FK usuarios (creado_por); plantilla que genera una factura real cada ciclo
                       (periodicidad, dia_generacion, proxima_generacion, activa, enviar_email)
facturas_recurrentes_lineas → FK facturas_recurrentes (ON DELETE CASCADE), conceptos y precios de la plantilla
actividades          → FK contactos, FK leads, FK oportunidades (tambien usada como "tarea"
                       interna por el script de recordatorios de cobro)
auditoria            → FK usuarios (ON DELETE SET NULL)
dominios             ← dominios con tipo, estado, IP, SSL
cuentas_correo       ← cuentas de correo con cuota y estado
_migraciones         ← registro de migraciones aplicadas (sistema interno)
```

Esquema completo: [`database/init.sql`](../database/init.sql)

#### ENUMs relevantes

| Tabla | Campo | Valores |
|-------|-------|---------|
| `leads` | `estado` | `nuevo`, `contactado`, `calificado`, `convertido`, `descartado` |
| `oportunidades` | `etapa` | `prospecto`, `propuesta`, `negociacion`, `cerrada_ganada`, `cerrada_perdida` |
| `facturas` | `estado` | `borrador`, `emitida`, `pagada`, `vencida`, `cancelada` |
| `actividades` | `tipo` | `nota`, `llamada`, `reunion`, `tarea`, `email` |
| `dominios` | `tipo` | `principal`, `subdominio`, `addon`, `parked` |
| `dominios` | `estado` | `activo`, `pendiente`, `suspendido` |
| `cuentas_correo` | `estado` | `activo`, `suspendido` |

---

### Módulos JavaScript (20 ficheros)

Todos en `public/assets/js/dashboard/`. Patrón IIFE con `_initialized` guard. Cache-busting con `filemtime` en `cpanel.php`.

| Módulo | Scope | Descripción |
|--------|-------|-------------|
| `cpanel-core.js` | Global | `fetchSeguro`, `mostrarToast`, `mostrarConfirm`, `renderPaginacion`, `manejarApiError`, `navegarA`, `initThemeToggle`, `cargarActividadReciente`, `importarCsvArchivo`, `mostrarResultadoImportacion`; `_poblarDropdownHeader` rellena identidad en el menú de usuario al autenticarse |
| `cpanel-actividades.js` | CRM | Widget compartido de actividades (notas/llamadas/reuniones/tareas/email) |
| `cpanel-contactos.js` | CRM | CRUD contactos + detalle lateral + export/import CSV |
| `cpanel-leads.js` | CRM | CRUD leads + conversión a contacto + export/import CSV |
| `cpanel-oportunidades.js` | CRM | Pipeline kanban 5 etapas + drag & drop HTML5 entre columnas (`_initDragDrop`, `_moverEtapaDirecto`) + export/import CSV |
| `cpanel-presupuestos.js` | CRM | CRUD presupuestos, líneas con selector de producto, PDF/email, conversión a factura (`convertirEnFactura`) y export/import CSV |
| `cpanel-productos.js` | CRM | CRUD catálogo de productos/servicios (código, precio, IVA, stock, activo, proveedor) + export/import CSV |
| `cpanel-proveedores.js` | CRM | CRUD proveedores (nombre, NIF, contacto), detalle lateral + export/import CSV |
| `cpanel-facturas.js` | CRM | CRUD facturas, líneas editables con selector de producto/servicio (autorrellena concepto/precio/IVA), cálculo de totales, filtros, detalle lateral y export/import CSV |
| `cpanel-recurrentes.js` | CRM | Plantillas de facturas recurrentes: CRUD, líneas con selector de producto, botón "Generar ahora", historial de facturas generadas |
| `cpanel-estadisticas.js` | Dashboard | Métricas y gráficos CRM |
| `cpanel-email.js` | Hosting | CRUD cuentas de correo |
| `cpanel-dominios.js` | Hosting | CRUD dominios |
| `cpanel-configuracion.js` | Sistema | Perfil, contraseña, tema, toggle 2FA; expone `cargar(data)` para pre-cargar resumen de identidad al autenticarse |
| `cpanel-usuarios.js` | Admin | Gestión de usuarios (admin-only) |
| `cpanel-auditoria.js` | Admin | Log de auditoría con diff (admin-only) |
| `cpanel-modulos.js` | Admin | Visibilidad de módulos por rol, toggle instantáneo (admin-only) |
| `cpanel-avisos.js` | Panel Principal (todos) | Bandeja de avisos recibidos + badge de no leídos; composición y "Enviados" solo para admin |
| `cpanel-databases.js` | Admin | Estadísticas MySQL (admin-only) |
| `cpanel-backups.js` | Admin | Copias de seguridad (admin-only) |

---

### Endpoints API

| Endpoint | Métodos | Auth | Descripción |
|----------|---------|------|-------------|
| `/api/get_user.php` | GET | Sesión | Usuario autenticado |
| `/api/monitorizacion.php` | GET | Sesión | CPU, RAM, Disco |
| `/api/actividad_reciente.php` | GET | Sesión | Últimos 10 eventos de auditoría |
| `/api/estadisticas.php` | GET | Sesión | Métricas CRM |
| `/api/contactos.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD contactos + `?action=exportar`/`?action=importar` (CSV) |
| `/api/leads.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD leads + conversión + `?action=exportar`/`?action=importar` (CSV) |
| `/api/oportunidades.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD oportunidades + etapa + `?action=exportar`/`?action=importar` (CSV) |
| `/api/presupuestos.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD presupuestos + lineas + `?action=convertir` (a factura) + export/import CSV |
| `/api/presupuesto_pdf.php` | GET | Sesión | Descarga PDF del presupuesto |
| `/api/presupuesto_email.php` | POST | Sesión + CSRF | Envía el presupuesto por email con el PDF adjunto; genera el token de confirmación pública |
| `/presupuesto-confirmar.php` | GET/POST | **Sin sesión** (token de 64 hex en la URL) | Página pública para que el cliente acepte/rechace el presupuesto |
| `/api/productos.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD catálogo de productos/servicios + `?action=exportar`/`?action=importar` (CSV) |
| `/api/proveedores.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD proveedores + `?action=exportar`/`?action=importar` (CSV) |
| `/api/facturas.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD facturas + lineas + totales + `?action=exportar`/`?action=importar` (CSV) |
| `/api/facturas_recurrentes.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD plantillas + lineas + `?action=generar` (factura inmediata) |
| `/api/factura_pdf.php` | GET | Sesión | Descarga PDF de la factura |
| `/api/factura_email.php` | POST | Sesión + CSRF | Envía la factura por email con el PDF adjunto |
| `/api/actividades.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD actividades |
| `/api/dominios.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD dominios |
| `/api/cuentas_correo.php` | GET/POST/PUT/DELETE | Sesión + CSRF | CRUD cuentas correo |
| `/api/configuracion.php` | GET/PUT | Sesión + CSRF | Perfil propio + contraseña + estado y toggle 2FA |
| `/api/usuarios.php` | GET/POST/PUT/DELETE | Sesión + CSRF + Admin | CRUD usuarios |
| `/api/auditoria.php` | GET | Sesión + Admin | Log de auditoría |
| `/api/databases.php` | GET | Sesión + Admin | Estadísticas tablas |
| `/api/backups.php` | GET/POST/DELETE | Sesión + CSRF + Admin | Backups |
| `/api/modulos_visibilidad.php` | GET/PUT | Sesión (GET) / + Admin (PUT) | Visibilidad de módulos por rol |
| `/api/avisos.php` | GET/POST/PUT/DELETE | Sesión (GET/PUT) / + Admin (POST/DELETE) | Avisos de un admin a otros usuarios |

---

### Estructura de secciones del panel

```text
public/modules/dashboard/partials/sections/
├── panel/
│   ├── dashboard.php       # Métricas, acciones rápidas, actividad reciente
│   └── statistics.php      # Estadísticas CRM
├── avisos.php              # Bandeja (todos) + composición/enviados (admin, data-admin-only)
├── crm/
│   ├── contactos.php
│   ├── leads.php
│   ├── oportunidades.php   # Pipeline kanban
│   ├── presupuestos.php    # Presupuestos con conversion a factura
│   ├── productos.php       # Catálogo de productos/servicios
│   ├── proveedores.php     # Proveedores, enlazados opcionalmente desde productos
│   ├── facturas.php        # Facturas vinculadas a contactos; concepto seleccionable desde productos
│   └── facturas_recurrentes.php # Plantillas que generan una factura real cada ciclo
├── correo/
│   ├── email.php            # Cuentas de correo
│   └── domains.php          # Dominios
├── sistema/
│   ├── users.php            # Gestión de usuarios (admin)
│   ├── logs.php             # Auditoría (admin)
│   ├── modulos.php          # Visibilidad de módulos por rol (admin)
│   └── configuracion.php    # Configuración de cuenta
├── archivos/
│   ├── databases.php        # Estadísticas BD (admin)
│   ├── backups.php          # Copias de seguridad (admin)
│   ├── file-manager.php     # Gestor de archivos (próximamente)
│   └── ftp.php              # Configuración FTP (próximamente)
├── seguridad/
│   ├── ssl.php              # SSL (próximamente)
│   ├── security.php         # Seguridad (próximamente)
│   └── firewall.php         # Firewall (próximamente)
└── perfil.php               # Perfil del usuario
```

---

### Roles y permisos

| Rol | Acceso |
|-----|--------|
| No autenticado | Solo landing y login |
| `usuario` | Panel completo (CRM, Dominios, Correo, Configuración, Perfil) |
| `administrador` | Todo lo anterior + Usuarios, Auditoría, Bases de Datos, Backups |

Secciones admin-only: `users`, `logs`, `databases`, `backups`.

---

## Pasos para desplegar en local (Docker)

### Arranque
```bash
docker compose up -d --build
```

### Aplicar migraciones (si necesario)
```bash
docker compose run --rm migrate
```

### URLs de acceso

| Servicio | URL |
|----------|-----|
| App web | http://localhost:91 |
| Login | http://localhost:91/modules/site/login.html |
| Panel | http://localhost:91/modules/dashboard/cpanel.php |
| phpMyAdmin | http://localhost:8082 |
| cAdvisor | http://localhost:8080 |

### Comandos útiles
```bash
docker compose ps
docker compose logs -f
docker compose down
docker compose down -v   # Reset completo de BD
docker compose exec php sh
docker compose exec db mysql -uroot
```

---

### CSS — Módulos (6 ficheros)

Todos en `public/assets/css/dashboard/`. Separados por dominio; cargados con `<link>` individuales (HTTP/2) con cache-busting `filemtime` en `head.php`.

| Módulo | Contenido |
|--------|-----------|
| `cpanel-base.css` | Layout, header, sidebar, cards, dashboard, menú de usuario/dropdown |
| `cpanel-crm.css` | CRM: toolbar, tablas, contactos, leads, backups, dominios, estadísticas, detalle lateral, toasts, modales, perfil, filtros |
| `cpanel-pipeline.css` | Pipeline kanban + drag & drop + paginación |
| `cpanel-admin.css` | Auditoría + bases de datos |
| `cpanel-sistema.css` | Configuración: layout aside/main, tarjeta de identidad, formularios, apariencia, seguridad |
| `cpanel-dark.css` | Todos los overrides `[data-theme="dark"]` (WCAG AA) |

---

## Convenciones

- "Hecho" = tarea completada, verificada y documentada.
- Marca tareas con `[x]` en cada fase del `SEGUIMIENTO.md`.
- Si una tarea se bloquea, anota motivo, impacto y decisión tomada.
- Todos los endpoints responden `{ ok: true, data: ... }` o `{ ok: false, error: "..." }`.
- Los endpoints mutantes requieren `X-CSRF-Token` header; `fetchSeguro()` lo inyecta automáticamente.
- Event delegation con `data-*` en todos los botones generados dinámicamente (CSP `script-src 'self'`).
- Import/export CSV: lógica de servidor compartida en `public/config/csv_util.php`; helpers de cliente compartidos en `cpanel-core.js` (`importarCsvArchivo`, `mostrarResultadoImportacion`). Detalle completo en [`CONTRATOS_API.md`](CONTRATOS_API.md#import--export-csv-contactos-leads-oportunidades-productos-facturas).
- Visibilidad de módulos: `public/config/modulos_visibilidad.php` (`verificarModuloVisible()`/`verificarModuloVisibleTexto()`) se llama al inicio de cada endpoint de un módulo ocultable — bloqueo real en backend, no solo en el sidebar. Ver [`CONTRATOS_API.md`](CONTRATOS_API.md#visibilidad-de-módulos-admin).
