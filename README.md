# L&J — CRM Web

Panel de control CRM (landing, login y panel), backend PHP con MySQL, servido con Nginx y PHP-FPM en Docker. La planificación, arquitectura y seguimiento por fases están en `implementaciones/`.

## Qué incluye este proyecto

- **Landing** (`public/index.html`): página de presentación en la raíz del sitio (`/`).
- **Autenticación** (`public/modules/site/login.html` → `public/auth/login.php`): sesión PHP, hash Argon2id, tabla `usuarios`.
- **Panel CRM** (`public/modules/dashboard/cpanel.php`): layout modular con partials PHP; activa las secciones vía JS (`cpanel-script.js`). Incluye: Contactos, Leads, Gestión de usuarios, Perfil, Auditoría, Monitorización.
- **Perfil de usuario**: vista `#perfil` con datos reales (nombre, email, rol, fecha de registro) vía `GET /api/get_user.php`.
- **Monitorización**: `GET /api/monitorizacion.php` devuelve CPU, RAM y disco reales.
- **CRM — Contactos**: CRUD completo con búsqueda debounced, detalle lateral, validaciones JS + PHP.
- **CRM — Leads**: CRUD completo con búsqueda debounced, filtro por estado, panel de detalle y conversión de lead a contacto (transacción atómica).
- **Gestión de usuarios** (solo admin): CRUD de cuentas con roles, badges, protección anti-autoborrado y protección del último administrador.
- **Auditoría**: log de cambios en todas las entidades, accesible solo para administradores.
- **Seguridad**: CSRF Synchronizer Token + Custom Request Header (`X-CSRF-Token`), Content Security Policy, `X-Frame-Options`, `Referrer-Policy`.
- **Base de datos**: esquema en `database/init.sql` (6 tablas con FKs); datos de prueba vía sistema de migraciones (`database/migrations/`).

## Stack

| Capa | Tecnología |
|------|------------|
| Frontend | HTML, CSS, JavaScript (vanilla) |
| Backend | PHP 8.3-FPM, PDO + MySQL |
| Servidor | Nginx Alpine |
| Datos | MySQL 8.4 |
| Entorno | Docker Compose |

## Estructura principal del código

El directorio servido por Nginx es `public/` → `/usr/share/nginx/html/public` en el contenedor.

```text
LANDJ/
├── database/
│   ├── init.sql                   # Esquema completo: 6 tablas (auditoria + 5 del CRM)
│   ├── migrate.php                # Runner de migraciones (crea _migraciones, aplica .sql y .php)
│   └── migrations/
│       ├── 001_auditoria.sql         # CREATE TABLE auditoria (idempotente)
│       ├── 002_leads_contacto_id.php # Añade columna contacto_id + FK a leads (idempotente)
│       ├── 002_leads_contacto_id.sql # No-op (placeholder para el runner)
│       ├── 003_seed_usuarios.php     # Seed: 1 admin + 3 usuarios (Argon2id)
│       └── 004_seed_crm.sql          # Seed: contactos, leads, oportunidades, actividades
├── docker/
│   └── nginx/conf.d/default.conf  # try_files $uri =404 antes de fastcgi_pass
├── public/
│   ├── index.html
│   ├── login.html                 # Redirección → /modules/site/login.html
│   ├── assets/
│   │   ├── css/
│   │   │   ├── site/              # index-style.css, login style.css
│   │   │   └── dashboard/         # cpanel-style.css
│   │   ├── js/
│   │   │   ├── site/              # index-script.js
│   │   │   └── dashboard/         # cpanel-script.js (módulos: Contactos, Leads, Usuarios)
│   │   └── img/
│   ├── modules/
│   │   ├── site/                  # login.html
│   │   └── dashboard/
│   │       ├── cpanel.php         # Layout principal (ob_start, session, CSRF, includes)
│   │       └── partials/
│   │           ├── head.php       # <head> con meta csrf-token
│   │           ├── header.php
│   │           ├── sidebar.php
│   │           └── sections/      # Una sección PHP por módulo del panel
│   │               ├── contactos.php
│   │               ├── leads.php
│   │               ├── users.php
│   │               └── ...
│   ├── auth/                      # login.php, logout.php
│   ├── api/
│   │   ├── get_user.php           # GET: datos del usuario autenticado
│   │   ├── monitorizacion.php     # GET: CPU / RAM / Disco (solo admin)
│   │   ├── contactos.php          # CRUD REST (sesión activa)
│   │   ├── leads.php              # CRUD REST (sesión activa)
│   │   ├── usuarios.php           # CRUD REST (solo administrador)
│   │   └── auditoria.php          # GET paginado (solo administrador)
│   └── config/                    # Bloqueado por Nginx (deny all)
│       ├── conexion.php           # PDO: lee variables de entorno Docker
│       ├── seguridad.php          # Cabeceras HTTP, CSRF (generar/validar/meta)
│       └── auditoria.php          # Helper registrarAuditoria() tolerante a fallos
├── docker-compose.yml
├── Dockerfile
└── README.md
```

## Base de datos — esquema del MVP

| Tabla | Descripción | Relaciones |
|-------|-------------|------------|
| `auditoria` | Log de cambios: tabla, registro, acción, usuario, JSON antes/después | → `usuarios` (SET NULL) |
| `usuarios` | Cuentas con rol (`usuario`/`administrador`) y hash Argon2id | — |
| `contactos` | Clientes/contactos del CRM | → `usuarios` |
| `leads` | Prospectos con estado y origen | → `usuarios`, → `contactos` |
| `oportunidades` | Negociaciones por etapas | → `contactos`, `leads`, `usuarios` |
| `actividades` | Notas/tareas/llamadas ligadas a entidades | → `contactos`, `leads`, `oportunidades` |

## Usuarios de prueba

Definidos en `database/migrations/002_seed_usuarios.php`. Solo para entornos locales.

| Usuario | Contraseña | Rol |
|---------|-----------|-----|
| admin | lolito412/ | administrador |
| Samuel | tuchulito96 | usuario |
| lito412 | lolito412/ | usuario |
| Cuervo | soyunchulo | usuario |

> Las contraseñas se almacenan como hash Argon2id. Nunca se guardan en texto plano.

## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y en ejecución.
- Puertos libres: **91** (app), **3307** (MySQL), **8082** (phpMyAdmin), **8080** (cAdvisor).

## Cómo arrancar el proyecto

```bash
docker compose up -d --build
```

Al arrancar, el servicio `migrate` ejecuta automáticamente las migraciones pendientes (crea la tabla `auditoria` e inserta los datos de prueba). Se puede relanzar manualmente:

```bash
docker compose run --rm migrate
```

Verificar contenedores:

```bash
docker compose ps
```

### URLs útiles

| Servicio | URL |
|----------|-----|
| Aplicación | http://localhost:91 |
| Login | http://localhost:91/modules/site/login.html |
| Panel | http://localhost:91/modules/dashboard/cpanel.php |
| phpMyAdmin | http://localhost:8082 |
| cAdvisor | http://localhost:8080 |

MySQL expuesto en el host: puerto **3307**.

### Parar el entorno

```bash
docker compose down
```

Reset completo de BD (borra volúmenes, útil si hay datos inconsistentes):

```bash
docker compose down -v && docker compose up -d --build
```

## Comandos útiles (Docker)

### Logs y depuración

```bash
docker compose logs -f
docker compose logs -f web
docker compose logs -f php
docker compose logs -f db
```

### Estado, reinicio y reconstrucción

```bash
docker compose ps
docker compose restart
docker compose restart php
docker compose up -d --build
docker compose build --no-cache php
```

### Entrar a un contenedor

```bash
docker compose exec php sh
docker compose exec db sh
```

### Cliente MySQL desde el contenedor

```bash
docker compose exec db mysql -uroot
```

## Configuración de base de datos

### Conexión PHP (dentro de Docker)

`public/config/conexion.php` lee las variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` con fallbacks alineados con `docker-compose.yml`. Usa PDO con `utf8mb4` y `ERRMODE_EXCEPTION`.

> El host de MySQL desde PHP-FPM es el nombre del servicio `db`, no `127.0.0.1`.

### Conexión externa (fuera de Docker)

```bash
mysql -h 127.0.0.1 -P 3307 -uroot
```

### phpMyAdmin

Disponible en `http://localhost:8082`. Credenciales: `root` sin contraseña (entorno de desarrollo).

### Buenas prácticas

- No subas contraseñas reales a repositorios públicos.
- En producción, crea un usuario MySQL dedicado con permisos mínimos.
- Cambia todas las credenciales antes de cualquier despliegue real.

## Documentación ampliada

- **Arquitectura, estructura y comandos**: [`implementaciones/README.md`](implementaciones/README.md)
- **MVP, historias de usuario y criterios de aceptación**: [`implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md`](implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md)
- **Seguimiento y fases**: [`implementaciones/SEGUIMIENTO.md`](implementaciones/SEGUIMIENTO.md)
- **Contratos de API**: [`implementaciones/CONTRATOS_API.md`](implementaciones/CONTRATOS_API.md)
- **Validaciones**: [`implementaciones/VALIDACIONES.md`](implementaciones/VALIDACIONES.md)
- **Seguridad CSRF/XSS**: [`implementaciones/SEGURIDAD_CSRF_XSS.md`](implementaciones/SEGURIDAD_CSRF_XSS.md)
