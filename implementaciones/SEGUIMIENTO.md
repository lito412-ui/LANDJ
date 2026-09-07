# Seguimiento - CRM

Usa este archivo como tablero. Marca cada tarea como `[x]` cuando esté completada.

## Resumen de avance (global)
- Peso total del proyecto: `100%`
- Fórmula: `avance_global = suma(avance_fase * peso_fase)`
- **Avance estimado actual: `90%`**
  - Fase 01: 100% × 15% = 15
  - Fase 02: 100% × 20% = 20
  - Fase 03: 100% × 40% = 40
  - Fase 04:   0% × 15% =  0
  - Fase 05:  90% × 10% =  9 (↑ desde 80%)

## Pesos por fase
- Fase 01 - Análisis y Planificación: `15%`
- Fase 02 - Diseño y Arquitectura: `20%`
- Fase 03 - Implementación y Desarrollo: `40%`
- Fase 04 - Pruebas y Validación: `15%`
- Fase 05 - Despliegue y Documentación: `10%`

---

## Fase 01 - Análisis y Planificación (Peso: 15% | Avance: 100%)
- [x] Definir alcance del MVP (que entra / que no entra)
- [x] Definir actores y roles (admin, usuario)
- [x] Escribir historias de usuario (epics) con criterios de aceptación → [`MVP_HISTORIAS_Y_CRITERIOS.md`](MVP_HISTORIAS_Y_CRITERIOS.md)
- [x] Definir requisitos no funcionales (seguridad, rendimiento, auditoría)
- [x] Definir entidades principales del CRM (alto nivel)
- [x] Plan de entregables (documentos, prototipos y versiones)

---

## Fase 02 - Diseño y Arquitectura (Peso: 20% | Avance: 100%)
- [x] Modelar datos del MVP (tablas: `usuarios`, `contactos`, `leads`, `oportunidades`, `actividades`, `auditoria`)
- [x] Ampliar modelo con tablas de hosting: `dominios`, `cuentas_correo`
- [x] Definir relaciones y reglas de integridad (FKs, ON DELETE, unicidad)
- [x] Definir esquema para auditoría (tabla `auditoria`, helper PHP tolerante a fallos, endpoint GET admin-only)
- [x] Diseñar flujo de autenticación (sesión PHP) y cierre de sesión
- [x] Diseñar RBAC (permisos por rol `usuario` / `administrador`)
- [x] Diseñar validaciones de entrada (tipo, formato, longitudes) y manejo de errores → [`VALIDACIONES.md`](VALIDACIONES.md)
- [x] Definir seguridad anti-CSRF/anti-XSS (encoding, tokens, cabeceras) → [`SEGURIDAD_CSRF_XSS.md`](SEGURIDAD_CSRF_XSS.md)
- [x] Prototipar pantallas principales (login, dashboard, listados, formularios, pipeline)
- [x] Definir mapa de navegación (de la vista principal a cada módulo)
- [x] Definir contratos de endpoints/acciones (rutas, payloads, respuestas y códigos de error) → [`CONTRATOS_API.md`](CONTRATOS_API.md)
- [x] Definir búsqueda/filtros (campos y paginación) → [`BUSQUEDA_FILTROS.md`](BUSQUEDA_FILTROS.md)
- [x] Preparar estrategia de archivos/adjuntos → excluido del MVP; diseño de tabla `adjuntos` documentado en FASE_02

---

## Fase 03 - Implementación y Desarrollo (Peso: 40% | Avance: 100%)

### Core e infraestructura (completado)
- [x] Preparar estructura del proyecto (carpetas por dominio, convenciones, rutas)
- [x] Configurar entorno Docker (Nginx + PHP-FPM + MySQL + migrate + phpMyAdmin + cAdvisor)
- [x] Crear esquema SQL completo del MVP con 6 tablas y FKs (`database/init.sql`)
- [x] Implementar sistema de migraciones idempotente (`database/migrate.php`)
- [x] Seed con datos de prueba vía migraciones (usuarios Argon2id + datos CRM)
- [x] Implementar seguridad: CSRF tokens + cabeceras HTTP (CSP, X-Frame-Options) en `seguridad.php`
- [x] `fetchSeguro()` cliente: inyecta `X-CSRF-Token` automáticamente
- [x] `registrarAuditoria()` tolerante a fallos (captura su propia PDOException)
- [x] Implementar autenticación (hash Argon2id, login, logout, sesión)
- [x] Implementar control de acceso RBAC en rutas y endpoints protegidos
- [x] Monitorización con métricas reales (CPU/RAM/Disco), actualización cada 3 s
- [x] Vista de perfil del usuario autenticado
- [x] Panel modularizado: `cpanel.php` + partials PHP por sección + assets por dominio

### CRM — módulos principales (completado)
- [x] CRUD completo de contactos (API REST + módulo JS + validaciones JS+PHP + detalle lateral)
- [x] CRUD completo de leads (búsqueda, filtros, badges estado, conversión lead→contacto atómica)
- [x] CRUD completo de gestión de usuarios — admin-only (badges rol, autoprotección, protección último admin)
- [x] Pipeline kanban de oportunidades: 5 etapas, drag & drop HTML5 entre columnas, cambio de etapa con confirm desde detalle, filtros avanzados
- [x] Modulo de facturas: tablas `facturas`/`factura_lineas`, API REST, lineas editables, calculo de totales, filtros, paginacion y detalle lateral
- [x] CRUD de actividades/notas ligadas a contactos, leads y oportunidades (`ActividadesWidget` compartido)
- [x] Búsqueda y filtros avanzados en todos los módulos (panel colapsable, badge activos, ordenación asc/desc)
- [x] Paginación server-side con `renderPaginacion` compartido en todos los listados CRM

### Catálogo de productos y import/export CSV (post-MVP, completado)
- [x] Módulo de productos/servicios (`productos`): CRUD completo (API + `cpanel-productos.js` + `productos.php` vista), con código único, precio, IVA, stock y estado activo/inactivo
- [x] Importación/exportación CSV en contactos, leads, oportunidades, productos y facturas (`?action=exportar` / `?action=importar` en cada API, helper compartido `public/config/csv_util.php`)
  - Export: CSV `;` con BOM UTF-8, descarga directa vía `window.open()`
  - Import: subida `multipart/form-data`, hasta 2000 filas, upsert por clave natural (email/código) donde aplica, resumen `{ creados, actualizados, errores, total }` sin frenar el resto ante filas inválidas
  - Facturas: una fila CSV por línea, agrupadas por `numero`; `contacto_email`/`lead_email` en vez de IDs crudos para portabilidad entre instalaciones
  - Botones "Exportar"/"Importar" añadidos a la toolbar de los 5 módulos; helpers cliente compartidos `importarCsvArchivo()` y `mostrarResultadoImportacion()` en `cpanel-core.js`
- [x] Selector de producto/servicio en las líneas de factura: al elegir un producto del catálogo se autorrellena concepto, precio e IVA (el concepto sigue siendo editable para conceptos personalizados no catalogados)
- [x] Migraciones `011_seed_productos.sql` (9 productos/servicios de ejemplo) y `012_seed_facturas.php` (factura de ejemplo `FAC-DEMO-0001` calculada dinámicamente desde el catálogo), ambas idempotentes y verificadas contra Docker

### Presupuestos y recordatorios automáticos de cobro (post-MVP, completado)
- [x] Módulo de presupuestos (`presupuestos`): tablas `presupuestos`/`presupuesto_lineas` (migración `013_presupuestos_recordatorios.sql`), API `presupuestos.php` con CRUD, líneas con selector de producto, filtros/paginación y numeración automática `PRE-YYYY-0001`
- [x] Conversión de presupuesto a factura en un clic: `POST presupuestos.php?id=<id>&action=convertir` crea una factura nueva (`emitida`, vencimiento a 30 días) copiando las líneas dentro de una transacción; marca el presupuesto como `convertido` y lo enlaza vía `factura_id`. Bloqueada la doble conversión y la edición de presupuestos ya convertidos (verificado con pruebas reales)
- [x] PDF y envío por email de presupuestos (`presupuesto_pdf.php`, `presupuesto_email.php`), reutilizando el motor `FacturaPdfSimple` de `facturas_documentos.php`; al enviarse por primera vez, un presupuesto en `borrador` pasa automáticamente a `enviado`
- [x] Import/export CSV de presupuestos con el mismo patrón que facturas (una fila por línea, agrupada por `numero`, `contacto_email` en vez de ID)
- [x] Frontend: sección "Presupuestos" en el sidebar (entre Oportunidades y Productos), vista + `cpanel-presupuestos.js`, badges de estado propios, botón "Convertir en factura" en el panel de detalle
- [x] Recordatorios automáticos de cobro: script `database/tareas/recordatorios_facturas.php` que (1) marca como `vencida` toda factura `emitida` con `fecha_vencimiento` pasada, (2) envía email de recordatorio con el PDF adjunto a facturas vencidas sin recordatorio en los últimos 7 días, (3) crea una tarea interna (`actividades`, `tipo='tarea'`) para el comercial — reutilizando el sistema de notificaciones ya existente, sin frontend nuevo — y (4) avisa 3 días antes del vencimiento. Columna `recordatorio_enviado_at` añadida a `facturas`. Verificado idempotente (dos ejecuciones seguidas no duplican tareas ni reenvían email)
- [x] Nuevo servicio `cron` en `docker-compose.yml` que ejecuta el script de recordatorios cada `CRON_INTERVALO_SEGUNDOS` (3600 por defecto), esperando a que `migrate` termine con éxito
- [x] **Bug corregido**: PDF en blanco en `FacturaPdfSimple` (`facturas_documentos.php`), heredado también por presupuestos al compartir el mismo motor. Causa: al construir los objetos del PDF, el objeto `/Type /Page` se sobrescribía por error con el objeto `/Type /Pages` antes de serializar, dejando el árbol de páginas roto (el lector de PDF no encontraba ninguna página válida). Reescrito `output()` con un array de objetos indexado por número de objeto real (sin colisiones de índice) y numeración explícita. Verificado con `pypdf` (extracción de texto), `pdftoppm` (renderizado a imagen) y un caso de 60 líneas para forzar 2 páginas
- [x] Página pública de confirmación de presupuestos (`public/presupuesto-confirmar.php`): en vez de que el cliente responda por email y haya que actualizar el estado a mano, el email incluye un enlace con un token de 64 caracteres (`bin2hex(random_bytes(32))`, generado la primera vez que se envía y reutilizado en envíos posteriores). La página, sin sesión, muestra el resumen del presupuesto con botones "Aceptar"/"Rechazar"; el `GET` nunca modifica el estado (evita confirmaciones accidentales por escáneres de email que pre-visitan enlaces) y solo el `POST` actualiza `estado`/`token_confirmado_at`. Protegida contra doble confirmación, presupuestos caducados y presupuestos ya convertidos en factura. URL construida dinámicamente desde `$_SERVER['HTTP_HOST']` (funciona igual en local que en producción). El enlace también se puede copiar manualmente desde el panel de detalle en el CRM. Verificado end-to-end: envío → token → confirmación → bloqueo de reconfirmación → casos límite (token inválido, caducado, ya convertido)

### Proveedores (post-MVP, completado)
- [x] Nueva tabla `proveedores` (migración `015_proveedores.sql`): nombre, NIF/CIF (único), email, teléfono, dirección, persona de contacto, notas, activo
- [x] API `proveedores.php`: CRUD completo + filtros (`buscar`, `activo`) + paginación + export/import CSV (upsert por NIF), siguiendo el mismo patrón que `contactos.php`
- [x] Enlace opcional `productos.proveedor_id` (`ON DELETE SET NULL`, para que borrar un proveedor no rompa los productos ya creados). Selector "Proveedor" añadido al formulario de productos; columna "Proveedor" en la tabla; `proveedor_nombre` incluido en el export CSV de productos y resuelto automáticamente por nombre exacto al importar
- [x] Frontend: sección "Proveedores" en el sidebar (después de Productos), vista + `cpanel-proveedores.js` (CRUD, búsqueda, filtro activo/inactivo, detalle lateral, export/import CSV)
- [x] **Bug corregido durante el desarrollo**: al unir `productos` con `proveedores` para mostrar `proveedor_nombre`, columnas como `nombre` y `activo` existen en ambas tablas; los filtros `buscar`/`activo` de `productos.php` daban error de "columna ambigua" hasta prefijarlos con el alias (`p.nombre`, `p.activo`)
- [x] Verificado end-to-end: CRUD, vínculo producto↔proveedor, `SET NULL` al eliminar un proveedor con productos vinculados, CSV export/import con upsert por NIF, CSRF y sesión

### Visibilidad de módulos por rol (post-MVP, completado)
- [x] Nueva sección admin-only "Módulos Visibles" (sidebar → Sistema): permite ocultar secciones enteras del panel para los usuarios con rol `usuario`, sin afectar a otros administradores (siempre ven todo)
- [x] Migración `016_modulos_visibilidad.sql`: tabla `modulos_visibilidad` (catálogo de 15 módulos ocultables, agrupados por categoría: Panel Principal, CRM, Archivos & Bases de Datos, Seguridad & SSL, Correo & Dominios) con `visible` por defecto `true`
- [x] API `modulos_visibilidad.php`: lectura (`GET`) abierta a cualquier usuario autenticado (la necesita el propio panel para decidir qué ocultar); escritura (`PUT`, un módulo a la vez, guardado instantáneo) restringida a `rol = 'administrador'` (`403` en caso contrario)
- [x] Deliberadamente **no** son ocultables: `dashboard`, `configuracion` (el usuario siempre necesita acceso a su propio perfil) ni las secciones ya restringidas a admin (`users`, `logs`, `databases`, `backups`, `modulos`)
- [x] Cliente: `aplicarVisibilidadModulos()` en `cpanel-core.js`, llamada justo después de resolver el rol en el login. Para no-administradores: oculta el `<li>` del sidebar de cada módulo no visible, redirige al dashboard si la sección activa deja de ser visible, y bloquea la navegación directa (clic o `navegarA()`) a un módulo oculto. Los administradores hacen bypass total (ni siquiera consultan la API)
- [x] Documentado inicialmente como control de solo interfaz; ampliado después (a petición explícita) a **enforcement real en el backend**, ver bloque siguiente
- [x] **Bloqueo real a nivel de API**: `public/config/modulos_visibilidad.php` añade `verificarModuloVisible()`/`verificarModuloVisibleTexto()` (esta última para los endpoints de PDF, que no responden JSON), llamada al inicio de cada endpoint de un módulo ocultable, justo después de `conexion.php` y antes de cualquier lógica. Devuelve `403` inmediatamente para usuarios no-admin si el módulo está oculto — antes de procesar `GET`/`POST`/`PUT`/`DELETE` o acciones como `?action=exportar`/`?action=convertir`. Los administradores siempre pasan (bypass total)
- [x] Aplicado en los 14 endpoints con módulo ocultable correspondiente: `contactos.php`, `leads.php`, `oportunidades.php`, `presupuestos.php`, `presupuesto_pdf.php`, `presupuesto_email.php`, `productos.php`, `proveedores.php`, `facturas.php`, `factura_pdf.php`, `factura_email.php`, `estadisticas.php`, `cuentas_correo.php`, `dominios.php`
- [x] Verificado end-to-end con llamadas directas por `curl` (sin pasar por el panel): usuario no-admin con "leads" oculto recibe `403` en `GET`, `POST` y `?action=exportar` de `leads.php`; `factura_pdf.php` responde `403` en texto plano (no JSON) cuando "facturas" está oculto; el mismo usuario sigue accediendo con normalidad a módulos no ocultos (`contactos`, `productos`, `estadisticas`); el administrador conserva acceso `200` a todo en todo momento
- [x] **Corregido a petición del usuario**: si se ocultaban todos los módulos de una categoría del sidebar (ej. "Archivos & Bases de Datos"), la cabecera de esa categoría se quedaba visible sin nada debajo. `aplicarVisibilidadModulos()` ahora detecta cuando un `nav-section` se queda sin ningún `nav-item` visible y oculta también su cabecera. Verificado con jsdom reproduciendo el sidebar real, primero con datos simulados y después con la respuesta real de la API (categoría completamente oculta desaparece del todo; categorías con al menos un módulo visible permanecen)
- [x] Añadido interruptor maestro por categoría en la pantalla "Módulos Visibles": un único toggle en la cabecera de cada tarjeta muestra/oculta todos los módulos de esa categoría a la vez (`Promise.all` de un `PUT` por módulo), con estado `indeterminate` cuando la categoría tiene una mezcla de visibles/ocultos. Verificado contra la API real (3 módulos de "Seguridad & SSL" ocultados y restaurados correctamente)
- [x] Verificado end-to-end: `GET` funciona para admin y usuario normal; `PUT` da `403` para usuario normal; toggle real en BD (`leads` ocultado y restaurado) con `actualizado_por` registrado; lógica de ocultación del sidebar y el guard de navegación verificados con jsdom reproduciendo el DOM real del panel (oculta el módulo correcto, deja el resto intacto, hace bypass completo para admin, y redirige a dashboard si la sección activa queda oculta)

### Ranking de comerciales, detección de duplicados y facturas recurrentes (post-MVP, completado)
- [x] **Ranking de comerciales** en Estadísticas: tabla con medallas (🥇🥈🥉) mostrando oportunidades ganadas, valor ganado, facturas emitidas y total facturado por usuario. Oportunidades atribuidas a `COALESCE(asignado_a, creado_por)` con `etapa='cerrada_ganada'`; facturas atribuidas a `creado_por`, excluyendo `cancelada`. Solo lista usuarios con actividad comercial real. Verificado con datos reales
- [x] **Detección de duplicados** al crear contactos (manual o CSV): coincidencia por `email` exacto → bloqueo permanente no forzable (columna `UNIQUE` en BD, forzarlo rompería la base de datos — se detectó este caso probándolo y se corrigió antes de dejarlo así); coincidencia solo por `telefono` (ignorando espacios/guiones) → aviso "blando" forzable con `?forzar=1`. La importación CSV usa la misma lógica de coincidencia como clave secundaria de upsert. Verificado con 5 escenarios: bloqueo por email, intento de forzar un duplicado de email (sigue bloqueando), duplicado por teléfono forzable, creación normal sin duplicados, e importación CSV actualizando por teléfono en vez de duplicar
- [x] **Facturas recurrentes**: nuevo módulo "Recurrentes" (categoría CRM, sidebar, después de Facturas) para plantillas que generan una factura real automáticamente cada cierto periodo
  - Migración `018_facturas_recurrentes.sql`: tablas `facturas_recurrentes` + `facturas_recurrentes_lineas`, columna `facturas.recurrente_id` (`ON DELETE SET NULL` — borrar la plantilla no borra las facturas ya generadas), y alta del módulo `recurrentes` en `modulos_visibilidad` (independiente de `facturas`: se puede ocultar uno sin el otro)
  - API `facturas_recurrentes.php`: CRUD + líneas con selector de producto (mismo patrón que facturas/presupuestos) + `?action=generar` (botón "Generar ahora", inmediato, sin esperar al cron)
  - Lógica de generación extraída a `public/config/facturas_recurrentes_util.php` (`generarFacturaDesdeRecurrente()`, `calcularProximaFechaRecurrente()`) para que la comparta el botón manual de la API **y** el script de cron, sin duplicar código
  - Script `database/tareas/generar_facturas_recurrentes.php`, añadido al mismo ciclo del servicio `cron` (junto a `recordatorios_facturas.php`): genera las facturas de las plantillas `activa=1` con `proxima_generacion <= CURDATE()` (respetando `fecha_fin`), avanza `proxima_generacion` al siguiente ciclo, y envía la factura por email automáticamente si la plantilla tiene `enviar_email=1`
  - `proxima_generacion` se inicializa a `fecha_inicio` al crear; los ciclos siguientes se calculan sumando 1/3/12 meses según periodicidad y ajustando el día al último día válido del mes destino (para plantillas con `dia_generacion` cercano a fin de mes)
  - Verificado end-to-end: creación de plantilla, botón "Generar ahora" (número de factura, totales e IVA exactos, `proxima_generacion` avanzada correctamente de agosto a septiembre), ejecución directa del script de cron (genera, idempotente en una segunda ejecución inmediata), plantilla `activa=0` correctamente excluida, envío de email automático intentado cuando `enviar_email=1` (falla de forma controlada sin SMTP real, sin crashear), enforcement de `modulos_visibilidad` en el endpoint (403 para usuario no-admin con el módulo oculto, acceso intacto para admin)

### Avisos: administradores notifican a otros usuarios (post-MVP, completado)
- [x] Nueva sección "Avisos" (sidebar, visible para **todos** los roles, no admin-only): un administrador redacta y envía un mensaje a otros usuarios; cada destinatario ve su propia bandeja con estado leído/no leído
- [x] Deliberadamente **distinto** del sistema de "Notificaciones" ya existente (recordatorios personales basados en `actividades.recordatorio_at`): los Avisos son mensajes de un usuario a otros, con tabla propia y seguimiento de lectura por destinatario, para no mezclar dos conceptos distintos bajo el mismo nombre
- [x] Migración `017_avisos.sql`: tabla `avisos` (título, mensaje, tipo `info`/`exito`/`aviso`/`urgente`, remitente) + tabla `avisos_destinatarios` (una fila por destinatario, con `leido_at` nulable, `ON DELETE CASCADE` en ambos sentidos)
- [x] API `avisos.php`: `GET` (mi bandeja, cualquier usuario) · `GET ?solo_conteo=1` (badge ligero) · `GET ?vista=enviados` (admin, con contador leídos/total por aviso) · `GET ?vista=usuarios` (admin, selector de destinatarios) · `POST` (admin, envía a `"todos"` o a una lista de IDs, transaccional) · `POST ?action=marcar_todas_leidas` · `PUT ?id=` (marcar leído, cualquier destinatario) · `DELETE ?id=` (admin)
- [x] "Todos" = todos los usuarios **excepto quien envía** el aviso (no hace falta notificarse a uno mismo), tal y como se pidió: "notificar a los demás usuarios"
- [x] Frontend: bandeja para todos, con badge rojo de no leídos en el sidebar (`cargarBadgeAvisosInicial()` al iniciar sesión, refrescado tras marcar como leído); composición y pestaña "Enviados" ocultas tras `data-admin-only`; selector de destinatarios "Todos" vs "Usuarios específicos" con checklist
- [x] `avisos` **no** se añadió al catálogo de `modulos_visibilidad`: es infraestructura de comunicación esencial, no ocultable (mismo criterio que `dashboard`/`configuracion`)
- [x] Verificado end-to-end con tres usuarios reales (admin, Samuel, lito412): envío a "todos" llega a los 3 destinatarios correctos con el remitente correcto; envío a un usuario específico solo aparece en su bandeja, no en la de terceros (aislamiento confirmado); marcar leído y "marcar todas" actualizan el badge correctamente; `vista=enviados` muestra el conteo de lectura exacto; usuario no-admin recibe `403` al intentar enviar, ver enviados o eliminar; validación de campos obligatorios; CSRF y sesión intactos; borrado en cascada verificado en BD

### cPanel — módulos hosting (completado)
- [x] CRUD completo de dominios (`dominios.php` API + `cpanel-dominios.js` + `domains.php` HTML)
  - Tipo: principal/subdominio/addon/parked; estado: activo/pendiente/suspendido; IP; SSL toggle
  - Filtros avanzados, paginación, validación de dominio y formato IP
  - Corregido: `registrarAuditoria` llamada sin `$userId`; `` `ssl` `` backtick-escapado (reservado MySQL)
  - CSP compliant: event delegation con `data-edit`/`data-del` (sin onclick inline)
- [x] CRUD completo de cuentas de correo (`cuentas_correo.php` API + `cpanel-email.js` + `email.php` HTML)
  - Cuota en MB (0 = ilimitada); formato ≥ 1024 MB → GB; dominio extraído automáticamente del email
  - Filtros avanzados, paginación, validación email

### Panel — secciones de sistema (completado)
- [x] Sección Auditoría: tabla con diff expandible, filtros por entidad y acción, paginación offset
- [x] Sección Bases de Datos: estadísticas MySQL en tiempo real con `SHOW TABLE STATUS`, botón phpMyAdmin
- [x] Sección Copias de Seguridad: creación, descarga y eliminación de backups `.sql` (`cpanel-backups.js`)
- [x] Sección Estadísticas: métricas CRM (leads por estado, oportunidades por etapa, valor potencial)
- [x] Configuración de cuenta: rediseñada a cards verticales (`.config-vertical`) — identidad horizontal, formularios Datos/Contraseña, Apariencia, Seguridad (con toggle 2FA), Avisos y Recordatorios con iconos; resumen pre-cargado al autenticarse (`Configuracion.cargar(data)`)
- [x] Dropdown de usuario expandido: avatar iniciales, nombre, email, badge de rol, sub-links a configuración, toggle de apariencia inline
- [x] Verificación en dos pasos (2FA): migración `008_2fa.sql`, login bifurcado, `verify-2fa.php` con countdown/reenvío/bloqueo, API `GET/PUT ?accion=2fa`, toggle en UI de Configuración
- [x] Servicio de email (PHPMailer ^6.9): `config/mailer.php`, plantilla HTML 2FA, `composer install` en startup del contenedor `php`, variables SMTP en docker-compose y `.env`

### UX y UI (completado)
- [x] Tema claro/oscuro: toggle en cabecera y en dropdown de usuario (sincronizados), `localStorage`, WCAG AA
  - Contrast fixes: `.nav-section-title`, `.crm-empty`, `.chart-label`, `.quick-action-btn span`
  - Status badges con clases CSS semánticas (sin `style=""` inline) para compatibilidad dark mode
  - Overrides por sección: pipeline cards, backup stats, db summary, badges usuario/auditoría, configuración
- [x] CSS refactorizado en 6 módulos con cache-busting `filemtime` independiente:
  `cpanel-base`, `cpanel-crm`, `cpanel-pipeline`, `cpanel-admin`, `cpanel-sistema`, `cpanel-dark`
- [x] Cache-busting `filemtime` añadido a todos los scripts JS en `cpanel.php`
- [x] Actividad reciente en dashboard: últimos 10 eventos de auditoría con tiempo relativo
- [x] Acciones rápidas del dashboard: backup, nueva cuenta de correo
- [x] Navegación programática `navegarA(sectionId)` + `initQuickActions()`
- [x] Permisos en UI: secciones admin-only ocultas, badge de rol en header, guard en JS
- [x] Estados vacíos (`crm-empty`) y carga (`crm-loading`) en todos los listados
- [x] Toast notifications (success/error/info) y confirm modal reutilizables con variantes

---

## Fase 04 - Pruebas y Validación (Peso: 15% | Avance: 0%)
- [ ] Plan de pruebas (funcionales y de regresión)
- [ ] Pruebas manuales de flujos críticos (login → CRUD → pipeline)
- [ ] Pruebas de seguridad básicas (inyección, XSS, CSRF, roles)
- [ ] Revisar accesibilidad básica (navegación por teclado, contraste WCAG)
- [ ] Cerrar brechas de usabilidad (formularios, mensajes, estados vacíos)

---

## Fase 05 - Despliegue y Documentación (Peso: 10% | Avance: 90%)
- [x] Preparar entorno (Docker, healthcheck, variables corregidas)
- [x] Documentar instalación/ejecución (README del proyecto)
- [x] Documentar arquitectura (modelo de datos + flujos)
- [x] Actualizar documentación con todos los cambios (Fase 03 completa + módulos hosting + UX dark mode)
- [x] Actualizar documentación con mejoras UX: dropdown expandido, configuración rediseñada, drag & drop, CSS modular
- [ ] Ejecutar prueba final end-to-end con un caso realista
- [ ] Tag/versión para entrega

---

## Posibles mejoras (post-MVP)

### Mejoras de alto impacto (prioridad alta)
- [x] Importación/exportación CSV para contactos, leads y oportunidades (impacto: alto | esfuerzo: bajo) — **implementado**, extendido también a productos, facturas y presupuestos
- [x] Notificaciones y recordatorios de tareas (impacto: alto | esfuerzo: medio) — **implementado**
- [x] Presupuestos (quotes) con conversión a factura en un clic (impacto: alto | esfuerzo: medio) — **implementado**
- [x] Recordatorios automáticos de cobro para facturas vencidas (impacto: alto | esfuerzo: medio) — **implementado**
- [ ] Búsqueda global unificada (impacto: alto | esfuerzo: medio)
- [x] Kanban drag-and-drop real para pipeline (impacto: medio-alto | esfuerzo: medio) — **implementado**

### Mejoras técnicas (prioridad media)
- [ ] Tests automatizados mínimos (smoke + integración) (impacto: alto | esfuerzo: medio)
- [~] Hardening de seguridad (rate limit pendiente, bloqueo por intentos 2FA ✓, 2FA OTP ✓, CSRF ✓, CSP ✓) (impacto: alto | esfuerzo: medio)
- [ ] API REST versionada para integraciones futuras (impacto: medio-alto | esfuerzo: medio-alto)
- [ ] Logging estructurado para incidencias (impacto: medio | esfuerzo: medio)

### Mejoras de producto (prioridad media-baja)
- [ ] Módulo de etiquetas y segmentación de contactos (impacto: medio | esfuerzo: medio)
- [ ] Informes exportables (PDF/CSV) por periodo (impacto: medio | esfuerzo: bajo)
- [x] Exportar facturas a PDF y enviarlas por email (impacto: alto | esfuerzo: medio) — **implementado** (`factura_pdf.php`, `factura_email.php`; mismo motor reutilizado por presupuestos)
- [ ] Multilenguaje básico (impacto: medio-bajo | esfuerzo: medio)
- [ ] Plantillas de email y seguimiento de envíos (impacto: medio | esfuerzo: alto)

### Siguiente iteración recomendada
- [x] Configurar proveedor de email funcional (Resend API o SMTP con credenciales válidas) para completar el flujo 2FA end-to-end — **completado**: `.env` tiene credenciales Gmail (contraseña de aplicación) reales y funcionales, usadas por 2FA, `factura_email.php`, `presupuesto_email.php` y `recordatorios_facturas.php`. Verificar con `public/test-mail.php`
- [ ] Rate limit en login (intentos fallidos por IP/usuario)
- [ ] Pruebas manuales de flujos críticos (Fase 04)
- [ ] Tag de versión para entrega final
