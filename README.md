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
- **Contactos**: CRUD completo, búsqueda debounced (400 ms), filtros avanzados (empresa, fechas, orden), paginación server-side, detalle lateral con actividades, **exportación/importación CSV**, **detección de duplicados** al crear (por email, bloqueo permanente; por teléfono, aviso forzable).
- **Leads**: CRUD completo, búsqueda, filtros (estado, origen, fechas), paginación, panel de detalle con actividades, conversión de lead a contacto (transacción atómica), **exportación/importación CSV**.
- **Pipeline de Oportunidades**: kanban board con 5 etapas, drag & drop HTML5 entre columnas (mueve directo vía API sin modal), cambio de etapa con confirmación desde el panel de detalle, filtros avanzados (valor, fecha cierre, orden), detalle lateral con actividades, **exportación/importación CSV** (vía email de contacto/lead).
- **Presupuestos**: CRUD completo con líneas y selector de producto/servicio, numeración automática (`PRE-YYYY-0001`), PDF y envío por email, **conversión a factura en un clic** (copia las líneas y enlaza ambos documentos, con protección contra doble conversión o edición posterior), **exportación/importación CSV** y **página pública de confirmación** (el cliente acepta o rechaza el presupuesto desde un enlace del email, sin necesidad de contestar por correo).
- **Productos y servicios**: catálogo con código único, precio, IVA, stock, estado activo/inactivo y **proveedor asociado**; CRUD completo, búsqueda, filtros y **exportación/importación CSV**. Es el origen del selector de concepto en las líneas de factura, presupuesto y factura recurrente.
- **Proveedores**: CRUD completo (nombre, NIF/CIF, email, teléfono, dirección, persona de contacto, notas), búsqueda, filtro activo/inactivo, detalle lateral y **exportación/importación CSV** (upsert por NIF). Cada producto puede vincularse a un proveedor; si se elimina el proveedor, el producto no se pierde (queda sin proveedor asignado).
- **Facturas**: CRUD completo de facturas vinculadas a contactos, numeración automática (`FAC-YYYY-0001`), líneas con cantidad/precio/IVA, **selector de producto/servicio del catálogo que autorrellena concepto/precio/IVA** (el concepto sigue siendo editable para conceptos personalizados), cálculo de base imponible, IVA y total, estados (`borrador`, `emitida`, `pagada`, `vencida`, `cancelada`), PDF y envío por email, filtros avanzados, detalle lateral, **exportación/importación CSV** (una fila por línea, agrupada por número de factura) y **recordatorios automáticos de cobro** para facturas vencidas.
- **Facturas Recurrentes**: plantillas (contacto + líneas + periodicidad mensual/trimestral/anual) que generan una factura real automáticamente cada ciclo, vía el mismo servicio `cron`. Botón "Generar ahora" para forzar un ciclo sin esperar, envío automático por email opcional, historial de facturas generadas por plantilla, y módulo de visibilidad independiente del de Facturas.
- **Actividades**: CRUD de notas, llamadas, reuniones, tareas y emails ligados a contactos, leads y oportunidades. Widget compartido `ActividadesWidget`.
- **Estadísticas**: métricas del pipeline (leads por estado, oportunidades por etapa, actividades por tipo) más un **ranking de comerciales** con oportunidades ganadas, valor ganado, facturas emitidas y total facturado por usuario.

### Facturas recurrentes
- Script `database/tareas/generar_facturas_recurrentes.php`, en el mismo ciclo del servicio Docker `cron` que los recordatorios de cobro.
- Cada plantilla define contacto, líneas, periodicidad y día de generación; `proxima_generacion` avanza sola tras cada ciclo, ajustando el día al último válido del mes destino.
- Respeta `activa` (pausar sin borrar) y `fecha_fin` (opcional).
- Envío automático por email opcional, con el PDF adjunto, reutilizando el mismo motor que las facturas manuales.
- Lógica de generación compartida entre el botón "Generar ahora" y el cron (`public/config/facturas_recurrentes_util.php`), para que ambos calculen totales y numeración exactamente igual.

### Recordatorios automáticos de cobro
- Script `database/tareas/recordatorios_facturas.php`, ejecutado periódicamente por el servicio Docker `cron`.
- Marca como `vencida` toda factura `emitida` cuya fecha de vencimiento ya pasó.
- Envía un email de recordatorio (con el PDF adjunto) al contacto de facturas vencidas sin recordatorio en los últimos 7 días, y crea una **tarea interna** para el comercial responsable.
- Avisa también 3 días antes del vencimiento con una tarea interna (sin email al cliente).
- Las tareas aparecen automáticamente en la campana de notificaciones existente — no requiere frontend nuevo — y el proceso es idempotente (no duplica tareas ni reenvía email en ejecuciones sucesivas).

### Confirmación pública de presupuestos
- Página `public/presupuesto-confirmar.php`, **sin sesión** (la abre el cliente, no un usuario del CRM).
- Al enviar un presupuesto por email se genera un token de 64 caracteres aleatorio (`bin2hex(random_bytes(32))`) y el correo incluye un botón "Ver y confirmar presupuesto" con ese enlace.
- El cliente ve el resumen (líneas, total, validez) y dos botones: **Aceptar** / **Rechazar**. El `GET` nunca cambia nada (evita que un escáner de seguridad de email confirme el presupuesto sin querer); solo el `POST` (clic real) actualiza el estado.
- Protegida contra doble confirmación, presupuestos caducados y presupuestos ya convertidos en factura.
- La URL se construye dinámicamente a partir del host desde el que se accede (`$_SERVER['HTTP_HOST']`), así que funciona igual en `localhost:91` que en un dominio real, sin configuración adicional.
- El enlace también se puede copiar manualmente desde el panel de detalle del presupuesto en el CRM (botón "Copiar"), útil para probar el flujo en local sin depender de que el email llegue.

### Importación / exportación CSV (contactos, leads, oportunidades, productos, proveedores, facturas, presupuestos)
- Botones **Exportar**/**Importar** en la toolbar de los 7 módulos.
- Export: CSV `;` con BOM UTF-8, descarga directa (`GET ?action=exportar`).
- Import: subida `multipart/form-data` (`POST ?action=importar`), hasta 2000 filas, autodetecta `;`/`,`, upsert por clave natural (email o código) donde aplica.
- Las filas inválidas no frenan el resto: se listan en un resumen `{ creados, actualizados, errores, total }`.
- Lógica compartida en `public/config/csv_util.php` (servidor) y `cpanel-core.js` (cliente). Detalle completo de cada endpoint en [`implementaciones/CONTRATOS_API.md`](implementaciones/CONTRATOS_API.md).

### cPanel — Hosting
- **Dominios**: CRUD completo con filtros por tipo (principal/subdominio/addon/parked), estado, IP, SSL toggle; paginación y ordenación.
- **Cuentas de correo**: CRUD completo con cuota (MB/GB/ilimitada), estado, filtros y paginación. El dominio se extrae automáticamente del email.

### Avisos (todos los roles)
- Sección **visible para cualquier usuario**, no solo administradores: cada uno ve su propia bandeja de avisos recibidos, con badge rojo de no leídos en el sidebar.
- Los **administradores** pueden componer y enviar un aviso (título, mensaje, tipo: información/buena noticia/aviso importante/urgente) a **todos los demás usuarios** o a una selección específica, y ver un historial de enviados con el conteo de lectura por aviso.
- Distinto del sistema de "Notificaciones" (recordatorios personales ligados a contactos/leads/oportunidades): un Aviso es un mensaje de un administrador a su equipo, con seguimiento de lectura por destinatario.
- No es un módulo ocultable desde "Módulos Visibles" — es infraestructura de comunicación esencial, igual que el propio Dashboard.

### Administración (solo admin)
- **Gestión de usuarios**: CRUD de cuentas con roles, protección anti-autoborrado y del último administrador.
- **Auditoría**: tabla de cambios con diff expandible (antes/después resaltados), filtros por entidad y acción, paginación offset.
- **Bases de datos**: estadísticas de tablas MySQL en tiempo real — motor, filas, tamaño, colación, última modificación; botón phpMyAdmin.
- **Copias de seguridad**: creación, descarga y eliminación de backups `.sql` completos de la base de datos.
- **Módulos Visibles**: el administrador elige qué secciones del panel ven las cuentas con rol `usuario` (15 módulos ocultables: CRM, correo/dominios, seguridad, archivos...), con guardado instantáneo por toggle. El bloqueo es real de extremo a extremo: además de ocultarse en el sidebar, cada endpoint de un módulo oculto responde `403` aunque se llame directamente sin pasar por el panel. Si se ocultan todos los módulos de una categoría, la cabecera de esa categoría desaparece por completo (sin dejar títulos flotando sin nada debajo), y hay un interruptor maestro para ocultar/mostrar toda una categoría de un solo clic. Los administradores siempre ven y acceden a todo, sin excepción.

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
│   ├── migrations/
│   │   ├── 002_leads_contacto_id.php   # FK contacto_id en leads
│   │   ├── 003_seed_usuarios.php       # Seed: 1 admin + 3 usuarios (Argon2id)
│   │   ├── 004_seed_crm.sql            # Seed: contactos, leads, oportunidades, actividades
│   │   ├── 005_dominios.sql            # Tabla dominios
│   │   ├── 006_cuentas_correo.sql      # Tabla cuentas_correo
│   │   ├── 008_2fa.sql                 # Columnas 2FA en usuarios (enabled, code, expires_at, attempts)
│   │   ├── 009_facturas.sql            # Tablas facturas y factura_lineas
│   │   ├── 010_productos.sql           # Tabla productos (catálogo)
│   │   ├── 011_seed_productos.sql      # Seed: 9 productos/servicios de ejemplo
│   │   ├── 012_seed_facturas.php       # Seed: factura de ejemplo (FAC-DEMO-0001) calculada desde el catálogo
│   │   ├── 013_presupuestos_recordatorios.sql  # Tablas presupuestos/presupuesto_lineas + recordatorio_enviado_at en facturas
│   │   ├── 014_presupuestos_token_confirmacion.sql  # token_confirmacion/token_confirmado_at para la confirmación pública
│   │   ├── 015_proveedores.sql         # Tabla proveedores + productos.proveedor_id (ON DELETE SET NULL)
│   │   ├── 016_modulos_visibilidad.sql # Tabla modulos_visibilidad (15 módulos ocultables, admin-only)
│   │   ├── 017_avisos.sql              # Tablas avisos + avisos_destinatarios
│   │   └── 018_facturas_recurrentes.sql # Tablas facturas_recurrentes + lineas + facturas.recurrente_id + modulo 'recurrentes'
│   └── tareas/
│       ├── recordatorios_facturas.php  # Script cron: marca vencidas, email + tarea interna, idempotente
│       └── generar_facturas_recurrentes.php # Script cron: genera facturas desde plantillas recurrentes, envía email opcional
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
│   │       ├── cpanel-presupuestos.js  # CRM: Presupuestos + selector de producto + conversión a factura
│   │       ├── cpanel-productos.js     # CRM: Catálogo de productos/servicios
│   │       ├── cpanel-proveedores.js   # CRM: Proveedores (CRUD, CSV, enlace con productos)
│   │       ├── cpanel-facturas.js      # CRM: Facturas + líneas + selector de producto + totales
│   │       ├── cpanel-recurrentes.js   # CRM: Facturas recurrentes (plantillas + generar ahora + historial)
│   │       ├── cpanel-estadisticas.js  # Dashboard: Estadísticas
│   │       ├── cpanel-avisos.js        # Bandeja (todos) + composición/enviados (admin)
│   │       ├── cpanel-email.js         # Cuentas de correo
│   │       ├── cpanel-dominios.js      # Dominios
│   │       ├── cpanel-configuracion.js # Configuración de cuenta
│   │       ├── cpanel-usuarios.js      # Gestión de usuarios (admin)
│   │       ├── cpanel-auditoria.js     # Auditoría (admin)
│   │       ├── cpanel-modulos.js       # Visibilidad de módulos por rol (admin)
│   │       ├── cpanel-databases.js     # Bases de datos (admin)
│   │       └── cpanel-backups.js       # Copias de seguridad (admin)
│   ├── presupuesto-confirmar.php       # Página pública (sin sesión) para que el cliente acepte/rechace un presupuesto
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
│   │               ├── crm/            # contactos.php, leads.php, oportunidades.php, presupuestos.php, productos.php, proveedores.php, facturas.php, facturas_recurrentes.php
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
│   │   ├── contactos.php               # CRUD + filtros + paginación + export/import CSV
│   │   ├── leads.php                   # CRUD + filtros + paginación + conversión + export/import CSV
│   │   ├── oportunidades.php           # CRUD + filtros + cambio de etapa + export/import CSV
│   │   ├── presupuestos.php            # CRUD + líneas + ?action=convertir (a factura) + export/import CSV
│   │   ├── presupuesto_pdf.php         # Descarga PDF del presupuesto
│   │   ├── presupuesto_email.php       # Envío del presupuesto por email con PDF adjunto
│   │   ├── productos.php               # CRUD + filtros + paginación (catálogo) + export/import CSV
│   │   ├── proveedores.php             # CRUD + filtros + paginación + export/import CSV (upsert por NIF)
│   │   ├── facturas.php                # CRUD + líneas + totales + filtros + paginación + export/import CSV
│   │   ├── facturas_recurrentes.php    # CRUD + líneas + ?action=generar (factura inmediata)
│   │   ├── factura_pdf.php             # Descarga PDF de la factura
│   │   ├── factura_email.php           # Envío de la factura por email con PDF adjunto
│   │   ├── actividades.php             # CRUD por entidad
│   │   ├── dominios.php                # CRUD + filtros + paginación
│   │   ├── cuentas_correo.php          # CRUD + filtros + paginación
│   │   ├── configuracion.php           # PUT perfil / PUT password / GET+PUT 2FA (usuario propio)
│   │   ├── usuarios.php                # CRUD (admin-only)
│   │   ├── auditoria.php               # GET paginado (admin-only)
│   │   ├── databases.php               # GET estadísticas (admin-only)
│   │   ├── backups.php                 # CRUD backups (admin-only)
│   │   ├── modulos_visibilidad.php     # GET (cualquier usuario) / PUT (admin-only)
│   │   └── avisos.php                  # GET bandeja (cualquier usuario) / POST+DELETE (admin-only)
│   ├── auth/                           # login.php, logout.php, verify-2fa.php
│   ├── config/                         # Bloqueado por Nginx
│   │   ├── conexion.php                # PDO: lee variables de entorno
│   │   ├── seguridad.php               # Cabeceras HTTP + CSRF
│   │   ├── auditoria.php               # registrarAuditoria() tolerante a fallos
│   │   ├── csv_util.php                # csvDescargar()/csvLeerSubida() compartidas por export/import CSV
│   │   ├── modulos_visibilidad.php     # verificarModuloVisible()/verificarModuloVisibleTexto() (enforcement backend)
│   │   ├── facturas_documentos.php     # FacturaPdfSimple + helpers de formato + plantillas email factura
│   │   ├── facturas_recurrentes_util.php # generarFacturaDesdeRecurrente() + calculo de proxima_generacion (compartido API + cron)
│   │   ├── presupuestos_documentos.php # PDF/email de presupuestos (reutiliza FacturaPdfSimple)
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
| `productos` | Catálogo de productos/servicios (código, precio, IVA, stock, activo) | → `usuarios`, `proveedores` (opcional) |
| `proveedores` | Empresas/personas de las que se compra (nombre, NIF, contacto) | → `usuarios` |
| `modulos_visibilidad` | Visibilidad de secciones del panel para usuarios no-admin | → `usuarios` (`actualizado_por`) |
| `avisos` / `avisos_destinatarios` | Mensajes de un administrador a otros usuarios, con lectura por destinatario | → `usuarios` |
| `presupuestos` | Presupuestos con conversión a factura (`factura_id`) | → `contactos`, `usuarios`, `facturas` |
| `facturas` | Facturas con numeración automática, estado, fechas, totales, `recordatorio_enviado_at` y `recurrente_id` (si viene de una plantilla) | → `contactos`, `usuarios`, `facturas_recurrentes` |
| `facturas_recurrentes` / `facturas_recurrentes_lineas` | Plantillas que generan una factura real cada ciclo (mensual/trimestral/anual) | → `contactos`, `usuarios` |
| `factura_lineas` | Líneas de factura con concepto, cantidad, precio, IVA y total | → `facturas` |
| `actividades` | Notas/tareas/llamadas por entidad | → `contactos`, `leads`, `oportunidades` |
| `auditoria` | Log de cambios: tabla, registro, acción, JSON antes/después | → `usuarios` (SET NULL) |
| `dominios` | Dominios con tipo, estado, IP y SSL | — |
| `cuentas_correo` | Cuentas de correo con cuota y estado | — |

---

## Usuarios de prueba

Definidos en `database/migrations/003_seed_usuarios.php`. Solo para entornos locales.

| Usuario | Contraseña por defecto | Rol |
|---------|------------------------|-----|
| admin | Admin_LandJ#2026! | administrador |
| Samuel | User_LandJ#2026! | usuario |
| lito412 | User_LandJ#2026! | usuario |
| Cuervo | User_LandJ#2026! | usuario |
| *(Configurable vía INITIAL_ADMIN_PASSWORD / INITIAL_USER_PASSWORD en .env)* | | |

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

Al arrancar, el servicio `migrate` ejecuta automáticamente las migraciones pendientes e inserta los datos de prueba, incluyendo **9 productos/servicios de ejemplo** y **1 factura de ejemplo** (`FAC-DEMO-0001`) que demuestra el selector de producto en las líneas de factura. También se levanta el servicio `cron`, que ejecuta `database/tareas/recordatorios_facturas.php` y `database/tareas/generar_facturas_recurrentes.php` cada `CRON_INTERVALO_SEGUNDOS` (3600 s por defecto, configurable en `.env`) para marcar facturas vencidas, disparar recordatorios de cobro, y generar las facturas de las plantillas recurrentes que toquen ese día. Para relanzar manualmente:

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
