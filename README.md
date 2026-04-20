# L&J — CRM Web

Panel de control CRM (landing, login y panel), backend PHP con MySQL, servido con Nginx y PHP-FPM en Docker. La planificación, arquitectura y seguimiento por fases están en `implementaciones/`.

## Qué incluye este proyecto

### Autenticación y sesión
- **Login** (`public/modules/site/login.html` → `public/auth/login.php`): sesión PHP, hash Argon2id, tabla `usuarios`.
- **Perfil de usuario**: vista `#perfil` con datos reales (nombre, email, rol, fecha de registro).
- **Configuración de cuenta** (`#configuracion`): edición de nombre/email, cambio de contraseña con verificación, selector de tema claro/oscuro.

### Dashboard principal
- **Monitorización**: métricas en tiempo real de CPU, RAM y disco actualizadas cada 3 s.
- **Actividad reciente**: los 10 últimos eventos de auditoría, con tiempo relativo y usuario.
- **Acciones rápidas**: botones para crear backup, nueva cuenta de correo, FTP y SSL.
- **Estadísticas CRM**: distribución de leads por estado, oportunidades por etapa y valor potencial.
- **Tema claro/oscuro**: toggle en la cabecera, persistido en `localStorage`, con contraste WCAG AA en ambos modos.

### CRM
- **Contactos**: CRUD completo, búsqueda debounced (400 ms), filtros avanzados (empresa, fechas, orden), paginación server-side, detalle lateral con actividades.
- **Leads**: CRUD completo, búsqueda, filtros (estado, origen, fechas), paginación, panel de detalle con actividades, conversión de lead a contacto (transacción atómica).
- **Pipeline de Oportunidades**: kanban board con 5 etapas, cambio de etapa con modal de confirmación, filtros avanzados (valor, fecha cierre, orden), detalle lateral con actividades.
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
- `manejarApiError` compartido en `core.js`; todos los endpoints devuelven `{ ok, data/error }` con código HTTP correcto.

---

## Stack

| Capa | Tecnología |
|------|------------|
| Frontend | HTML, CSS, JavaScript vanilla |
| Backend | PHP 8.3-FPM, PDO + MySQL |
| Servidor | Nginx Alpine |
| Datos | MySQL 8.4 |
| Entorno | Docker Compose |

---

## Estructura principal del código

```text
LANDJ/
├── database/
│   ├── init.sql                        # Esquema: 8 tablas con FKs
│   ├── migrate.php                     # Runner de migraciones idempotente
│   └── migrations/
│       ├── 002_leads_contacto_id.php   # FK contacto_id en leads
│       ├── 003_seed_usuarios.php       # Seed: 1 admin + 3 usuarios (Argon2id)
│       ├── 004_seed_crm.sql            # Seed: contactos, leads, oportunidades, actividades
│       ├── 005_dominios.sql            # Tabla dominios
│       └── 006_cuentas_correo.sql      # Tabla cuentas_correo
├── docker/
│   └── nginx/conf.d/default.conf
├── public/
│   ├── assets/
│   │   ├── css/site/                   # index-style.css, style.css
│   │   ├── css/dashboard/              # cpanel-style.css (tema claro + oscuro)
│   │   ├── js/site/                    # index-script.js
│   │   └── js/dashboard/
│   │       ├── cpanel-core.js          # Shared: fetchSeguro, toast, modal, paginación, temas, navegación
│   │       ├── cpanel-actividades.js   # Widget de actividades compartido
│   │       ├── cpanel-contactos.js     # CRM: Contactos
│   │       ├── cpanel-leads.js         # CRM: Leads
│   │       ├── cpanel-oportunidades.js # CRM: Pipeline kanban
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
│   │               ├── crm/            # contactos.php, leads.php, oportunidades.php
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
│   │   ├── actividades.php             # CRUD por entidad
│   │   ├── dominios.php                # CRUD + filtros + paginación
│   │   ├── cuentas_correo.php          # CRUD + filtros + paginación
│   │   ├── configuracion.php           # PUT perfil / PUT password (usuario propio)
│   │   ├── usuarios.php                # CRUD (admin-only)
│   │   ├── auditoria.php               # GET paginado (admin-only)
│   │   ├── databases.php               # GET estadísticas (admin-only)
│   │   └── backups.php                 # CRUD backups (admin-only)
│   ├── auth/                           # login.php, logout.php
│   └── config/                         # Bloqueado por Nginx
│       ├── conexion.php                # PDO: lee variables de entorno
│       ├── seguridad.php               # Cabeceras HTTP + CSRF
│       └── auditoria.php               # registrarAuditoria() tolerante a fallos
├── docker-compose.yml
└── README.md
```

---

## Base de datos — esquema

| Tabla | Descripción | Relaciones principales |
|-------|-------------|------------------------|
| `usuarios` | Cuentas con rol (`usuario`/`administrador`) y hash Argon2id | — |
| `contactos` | Clientes/contactos del CRM | → `usuarios` |
| `leads` | Prospectos con estado y origen | → `usuarios`, → `contactos` |
| `oportunidades` | Negociaciones por etapas | → `contactos`, `leads`, `usuarios` |
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

## Documentación ampliada

- **Arquitectura, estructura y comandos**: [`implementaciones/README.md`](implementaciones/README.md)
- **MVP, historias de usuario y criterios de aceptación**: [`implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md`](implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md)
- **Seguimiento y fases**: [`implementaciones/SEGUIMIENTO.md`](implementaciones/SEGUIMIENTO.md)
- **Contratos de API**: [`implementaciones/CONTRATOS_API.md`](implementaciones/CONTRATOS_API.md)
- **Validaciones**: [`implementaciones/VALIDACIONES.md`](implementaciones/VALIDACIONES.md)
- **Seguridad CSRF/XSS**: [`implementaciones/SEGURIDAD_CSRF_XSS.md`](implementaciones/SEGURIDAD_CSRF_XSS.md)
