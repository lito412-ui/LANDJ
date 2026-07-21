# L&J — CRM Web

Panel de control CRM (landing, login y panel), backend PHP con MySQL, servido con Nginx y PHP-FPM en Docker. La planificación, arquitectura y seguimiento por fases están en `implementaciones/`.

## Qué incluye este proyecto

### Autenticación y sesión
- **Login** (`public/modules/site/login.html` → `public/auth/login.php`): sesión PHP, hash Argon2id, tabla `usuarios`. Si el usuario tiene 2FA activo, el login genera un OTP de 6 dígitos (válido 10 min) y redirige a la pantalla de verificación.
- **Verificación en dos pasos (2FA)** (`public/auth/verify-2fa.php`): página de verificación OTP con countdown de 10 minutos, reenvío de código, bloqueo tras 3 intentos fallidos y redirección automática al panel en caso de éxito.
- **Perfil de usuario**: vista `#perfil` con datos reales (nombre, email, rol, fecha de registro).
- **Configuración de cuenta** (`#configuracion`): layout de cards verticales (`.config-vertical`, max-width 760px) — identidad horizontal (avatar, nombre, email, rol, metadatos), formularios de datos y contraseña, selector de apariencia, tarjeta de Seguridad con toggle de 2FA y botón de cierre de sesión, y tarjetas de Avisos y Recordatorios con iconos. El resumen se pre-carga al autenticarse.

### Dashboard principal
- **Monitorización**: métricas en tiempo real de CPU, RAM y disco actualizadas cada 3 s.
- **Actividad reciente**: los 10 últimos eventos de auditoría, con tiempo relativo y usuario.
- **Acciones rápidas**: botones para crear backup, nueva cuenta de correo, FTP y SSL.
- **Estadísticas CRM**: distribución de leads por estado, oportunidades por etapa y valor potencial.
- **Menú de usuario**: dropdown expandido con avatar de iniciales, nombre, email y badge de rol; accesos directos a Datos de la cuenta y Cambiar contraseña; toggle de apariencia inline sincronizado con el header.
- **Tema claro/oscuro**: toggle en la cabecera y en el dropdown de usuario, persistido en `localStorage`, con contraste WCAG AA en ambos modos.

### CRM
- **Contactos**: CRUD completo, búsqueda debounced (400 ms), filtros avanzados (empresa, fechas, orden), paginación server-side, detalle lateral con actividades.
- **Leads**: CRUD completo, búsqueda, filtros (estado, origen, fechas), paginación, panel de detalle con actividades, conversión de lead a contacto (transacción atómica).
- **Pipeline de Oportunidades**: kanban board con 5 etapas, drag & drop HTML5 entre columnas (mueve directo vía API sin modal), cambio de etapa con confirmación desde el panel de detalle, filtros avanzados (valor, fecha cierre, orden), detalle lateral con actividades.
- **Facturas**: CRUD completo de facturas vinculadas a contactos, numeración automática (`FAC-YYYY-0001`), líneas con cantidad/precio/IVA, cálculo de base imponible, IVA y total, estados (`borrador`, `emitida`, `pagada`, `vencida`, `cancelada`), filtros avanzados y detalle lateral.
- **Actividades**: CRUD de notas, llamadas, reuniones, tareas y emails ligados a contactos, leads y oportunidades. Widget compartido `ActividadesWidget`.

### cPanel — Hosting
- **Dominios**: CRUD completo con filtros por tipo (principal/subdominio/addon/parked), estado, IP, SSL toggle; paginación y ordenación.
- **Cuentas de correo**: CRUD completo con cuota (MB/GB/ilimitada), estado, filtros y paginación. El dominio se extrae automáticamente del email.

### Administración (solo admin)
- **Gestión de usuarios**: CRUD de cuentas con roles, protección anti-autoborrado y del último administrador.
- **Auditoría**: tabla de cambios con diff expandible (antes/después resaltados), filtros por entidad y acción, paginación offset.
- **Bases de datos**: estadísticas de tablas MySQL en tiempo real — motor, filas, tamaño, colación, última modificación; botón phpMyAdmin.
- **Copias de seguridad**: creación, descarga y eliminación de backups `.sql` completos de la base de datos.

### Seguridad transversal
- CSRF Synchronizer Token + Custom Request Header (`X-CSRF-Token`) en todos los endpoints mutantes.
- Content Security Policy (`script-src 'self'`), `X-Frame-Options`, `Referrer-Policy`, `X-Content-Type-Options`.
- Event delegation con atributos `data-*` — sin handlers inline (CSP compliant).
- **Verificación en dos pasos (2FA)**: OTP de 6 dígitos por email, expiración en 10 min, bloqueo tras 3 intentos y `session_regenerate_id` al autenticar.
- `manejarApiError` compartido en `core.js`; todos los endpoints devuelven `{ ok, data/error }` con código HTTP correcto.

---

## Stack

| Capa | Tecnología |
|------|------------|
| Frontend | HTML, CSS, JavaScript vanilla |
| Backend | PHP 8.3-FPM, PDO + MySQL |
| Email | PHPMailer 6.9 (Composer), SMTP |
| Servidor | Nginx Alpine |
| Datos | MySQL 8.4 |
| Entorno | Docker Compose |

---

## Estructura principal del código

```text
LANDJ/
├── composer.json                       # PHPMailer ^6.9 (instalado al arrancar el contenedor php)
├── vendor/                             # Dependencias PHP (generado por Composer, no en VCS)
├── database/
│   ├── init.sql                        # Esquema: 8 tablas con FKs
│   ├── migrate.php                     # Runner de migraciones idempotente
│   └── migrations/
│       ├── 002_leads_contacto_id.php   # FK contacto_id en leads
│       ├── 003_seed_usuarios.php       # Seed: 1 admin + 3 usuarios (Argon2id)
│       ├── 004_seed_crm.sql            # Seed: contactos, leads, oportunidades, actividades
│       ├── 005_dominios.sql            # Tabla dominios
│       ├── 006_cuentas_correo.sql      # Tabla cuentas_correo
│       ├── 008_2fa.sql                 # Columnas 2FA en usuarios (enabled, code, expires_at, attempts)
│       └── 009_facturas.sql            # Tablas facturas y factura_lineas
├── docker/
│   └── nginx/conf.d/default.conf
├── public/
│   ├── assets/
│   │   ├── css/site/                   # index-style.css, style.css, verify-2fa.css
│   │   ├── css/dashboard/              # CSS en 6 módulos independientes con filemtime cache-busting:
│   │   │   ├── cpanel-base.css         #   Layout, header, sidebar, cards, dashboard, user menu
│   │   │   ├── cpanel-crm.css          #   CRM: toolbar, tablas, contactos, leads, toasts, modales, perfil
│   │   │   ├── cpanel-pipeline.css     #   Pipeline kanban + drag & drop + paginación
│   │   │   ├── cpanel-admin.css        #   Auditoría y Bases de Datos
│   │   │   ├── cpanel-sistema.css      #   Configuración (aside/main, identity, seguridad, apariencia)
│   │   │   └── cpanel-dark.css         #   Todos los overrides [data-theme="dark"] (WCAG AA)
│   │   ├── js/site/                    # index-script.js
│   │   └── js/dashboard/
│   │       ├── cpanel-core.js          # Shared: fetchSeguro, toast, modal, paginación, temas, navegación
│   │       ├── cpanel-actividades.js   # Widget de actividades compartido
│   │       ├── cpanel-contactos.js     # CRM: Contactos
│   │       ├── cpanel-leads.js         # CRM: Leads
│   │       ├── cpanel-oportunidades.js # CRM: Pipeline kanban
│   │       ├── cpanel-facturas.js      # CRM: Facturas + líneas + totales
│   │       ├── cpanel-estadisticas.js  # Dashboard: Estadísticas
│   │       ├── cpanel-email.js         # Cuentas de correo
│   │       ├── cpanel-dominios.js      # Dominios
│   │       ├── cpanel-configuracion.js # Configuración de cuenta
│   │       ├── cpanel-usuarios.js      # Gestión de usuarios (admin)
│   │       ├── cpanel-auditoria.js     # Auditoría (admin)
│   │       ├── cpanel-databases.js     # Bases de datos (admin)
│   │       └── cpanel-backups.js       # Copias de seguridad (admin)
│   ├── modules/
│   │   ├── site/login.html
│   │   └── dashboard/
│   │       ├── cpanel.php              # Layout principal (session, CSRF, includes)
│   │       └── partials/
│   │           ├── head.php            # <head> con meta csrf-token
│   │           ├── header.php          # Cabecera: logo, tema toggle, menú usuario
│   │           ├── sidebar.php         # Navegación lateral
│   │           └── sections/
│   │               ├── panel/          # dashboard.php, statistics.php
│   │               ├── crm/            # contactos.php, leads.php, oportunidades.php, facturas.php
│   │               ├── correo/         # email.php, domains.php
│   │               ├── sistema/        # users.php, logs.php, configuracion.php
│   │               ├── archivos/       # databases.php, backups.php, file-manager.php, ftp.php
│   │               ├── seguridad/      # ssl.php, security.php, firewall.php
│   │               └── perfil.php
│   ├── api/
│   │   ├── get_user.php                # GET: usuario autenticado
│   │   ├── monitorizacion.php          # GET: CPU / RAM / Disco
│   │   ├── actividad_reciente.php      # GET: últimos 10 eventos de auditoría
│   │   ├── estadisticas.php            # GET: métricas CRM
│   │   ├── contactos.php               # CRUD + filtros + paginación
│   │   ├── leads.php                   # CRUD + filtros + paginación + conversión
│   │   ├── oportunidades.php           # CRUD + filtros + cambio de etapa
│   │   ├── facturas.php                # CRUD + líneas + totales + filtros + paginación
│   │   ├── actividades.php             # CRUD por entidad
│   │   ├── dominios.php                # CRUD + filtros + paginación
│   │   ├── cuentas_correo.php          # CRUD + filtros + paginación
│   │   ├── configuracion.php           # PUT perfil / PUT password / GET+PUT 2FA (usuario propio)
│   │   ├── usuarios.php                # CRUD (admin-only)
│   │   ├── auditoria.php               # GET paginado (admin-only)
│   │   ├── databases.php               # GET estadísticas (admin-only)
│   │   └── backups.php                 # CRUD backups (admin-only)
│   ├── auth/                           # login.php, logout.php, verify-2fa.php
│   ├── config/                         # Bloqueado por Nginx
│   │   ├── conexion.php                # PDO: lee variables de entorno
│   │   ├── seguridad.php               # Cabeceras HTTP + CSRF
│   │   ├── auditoria.php               # registrarAuditoria() tolerante a fallos
│   │   └── mailer.php                  # enviarEmail() vía PHPMailer SMTP + plantilla2FA()
│   └── test-mail.php                   # Diagnóstico SMTP (solo desarrollo; requiere sesión)
├── docker-compose.yml
└── README.md
```

---

## Base de datos — esquema

| Tabla | Descripción | Relaciones principales |
|-------|-------------|------------------------|
| `usuarios` | Cuentas con rol (`usuario`/`administrador`), hash Argon2id y columnas 2FA (`two_factor_enabled`, `two_factor_code`, `two_factor_expires_at`, `two_factor_attempts`) | — |
| `contactos` | Clientes/contactos del CRM | → `usuarios` |
| `leads` | Prospectos con estado y origen | → `usuarios`, → `contactos` |
| `oportunidades` | Negociaciones por etapas | → `contactos`, `leads`, `usuarios` |
| `facturas` | Facturas con numeración automática, estado, fechas y totales | → `contactos`, `usuarios` |
| `factura_lineas` | Líneas de factura con concepto, cantidad, precio, IVA y total | → `facturas` |
| `actividades` | Notas/tareas/llamadas por entidad | → `contactos`, `leads`, `oportunidades` |
| `auditoria` | Log de cambios: tabla, registro, acción, JSON antes/después | → `usuarios` (SET NULL) |
| `dominios` | Dominios con tipo, estado, IP y SSL | — |
| `cuentas_correo` | Cuentas de correo con cuota y estado | — |

---

## Usuarios de prueba

Definidos en `database/migrations/003_seed_usuarios.php`. Solo para entornos locales.

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| admin | lolito412/ | administrador |
| Samuel | tuchulito96 | usuario |
| lito412 | lolito412/ | usuario |
| Cuervo | soyunchulo | usuario |

> Las contraseñas se almacenan como hash Argon2id. Nunca en texto plano.

---

## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y en ejecución.
- Puertos libres: **91** (app), **3307** (MySQL), **8082** (phpMyAdmin), **8080** (cAdvisor).

Si algún puerto está ocupado, edita `.env` antes de arrancar:

```env
PORT_WEB=91
PORT_DB=3307
PORT_PMA=8082
PORT_CADVISOR=8080
```

---

## Cómo arrancar el proyecto

```bash
docker compose up -d --build
```

Al arrancar, el servicio `migrate` ejecuta automáticamente las migraciones pendientes e inserta los datos de prueba. Para relanzar manualmente:

```bash
docker compose run --rm migrate
```

### URLs útiles

| Servicio | URL |
|----------|-----|
| Aplicación | http://localhost:91 |
| Login | http://localhost:91/modules/site/login.html |
| Panel | http://localhost:91/modules/dashboard/cpanel.php |
| phpMyAdmin | http://localhost:8082 |
| cAdvisor | http://localhost:8080 |

### Parar el entorno

```bash
docker compose down
```

Reset completo de BD (borra volúmenes):

```bash
docker compose down -v && docker compose up -d --build
```

---

## Comandos útiles (Docker)

```bash
# Logs
docker compose logs -f
docker compose logs -f web
docker compose logs -f php
docker compose logs -f db

# Estado y reinicio
docker compose ps
docker compose restart
docker compose restart php
docker compose up -d --build
docker compose build --no-cache php

# Acceso a contenedores
docker compose exec php sh
docker compose exec db sh
docker compose exec db mysql -uroot
```

---

## Configuración de base de datos

`public/config/conexion.php` lee las variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`. Usa PDO con `utf8mb4` y `ERRMODE_EXCEPTION`.

> El host de MySQL desde PHP-FPM es el nombre del servicio Docker `db`, no `127.0.0.1`.

Conexión externa (fuera de Docker):
```bash
mysql -h 127.0.0.1 -P 3307 -uroot
```

phpMyAdmin disponible en `http://localhost:8082`. Credenciales: `root` sin contraseña (solo desarrollo).

> En producción: crea un usuario MySQL dedicado con permisos mínimos y cambia todas las credenciales.

---

## Configuración de email (SMTP)

El envío de códigos 2FA usa **PHPMailer** (instalado vía Composer al arrancar el contenedor `php`). Las credenciales se pasan como variables de entorno:

```env
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu@gmail.com
SMTP_PASS=xxxxxxxxxxxxxxxxxxxx   # Google App Password (16 chars, sin espacios)
MAIL_FROM=tu@gmail.com
```

> Requiere 2-Step Verification activada en Google y una App Password generada en `myaccount.google.com/apppasswords`.  
> Alternativa recomendada: [Resend](https://resend.com) (API REST, sin SMTP).

Para diagnosticar la conexión SMTP en desarrollo, accede (con sesión activa) a:
```
http://localhost:91/test-mail.php
```

---

## Documentación ampliada

- **Arquitectura, estructura y comandos**: [`implementaciones/README.md`](implementaciones/README.md)
- **MVP, historias de usuario y criterios de aceptación**: [`implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md`](implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md)
- **Seguimiento y fases**: [`implementaciones/SEGUIMIENTO.md`](implementaciones/SEGUIMIENTO.md)
- **Contratos de API**: [`implementaciones/CONTRATOS_API.md`](implementaciones/CONTRATOS_API.md)
- **Validaciones**: [`implementaciones/VALIDACIONES.md`](implementaciones/VALIDACIONES.md)
- **Seguridad CSRF/XSS**: [`implementaciones/SEGURIDAD_CSRF_XSS.md`](implementaciones/SEGURIDAD_CSRF_XSS.md)
