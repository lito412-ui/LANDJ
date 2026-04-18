# Implementaciones CRM - Guía de proyecto

Este directorio centraliza planificación, seguimiento y operación del proyecto.

## Contenido de esta carpeta

- `SEGUIMIENTO.md`: avance global por fases y backlog de mejoras.
- `MVP_HISTORIAS_Y_CRITERIOS.md`: epics del MVP, criterios Given/When/Then, matriz de roles y RNF (cierre Fase 01).
- `FASE_01_ANALISIS_PLANIFICACION.md`: alcance y definición funcional.
- `FASE_02_DISENO_ARQUITECTURA.md`: diseño técnico, modelo de datos y contratos.
- `FASE_03_IMPLEMENTACION_DESARROLLO.md`: checklist de implementación.
- `FASE_04_PRUEBAS_VALIDACION.md`: checklist de calidad y seguridad.
- `FASE_05_DESPLIEGUE_DOCUMENTACION.md`: checklist de entrega y despliegue.

## Arquitectura actual del proyecto

### Stack y componentes

- **Frontend**: HTML, CSS, JavaScript vanilla (landing en `public/index.html`, login en `public/modules/site/login.html`, panel en `public/modules/dashboard/cpanel.php`).
- **Backend**: PHP 8.3-FPM (autenticación, sesión, API endpoints, monitorización).
- **Base de datos**: MySQL 8.4 — 5 tablas: `usuarios`, `contactos`, `leads`, `oportunidades`, `actividades`.
- **Servidor web**: Nginx Alpine + PHP-FPM.
- **Entorno**: Docker Compose.
- **Herramientas**: phpMyAdmin (BD visual), cAdvisor (métricas de contenedores).

### Servicios Docker

| Servicio | Imagen | Puerto host | Descripción |
|----------|--------|-------------|-------------|
| `web` | nginx:alpine | 91 | Sirve `public/`; raíz en `http://localhost:91` |
| `php` | php:8.3-fpm-alpine | — | Ejecuta PHP-FPM; variables `DB_*` para MySQL |
| `db` | mysql:8.4 | 3307 | Inicializado con `database/init.sql` |
| `seed` | (build local) | — | Ejecuta `database/seed.php` una vez al arrancar |
| `phpmyadmin` | phpmyadmin:latest | 8082 | Gestión visual de BD |
| `cadvisor` | gcr.io/cadvisor/cadvisor | 8080 | Monitorización de contenedores |

### Flujo de autenticación

1. El usuario abre `public/modules/site/login.html`.
2. El formulario envía credenciales por POST a `public/auth/login.php`.
3. `login.php` valida contra la tabla `usuarios` en MySQL con `password_verify` (Argon2id).
4. Si es correcto, se crean `$_SESSION['user_id']`, `$_SESSION['nombre']`, `$_SESSION['rol']` y se redirige a `public/admin/cpanel.php`.
5. `admin/cpanel.php` comprueba sesión e incluye `public/modules/dashboard/cpanel.php` (layout con partials).
6. `public/api/get_user.php` devuelve sesión activa en JSON (`nombre`, `rol`, `email`, `created_at`).
7. `public/auth/logout.php` destruye la sesión y redirige al login.

### Assets estáticos — estructura por dominio

```text
public/assets/
├── css/
│   ├── site/           # Landing (index-style.css) y login (style.css)
│   └── dashboard/      # Panel de control (cpanel-style.css)
├── js/
│   ├── site/           # Landing (index-script.js)
│   └── dashboard/      # Panel (cpanel-script.js)
└── img/                # Logos e iconos
```

### Panel de control — estructura modular

```text
public/modules/dashboard/
├── cpanel.php              # Layout principal: incluye todos los partials
└── partials/
    ├── head.php            # <head>: meta, CSS, fuentes
    ├── header.php          # Cabecera: logo, bienvenida, menú de usuario
    ├── sidebar.php         # Navegación lateral
    └── sections/           # Una sección por módulo del panel
        ├── dashboard.php   # Métricas, acciones rápidas, actividad reciente
        ├── perfil.php      # Perfil del usuario autenticado
        ├── ftp.php
        ├── ssl.php
        ├── statistics.php
        ├── file-manager.php
        ├── databases.php
        ├── backups.php
        ├── security.php
        ├── firewall.php
        ├── email.php
        ├── domains.php
        ├── users.php
        └── logs.php
```

### Esquema de base de datos

```text
usuarios         ← base de cuentas con rol y hash Argon2id
contactos        → FK usuarios (creado_por)
leads            → FK usuarios, FK contactos (si convertido)
oportunidades    → FK contactos, FK leads, FK usuarios (asignado_a, creado_por)
actividades      → FK contactos, FK leads, FK oportunidades
```

Esquema completo: [`database/init.sql`](../database/init.sql).  
Datos de prueba: [`database/seed.php`](../database/seed.php) (lee [`database/db.json`](../database/db.json) y puebla todas las tablas).

### Estructura general del repositorio

```text
LANDJ/
├── database/
│   ├── init.sql                # Esquema completo del MVP (5 tablas)
│   ├── db.json                 # Usuarios de ejemplo (contraseñas en texto plano, solo local)
│   └── seed.php                # Seed completo: usuarios, contactos, leads, oportunidades, actividades
├── docker/
│   └── nginx/conf.d/default.conf
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
│   ├── login.html              # Redirección → /modules/site/login.html
│   ├── cpanel.html             # Redirección → /admin/cpanel.php
│   ├── assets/
│   │   ├── css/site/           # index-style.css, style.css
│   │   ├── css/dashboard/      # cpanel-style.css
│   │   ├── js/site/            # index-script.js
│   │   ├── js/dashboard/       # cpanel-script.js
│   │   └── img/
│   ├── modules/
│   │   ├── site/login.html
│   │   └── dashboard/
│   │       ├── cpanel.php
│   │       └── partials/...
│   ├── auth/                   # login.php, logout.php, registro.php
│   ├── admin/                  # cpanel.php (guard), crearusuario.php, reset_admin.php
│   ├── api/                    # get_user.php, monitorizacion.php
│   └── config/conexion.php     # Bloqueado por Nginx ante peticiones directas
├── Dockerfile
├── docker-compose.yml
├── wait-for-it.sh
└── install.cmd
```

## Pasos para desplegar en local (Docker)

### Requisitos
- Docker Desktop instalado y en ejecución.
- Puertos `91`, `3307`, `8082` y `8080` libres.

### Arranque

```bash
docker compose up -d --build
```

### Poblar datos de prueba

```bash
docker compose run --rm seed
```

### Verificar servicios

```bash
docker compose ps
```

### URLs de acceso

| Servicio | URL |
|----------|-----|
| App web | http://localhost:91 |
| Login | http://localhost:91/modules/site/login.html |
| Panel (con sesión) | http://localhost:91/admin/cpanel.php |
| phpMyAdmin | http://localhost:8082 |
| cAdvisor | http://localhost:8080 |

### Parar entorno

```bash
docker compose down
```

### Reset completo de BD (borra volúmenes)

```bash
docker compose down -v
```

## Comandos útiles

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

# Acceso a contenedores
docker compose exec php sh
docker compose exec db mysql -uroot

# Validar config
docker compose config
```

## Roles y permisos actuales

| Rol | Acceso |
|-----|--------|
| No autenticado | Solo landing y login |
| `usuario` | Panel completo, CRUD según módulo |
| `administrador` | Panel + gestión de usuarios del sistema |

Endpoints protegidos (`api/get_user.php`, `api/monitorizacion.php`, `admin/cpanel.php`) comprueban `$_SESSION['user_id']` antes de responder.

## Convenciones de seguimiento

- "Hecho" = tarea completada, verificada y documentada.
- Marca tareas con `[x]` en cada fase del `SEGUIMIENTO.md`.
- Si una tarea se bloquea, anota motivo, impacto y decisión tomada.
