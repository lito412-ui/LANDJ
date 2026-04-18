# L&J — Panel de control web y proyecto

Vista general del repositorio: aplicación web tipo panel de control (landing, login y panel CRM), backend en PHP con MySQL, servida con Nginx y PHP-FPM en Docker. La planificación del CRM, la arquitectura detallada y el seguimiento por fases están en `implementaciones/`.

## Qué incluye este proyecto

- **Landing** (`public/index.html`): presentación del proyecto en la raíz del sitio (`/`).
- **Autenticación** (`public/modules/site/login.html` → `public/auth/login.php`): sesión PHP, hash Argon2id, tabla `usuarios` en MySQL.
- **Panel** (`public/admin/cpanel.php` → `public/modules/dashboard/cpanel.php`): el PHP comprueba sesión e incluye el layout modular con partials; estilos en `public/assets/css/dashboard/`, scripts en `public/assets/js/dashboard/`.
- **Perfil de usuario**: vista `#perfil` en el panel con datos reales del usuario (nombre, email, rol, fecha de registro) vía `public/api/get_user.php`.
- **Monitorización**: `public/api/monitorizacion.php` devuelve CPU, RAM y disco reales (usando `wmic` en Windows, `/proc/stat` y `/proc/meminfo` en Linux).
- **Base de datos**: esquema completo en `database/init.sql` (5 tablas del MVP con FKs), semilla desde `database/db.json` mediante `database/seed.php`.
- **Infraestructura**: `docker-compose.yml`, `Dockerfile`, configuración Nginx en `docker/nginx/conf.d/`.

### Rutas de compatibilidad

En la raíz de `public/` existen redirecciones ligeras para URLs cortas:

- `public/login.html` → redirige a `/modules/site/login.html`
- `public/cpanel.html` → redirige a `/admin/cpanel.php`

## Stack

| Capa       | Tecnología                        |
|------------|-----------------------------------|
| Frontend   | HTML, CSS, JavaScript (vanilla)   |
| Backend    | PHP 8.3-FPM, PDO + MySQL          |
| Servidor   | Nginx Alpine                      |
| Datos      | MySQL 8.4                         |
| Entorno    | Docker Compose                    |

## Estructura principal del código (activa)

El documento raíz servido por Nginx es `public/` (mapeado como `/usr/share/nginx/html/public` en el contenedor).

```text
LANDJ/
├── database/
│   ├── init.sql          # 5 tablas: usuarios, contactos, leads, oportunidades, actividades
│   ├── db.json           # usuarios de ejemplo para el seed
│   └── seed.php          # inserta/actualiza datos de prueba en todas las tablas
├── docker/
│   └── nginx/conf.d/default.conf
├── public/
│   ├── index.html
│   ├── login.html        # redirección → /modules/site/login.html
│   ├── cpanel.html       # redirección → /admin/cpanel.php
│   ├── assets/
│   │   ├── css/
│   │   │   ├── site/         # landing (index-style.css) + login (style.css)
│   │   │   └── dashboard/    # panel de control (cpanel-style.css)
│   │   ├── js/
│   │   │   ├── site/         # index-script.js
│   │   │   └── dashboard/    # cpanel-script.js
│   │   └── img/
│   ├── modules/
│   │   ├── site/             # vistas públicas (login.html)
│   │   └── dashboard/
│   │       ├── cpanel.php    # layout principal con includes PHP
│   │       └── partials/
│   │           ├── head.php
│   │           ├── header.php
│   │           ├── sidebar.php
│   │           └── sections/ # 14 secciones independientes (.php)
│   ├── auth/             # login.php, logout.php, registro.php
│   ├── admin/            # cpanel.php (guard), crearusuario.php, reset_admin.php
│   ├── api/              # get_user.php, monitorizacion.php
│   └── config/           # conexion.php (bloqueado por Nginx a acceso directo)
├── docker-compose.yml
├── Dockerfile
└── README.md
```

## Base de datos — esquema del MVP

| Tabla | Descripción | Relaciones |
|-------|-------------|------------|
| `usuarios` | Cuentas con rol y hash Argon2id | — |
| `contactos` | Clientes/contactos del CRM | → `usuarios` |
| `leads` | Prospectos con estado y origen | → `usuarios`, → `contactos` |
| `oportunidades` | Negociaciones en pipeline por etapas | → `contactos`, `leads`, `usuarios` |
| `actividades` | Notas/tareas/llamadas ligadas a entidades | → `contactos`, `leads`, `oportunidades` |

## Usuarios en `database/db.json`

El archivo [`database/db.json`](database/db.json) define usuarios de ejemplo **solo para entornos locales**. El script [`database/seed.php`](database/seed.php) lee el JSON y escribe en MySQL (hash Argon2id). El seed también inserta datos de prueba en `contactos`, `leads`, `oportunidades` y `actividades`.

| Usuario | Contraseña (JSON) | Rol |
|---------|-------------------|-----|
| Samuel  | tuchulito96       | usuario |
| lito412 | lolito412/        | usuario |
| Cuervo  | soyunchulo        | usuario |
| admin   | lolito412/ (seed.php) | administrador |

### Poblar o actualizar la base de datos

```bash
docker compose run --rm seed
```

Si cambias `db.json`, vuelve a ejecutar el comando para actualizar los hashes.

## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y en ejecución.
- Puertos libres: **91**, **3307**, **8082**, **8080**.

## Cómo arrancar el proyecto

```bash
docker compose up -d --build
```

Verificar contenedores:

```bash
docker compose ps
```

### URLs útiles

| Servicio    | URL                                             |
|-------------|-------------------------------------------------|
| Aplicación  | http://localhost:91                             |
| Login       | http://localhost:91/modules/site/login.html     |
| Panel       | http://localhost:91/admin/cpanel.php (requiere sesión) |
| phpMyAdmin  | http://localhost:8082                           |
| cAdvisor    | http://localhost:8080                           |

MySQL expuesto en host: puerto **3307**.

### Parar el entorno

```bash
docker compose down
```

Reset completo de BD (borra volúmenes):

```bash
docker compose down -v
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

### Validar la definición de Compose

```bash
docker compose config
```

## Configuración de base de datos

### Conexión PHP (dentro de Docker)

`public/config/conexion.php` lee variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` con fallbacks alineados con `docker-compose.yml`. Usa PDO con `utf8mb4` y `ERRMODE_EXCEPTION`.

> El host de MySQL desde PHP-FPM es el nombre del servicio `db`, no `127.0.0.1`.

### Conexión externa (fuera de Docker)

```bash
mysql -h 127.0.0.1 -P 3307 -uroot
```

### phpMyAdmin

Disponible en `http://localhost:8082`. Credenciales: `root` sin contraseña (entorno de desarrollo).

### Buenas prácticas

- No subas contraseñas reales a repositorios públicos: usa variables de entorno o `.env` ignorado por Git.
- En producción, crea un usuario MySQL dedicado con permisos mínimos.
- Cambia todas las credenciales antes de cualquier despliegue real.

## Documentación ampliada

- **Arquitectura, estructura y comandos**: [`implementaciones/README.md`](implementaciones/README.md)
- **MVP, historias de usuario y criterios de aceptación**: [`implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md`](implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md)
- **Seguimiento y fases**: [`implementaciones/SEGUIMIENTO.md`](implementaciones/SEGUIMIENTO.md)
