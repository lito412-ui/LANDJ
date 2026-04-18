# Implementaciones CRM - Guía de proyecto

Este directorio centraliza planificación, seguimiento y operación del proyecto.

## Contenido de esta carpeta

- `SEGUIMIENTO.md`: avance global por fases y backlog de mejoras.
- `MVP_HISTORIAS_Y_CRITERIOS.md`: epics del MVP, criterios Given/When/Then, matriz de roles y RNF (cierre Fase 01).
- `FASE_01_ANALISIS_PLANIFICACION.md`: alcance y definición funcional.
- `FASE_02_DISENO_ARQUITECTURA.md`: diseño técnico y contratos.
- `FASE_03_IMPLEMENTACION_DESARROLLO.md`: checklist de implementación.
- `FASE_04_PRUEBAS_VALIDACION.md`: checklist de calidad y seguridad.
- `FASE_05_DESPLIEGUE_DOCUMENTACION.md`: checklist de entrega y despliegue.

## Arquitectura actual del proyecto

### Stack y componentes

- **Frontend**: HTML, CSS, JavaScript (vistas bajo `public/modules/`, landing en `public/index.html`).
- **Backend**: PHP (autenticación, sesión, endpoints, monitorización).
- **Base de datos**: MySQL 8.4.
- **Servidor web**: Nginx + PHP-FPM.
- **Entorno**: Docker Compose.
- **Herramienta de administración BD**: phpMyAdmin.

### Servicios Docker (típico en este repo)

- `web` (Nginx): documento raíz `public/`; aplicación en `http://localhost:91`.
- `php` (PHP-FPM): ejecuta scripts PHP; variables `DB_*` para conexión a MySQL.
- `db` (MySQL): persistencia de datos; inicialización con `database/init.sql`.
- `seed` (opcional): ejecuta `database/seed.php` para cargar usuarios desde `database/db.json`.
- `phpmyadmin`: gestión visual de base de datos en `http://localhost:8082`.
- `cadvisor`: monitorización de contenedores en `http://localhost:8080`.

### Flujo de autenticación (resumen)

1. El usuario abre `public/modules/site/login.html` (o la redirección `public/login.html`).
2. El formulario envía credenciales por POST a `public/auth/login.php`.
3. `login.php` valida contra la tabla `usuarios` en MySQL (columna de hash, p. ej. `contraseña_hash`).
4. Si es correcto, se crean variables de `$_SESSION` y se redirige a `public/admin/cpanel.php`.
5. `cpanel.php` comprueba sesión y hace `readfile` de `public/modules/dashboard/cpanel.html`.
6. `public/api/get_user.php` confirma sesión activa para el cliente (JSON).
7. `public/auth/logout.php` destruye la sesión y redirige al login modular.

### Recursos estáticos

- CSS, JS e imágenes viven en **`public/assets/`** (`/assets/css/`, `/assets/js/`, `/assets/img/` en la URL).

### Estructura general (activa)

- **Raíz del repo**: `docker-compose.yml`, `Dockerfile`, `database/`, `docker/nginx/`.
- **`public/`**: único árbol servido por Nginx como aplicación web.
- **`public/config/conexion.php`**: PDO; bloqueado por Nginx ante peticiones directas a `/config/`.
- **`database/`**: SQL de creación, `db.json` de ejemplo y `seed.php` para poblar MySQL.

### Estructura de carpetas del proyecto (resumen)

```text
LANDJ/
├── database/
│   ├── init.sql
│   ├── db.json
│   └── seed.php
├── docker/
│   └── nginx/
│       └── conf.d/
│           └── default.conf
├── implementaciones/
│   ├── README.md
│   ├── SEGUIMIENTO.md
│   ├── FASE_01_ANALISIS_PLANIFICACION.md
│   ├── FASE_02_DISENO_ARQUITECTURA.md
│   ├── FASE_03_IMPLEMENTACION_DESARROLLO.md
│   ├── FASE_04_PRUEBAS_VALIDACION.md
│   └── FASE_05_DESPLIEGUE_DOCUMENTACION.md
├── public/
│   ├── index.html
│   ├── login.html              # redirección → /modules/site/login.html
│   ├── cpanel.html             # redirección → /admin/cpanel.php
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   ├── modules/
│   │   ├── site/
│   │   │   └── login.html
│   │   └── dashboard/
│   │       └── cpanel.html
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── registro.php
│   ├── admin/
│   │   ├── cpanel.php
│   │   ├── crearusuario.php
│   │   └── reset_admin.php
│   ├── api/
│   │   ├── get_user.php
│   │   └── monitorizacion.php
│   └── config/
│       └── conexion.php
├── Dockerfile
├── docker-compose.yml
├── wait-for-it.sh
└── install.cmd
```

**Nota:** puede existir una subcarpeta `LANDJ/` dentro de la raíz con copia histórica de parte del frontend. La carpeta activa para desarrollo y Docker es la **raíz del repositorio** (donde está `docker-compose.yml`).

## Pasos para desplegar en local (Docker)

### Requisitos

- Docker Desktop instalado y en ejecución.
- Puertos `91`, `3307`, `8082` y `8080` libres (según tu `docker-compose.yml`).

### Arranque del entorno

```bash
docker compose up -d --build
```

### Poblar usuarios desde `database/db.json` (opcional)

```bash
docker compose run --rm seed
```

### Verificar servicios

```bash
docker compose ps
```

### URLs de acceso

- App web: `http://localhost:91`
- Login: `http://localhost:91/modules/site/login.html`
- Panel (con sesión): `http://localhost:91/admin/cpanel.php`
- phpMyAdmin: `http://localhost:8082`
- cAdvisor: `http://localhost:8080`

### Parar entorno

```bash
docker compose down
```

### Parar y borrar volúmenes (reset completo de BD)

```bash
docker compose down -v
```

## Comandos útiles (operación y diagnóstico)

### Logs

```bash
docker compose logs -f
docker compose logs -f web
docker compose logs -f php
docker compose logs -f db
```

### Estado y reinicio

```bash
docker compose ps
docker compose restart
docker compose restart php
```

### Acceso a contenedores

```bash
docker compose exec php sh
docker compose exec db mysql -uroot -p
```

### Comprobaciones rápidas

```bash
docker compose config
docker compose top
```

## Roles y permisos actuales (implementados)

- `administrador`: acceso administrativo completo (según datos en BD).
- `usuario`: acceso básico autenticado.
- Usuario no autenticado: bloqueado en endpoints protegidos como `get_user.php` y `monitorizacion.php`.

## Convenciones de seguimiento

- "Hecho" = tarea completada, verificada y documentada.
- Marca tareas con `[x]` en cada fase.
- Si una tarea se bloquea, anota motivo, impacto y decisión tomada.
