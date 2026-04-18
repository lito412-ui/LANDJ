# L&J — Panel de control web y proyecto

Vista general del repositorio: aplicación web tipo panel de control (landing, login y panel), backend en PHP con MySQL, servida con Nginx y PHP-FPM en Docker. La planificación del CRM, la arquitectura detallada y el seguimiento por fases están en `implementaciones/`.

## Qué incluye este proyecto

- **Landing** (`public/index.html`): presentación del proyecto en la raíz del sitio (`/`).
- **Autenticación** (`public/modules/site/login.html` → `public/auth/login.php`): sesión PHP y tabla `usuarios` en MySQL.
- **Panel** (`public/admin/cpanel.php` + vista `public/modules/dashboard/cpanel.html`): el PHP comprueba sesión y sirve el HTML; los estilos y scripts están en `public/assets/`.
- **Utilidades PHP**: `public/api/get_user.php`, `public/auth/logout.php`, `public/api/monitorizacion.php`, `public/auth/registro.php`, scripts en `public/admin/` según necesidad.
- **Datos**: esquema en `database/init.sql`, semilla desde `database/db.json` mediante `database/seed.php` (ver más abajo).
- **Infraestructura**: `docker-compose.yml`, `Dockerfile`, configuración Nginx en `docker/nginx/conf.d/`.

### Rutas de compatibilidad

En la raíz de `public/` existen redirecciones ligeras para enlaces antiguos:

- `public/login.html` → redirige a `/modules/site/login.html`
- `public/cpanel.html` → redirige a `/admin/cpanel.php`

## Stack

| Capa        | Tecnología                          |
|------------|--------------------------------------|
| Frontend   | HTML, CSS, JavaScript                |
| Backend    | PHP 8.3 (FPM), PDO + MySQL           |
| Servidor   | Nginx                                |
| Datos      | MySQL 8.4                            |
| Entorno    | Docker Compose                       |

## Estructura principal del código (activa)

El documento raíz servido por Nginx es `public/` (mapeado como `/usr/share/nginx/html/public` en el contenedor).

```text
LANDJ/
├── database/
│   ├── init.sql          # creación tabla usuarios (entre otros)
│   ├── db.json           # usuarios de ejemplo para el seed
│   └── seed.php          # inserta/actualiza usuarios en MySQL desde db.json
├── docker/
│   └── nginx/conf.d/default.conf
├── public/
│   ├── index.html
│   ├── login.html        # redirección al login modular
│   ├── cpanel.html       # redirección al panel protegido
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   ├── modules/
│   │   ├── site/         # vistas públicas (login)
│   │   └── dashboard/    # vista HTML del panel (servida por admin/cpanel.php)
│   ├── auth/             # login, logout, registro
│   ├── admin/            # cpanel.php, crearusuario, reset_admin
│   ├── api/              # get_user, monitorizacion
│   └── config/           # conexion.php (bloqueado por Nginx a acceso directo)
├── docker-compose.yml
├── Dockerfile
└── README.md
```

Existe además una carpeta **`LANDJ/`** en el repositorio con una copia histórica de parte del frontend; el desarrollo activo y Docker usan la **raíz del repo** (este `README.md` y `docker-compose.yml`).

## Usuarios en `database/db.json`

El archivo [`database/db.json`](database/db.json) define usuarios de ejemplo (nombre de usuario y contraseña en texto plano) **solo para entornos locales**. El script [`database/seed.php`](database/seed.php) lee este JSON y escribe en la tabla **`usuarios`** de MySQL (hash con Argon2id). El login de la aplicación **siempre valida contra MySQL**, no contra el JSON en tiempo real.

| ID | Usuario  | Contraseña (ejemplo en JSON) |
|----|----------|------------------------------|
| 1  | Samuel   | tuchulito96                  |
| 2  | lito412  | lolito412/                   |
| 3  | Cuervo   | soyunchulo                   |

Tras el seed también puede existir un usuario **`admin`** (contraseña definida en `seed.php`, p. ej. `lolito412/`). Ajusta credenciales antes de cualquier despliegue real.

### Poblar o actualizar usuarios en la base de datos

Con los contenedores en marcha y MySQL saludable:

```bash
docker compose run --rm seed
```

O, si usas el servicio `seed` definido en `docker-compose.yml`, se puede ejecutar en el primer arranque según tu configuración. Si cambias `db.json`, vuelve a ejecutar el comando anterior para aplicar contraseñas actualizadas a filas ya existentes.

## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado y en ejecución.
- Puertos libres según `docker-compose.yml` (por defecto: **91**, **3307**, **8082**, **8080**).

## Cómo arrancar el proyecto (recomendado)

Desde la raíz del repositorio (esta carpeta):

```bash
docker compose up -d --build
```

Comprobar que los contenedores están en marcha:

```bash
docker compose ps
```

### URLs útiles

| Servicio    | URL por defecto              |
|------------|------------------------------|
| Aplicación | http://localhost:91          |
| Login      | http://localhost:91/modules/site/login.html |
| Panel      | http://localhost:91/admin/cpanel.php (requiere sesión) |
| phpMyAdmin | http://localhost:8082        |
| cAdvisor   | http://localhost:8080        |

La base de datos MySQL queda expuesta en el host en el puerto **3307** (mapeo `3307:3306`).

### Parar el entorno

```bash
docker compose down
```

Para borrar también los volúmenes de datos (reset de la BD):

```bash
docker compose down -v
```

## Comandos útiles (Docker)

Sustituye el nombre del servicio si tu `docker-compose.yml` usa otros (`web`, `php`, `db`, etc.).

### Logs y depuración

```bash
docker compose logs -f
docker compose logs -f --tail=100 web
docker compose logs -f php
docker compose logs -f db
```

### Estado, reinicio y reconstrucción

```bash
docker compose ps
docker compose top
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

### Cliente MySQL desde el contenedor de la base de datos

```bash
docker compose exec db mysql -uroot -p
```

La contraseña es la misma que `MYSQL_ROOT_PASSWORD` en `docker-compose.yml`.

### Validar la definición de Compose

```bash
docker compose config
```

### Uso de recursos (opcional)

```bash
docker stats
```

### Limpieza (con cuidado)

```bash
docker compose down
docker system prune -f
```

`docker system prune` puede borrar imágenes no usadas; úsalo solo si entiendes el impacto.

## Configuración de base de datos

### Resumen

| Concepto | Valor habitual en este proyecto |
|----------|----------------------------------|
| Motor | MySQL 8.x (imagen `mysql:8.4` en Compose) |
| Base de datos creada al arrancar | Definida en `MYSQL_DATABASE` (p. ej. `mi_proyecto_db`) |
| Usuario root | `root` (contraseña = `MYSQL_ROOT_PASSWORD` en Compose) |
| Charset recomendado | `utf8mb4` |
| Volumen de datos | Volumen nombrado en Compose (persistencia entre reinicios) |

### Desde PHP (dentro de la red Docker)

Los scripts PHP se ejecutan en el servicio PHP-FPM. El **host** de MySQL debe ser el **nombre del servicio** de la base en `docker-compose.yml` (habitualmente `db`), no `127.0.0.1`.

En `public/config/conexion.php` se leen variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` (con valores por defecto alineados con `docker-compose.yml` en el servicio `php`). La cadena DSN usa PDO con `utf8mb4`.

### Desde tu máquina (fuera de Docker)

Si el puerto de MySQL está publicado en el host (p. ej. `3307:3306`), puedes conectar con un cliente gráfico o:

```bash
mysql -h 127.0.0.1 -P 3307 -uroot -p
```

Usa el mismo usuario/contraseña que en Compose. Comprueba el puerto exacto en `docker-compose.yml` bajo el servicio `db` → `ports`.

### phpMyAdmin

Si el servicio está activo, la interfaz web suele estar en `http://localhost:8082`. Debe apuntar al host MySQL interno (`PMA_HOST=db` en Compose). Las credenciales de acceso deben coincidir con las de MySQL (`PMA_USER` / `PMA_PASSWORD` o usuario que configures).

### Tablas y usuarios de aplicación

- El login (`public/auth/login.php`) consulta la tabla **`usuarios`**. La columna del hash de contraseña se documenta como **`contraseña_hash`** (debe coincidir con el esquema real de tu base de datos).
- Scripts auxiliares en `public/admin/` sirven para poblar o recuperar un administrador: **solo en entorno local/controlado** y rotando credenciales antes de cualquier despliegue real.

### Buenas prácticas

- No subas a repositorios públicos contraseñas reales: usa variables de entorno o un `.env` ignorado por Git.
- En producción, crea un usuario MySQL dedicado con permisos mínimos; no uses `root` desde la aplicación.
- Cambia `MYSQL_ROOT_PASSWORD` y las credenciales de la app respecto a los valores de ejemplo del desarrollo.

## Documentación ampliada

- **Arquitectura, estructura de carpetas, despliegue y comandos útiles**: [`implementaciones/README.md`](implementaciones/README.md)
- **MVP, historias de usuario y criterios de aceptación (Fase 01)**: [`implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md`](implementaciones/MVP_HISTORIAS_Y_CRITERIOS.md)
- **Seguimiento y fases del CRM**: [`implementaciones/SEGUIMIENTO.md`](implementaciones/SEGUIMIENTO.md)
