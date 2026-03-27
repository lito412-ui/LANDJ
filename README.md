# L&J — Panel de control web y proyecto

Vista general del repositorio: aplicación web tipo panel de control (landing, login y panel), backend en PHP con MySQL, servida con Nginx y PHP-FPM en Docker. La planificación del CRM, la arquitectura detallada y el seguimiento por fases están en `implementaciones/`.

## Qué incluye este proyecto

- **Landing** (`index.html`): presentación del proyecto.
- **Autenticación** (`login.html` → `login.php`): sesión PHP y tabla `usuarios` en MySQL.
- **Panel** (`cpanel.html`, scripts en `backend/`): interfaz del panel; parte de las secciones son maquetación o simulación en cliente.
- **Utilidades PHP**: `get_user.php`, `logout.php`, `monitorizacion.php`, `registro.php`, scripts de administración de usuario según necesidad.
- **Infraestructura**: `docker-compose.yml`, `Dockerfile`, configuración Nginx en `nginx/conf.d/`.

## Stack

| Capa        | Tecnología                          |
|------------|--------------------------------------|
| Frontend   | HTML, CSS, JavaScript                |
| Backend    | PHP 8.3 (FPM), PDO + MySQL           |
| Servidor   | Nginx                                |
| Datos      | MySQL 8.4                            |
| Entorno    | Docker Compose                       |

## Usuarios en `data/db.json`

El archivo [`data/db.json`](data/db.json) contiene un listado de ejemplo con identificador, nombre de usuario y contraseña en texto plano (solo adecuado para entornos locales o de prueba).

| ID | Usuario  | Contraseña   |
|----|----------|--------------|
| 1  | Samuel   | tuchulito96  |
| 2  | lito412  | admin412     | ############# Este es el administrador.
| 3  | Cuervo   | soyunchulo   |

El login principal del proyecto, cuando usas PHP y MySQL en Docker, valida contra la tabla **`usuarios`** de la base de datos, no contra este JSON. Revisa si tu flujo concreto lee `db.json` o la base de datos antes de probar credenciales.

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

Los scripts PHP se ejecutan en el servicio PHP-FPM. El **host** de MySQL debe ser el **nombre del servicio** de la base en `docker-compose.yml` (habitualmente `db`), no `localhost`, para que la resolución DNS de Docker funcione.

En `conexion.php` suele usarse PDO con opciones como:

- `PDO::ATTR_ERRMODE` = excepciones
- `PDO::ATTR_DEFAULT_FETCH_MODE` = array asociativo
- `PDO::ATTR_EMULATE_PREPARES` = desactivado (consultas preparadas reales)

La cadena DSN típica: `mysql:host=<servicio_db>;dbname=<nombre_bd>;charset=utf8mb4`.

### Desde tu máquina (fuera de Docker)

Si el puerto de MySQL está publicado en el host (p. ej. `3307:3306`), puedes conectar con un cliente gráfico (DBeaver, TablePlus, MySQL Workbench) o por línea de comandos:

```bash
mysql -h 127.0.0.1 -P 3307 -uroot -p
```

Usa el mismo usuario/contraseña que en Compose. Comprueba el puerto exacto en `docker-compose.yml` bajo el servicio `db` → `ports`.

### phpMyAdmin

Si el servicio está activo, la interfaz web suele estar en `http://localhost:8082` (o el puerto que indique tu `docker-compose.yml`). El host que debe usar phpMyAdmin para llegar a MySQL es el nombre del servicio de base de datos (p. ej. `PMA_HOST=db`).

### Tablas y usuarios de aplicación

- El login (`login.php`) consulta la tabla **`usuarios`** (campos como identificador, nombre, hash de contraseña, rol).
- Scripts auxiliares como `crearusuario.php` o `reset_admin.php` (si existen en tu copia del repo) sirven para poblar o recuperar un administrador: **solo en entorno local/controlado** y rotando credenciales antes de cualquier despliegue real.

### Buenas prácticas

- No subas a repositorios públicos contraseñas reales: usa variables de entorno o un `.env` ignorado por Git.
- En producción, crea un usuario MySQL dedicado con permisos mínimos, no uses `root` desde la aplicación.
- Cambia `MYSQL_ROOT_PASSWORD` y las credenciales de la app respecto a los valores de ejemplo del desarrollo.

## Documentación ampliada

- **Arquitectura, estructura de carpetas, despliegue y comandos útiles**: [`implementaciones/README.md`](implementaciones/README.md)
- **Seguimiento y fases del CRM**: [`implementaciones/SEGUIMIENTO.md`](implementaciones/SEGUIMIENTO.md)

## Nota sobre la carpeta `LANDJ/`

Dentro del repositorio puede existir una subcarpeta `LANDJ/` con copia de parte del frontend. El desarrollo activo y el arranque con Docker se asumen desde la **raíz del repositorio** (donde está este `README.md` y `docker-compose.yml`).
