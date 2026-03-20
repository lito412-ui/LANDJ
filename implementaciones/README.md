# Implementaciones CRM - Guia de Proyecto

Este directorio centraliza planificacion, seguimiento y operacion del proyecto.

## Contenido de esta carpeta
- `SEGUIMIENTO.md`: avance global por fases y backlog de mejoras.
- `FASE_01_ANALISIS_PLANIFICACION.md`: alcance y definicion funcional.
- `FASE_02_DISENO_ARQUITECTURA.md`: diseno tecnico y contratos.
- `FASE_03_IMPLEMENTACION_DESARROLLO.md`: checklist de implementacion.
- `FASE_04_PRUEBAS_VALIDACION.md`: checklist de calidad y seguridad.
- `FASE_05_DESPLIEGUE_DOCUMENTACION.md`: checklist de entrega y despliegue.

## Arquitectura actual del proyecto

### Stack y componentes
- Frontend: `HTML`, `CSS`, `JavaScript` (vistas `index.html`, `login.html`, `cpanel.html`).
- Backend: `PHP` (autenticacion, sesion, endpoints simples, monitorizacion).
- Base de datos: `MySQL 8.4`.
- Web server: `Nginx` + `PHP-FPM`.
- Entorno: `Docker Compose`.
- Herramienta de administracion BD: `phpMyAdmin`.

### Servicios Docker
- `web` (Nginx): publica la app en `http://localhost:91`.
- `php` (PHP-FPM): ejecuta scripts PHP.
- `db` (MySQL): persistencia de datos.
- `phpmyadmin`: gestion visual de base de datos en `http://localhost:8082`.
- `cadvisor`: monitorizacion de contenedores en `http://localhost:8080`.

### Flujo de autenticacion (resumen)
1. `login.html` envia credenciales a `login.php`.
2. `login.php` valida usuario contra `usuarios` en MySQL.
3. Si es correcto, crea variables de `$_SESSION` y redirige a `cpanel.html`.
4. `get_user.php` confirma sesion activa para cargar datos del usuario.
5. `logout.php` destruye sesion y redirige a inicio.

### Estructura general (resumen)
- Raiz: vistas, scripts frontend y scripts PHP.
- `backend/`: scripts JS de interaccion de panel.
- `data/`: ficheros auxiliares.
- `nginx/conf.d/default.conf`: configuracion Nginx -> PHP-FPM.
- `docker-compose.yml` y `Dockerfile`: orquestacion y build.

### Estructura de carpetas del proyecto
```text
LANDJ/
├── .git/
├── .vscode/
│   └── settings.json
├── backend/
│   ├── cpanel-script.js
│   ├── index-script.js
│   ├── package.json
│   └── script.js
├── data/
│   ├── auth.js
│   └── db.json
├── implementaciones/
│   ├── README.md
│   ├── SEGUIMIENTO.md
│   ├── FASE_01_ANALISIS_PLANIFICACION.md
│   ├── FASE_02_DISENO_ARQUITECTURA.md
│   ├── FASE_03_IMPLEMENTACION_DESARROLLO.md
│   ├── FASE_04_PRUEBAS_VALIDACION.md
│   └── FASE_05_DESPLIEGUE_DOCUMENTACION.md
├── nginx/
│   └── conf.d/
│       └── default.conf
├── resources/
│   ├── icono web.png
│   ├── logo_proyecto.png
│   └── ocultarpass.png
├── Dockerfile
├── docker-compose.yml
├── wait-for-it.sh
├── install.cmd
├── index.html
├── login.html
├── cpanel.html
├── index-style.css
├── style.css
├── cpanel-style.css
├── index-script.js
├── cpanel-script.js
├── conexion.php
├── login.php
├── logout.php
├── get_user.php
├── cpanel.php
├── monitorizacion.php
├── registro.php
├── crearusuario.php
└── reset_admin.php
```

Nota: existe ademas una carpeta `LANDJ/` dentro de la raiz con una copia historica de parte del frontend y recursos. La carpeta activa para desarrollo actual es la raiz del repositorio.

## Pasos para desplegar en local (Docker)

### Requisitos
- Docker Desktop instalado y en ejecucion.
- Puerto `91`, `3307`, `8082` y `8080` libres.

### Arranque del entorno
```bash
docker compose up -d --build
```

### Verificar servicios
```bash
docker compose ps
```

### URLs de acceso
- App web: `http://localhost:91`
- phpMyAdmin: `http://localhost:8082`
- cAdvisor: `http://localhost:8080`

### Parar entorno
```bash
docker compose down
```

### Parar y borrar volumenes (reset completo de BD)
```bash
docker compose down -v
```

## Comandos utiles (operacion y diagnostico)

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

### Comprobaciones rapidas
```bash
docker compose config
docker compose top
```

## Roles y permisos actuales (implementados)
- `administrador`: acceso administrativo completo (segun datos en BD).
- `usuario`: acceso basico autenticado.
- Usuario no autenticado: bloqueado en endpoints protegidos como `get_user.php` y `monitorizacion.php`.

## Convenciones de seguimiento
- "Hecho" = tarea completada, verificada y documentada.
- Marca tareas con `[x]` en cada fase.
- Si una tarea se bloquea, anota motivo, impacto y decision tomada.

