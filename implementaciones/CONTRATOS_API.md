# Contratos de API — LANDJ CRM

Todos los endpoints devuelven `Content-Type: application/json`.  
Las respuestas siguen el envelope estándar:

```json
{ "ok": true,  "data": <payload> }
{ "ok": false, "error": "<mensaje legible>" }
```

La sesión PHP es el mecanismo de autenticación. Sin sesión activa → `401 Unauthorized`.  
Los endpoints mutantes (POST, PUT, DELETE, PATCH) requieren el header `X-CSRF-Token` con el valor del token de sesión.

---

## Facturas

Base: `/api/facturas.php`  
Requiere sesion activa. Los metodos mutantes requieren `X-CSRF-Token`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_factura` | int | PK, auto |
| `numero` | string | Unico, generado como `FAC-YYYY-0001` |
| `contacto_id` | int | **Obligatorio**, FK a `contactos.id_contacto` |
| `estado` | enum | `borrador`, `emitida`, `pagada`, `vencida`, `cancelada` |
| `fecha_emision` | date | **Obligatoria**, formato `YYYY-MM-DD` |
| `fecha_vencimiento` | date\|null | No puede ser anterior a `fecha_emision` |
| `base_imponible` | decimal | Calculado desde lineas |
| `iva_total` | decimal | Calculado desde lineas |
| `total` | decimal | Calculado desde lineas |
| `notas` | string\|null | max 1000 |
| `lineas` | array | Minimo 1 linea |

### Campos de linea

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `concepto` | string | **Obligatorio**, max 255 |
| `cantidad` | decimal | > 0 |
| `precio_unitario` | decimal | >= 0 |
| `iva_porcentaje` | decimal | 0-100 |
| `subtotal` | decimal | Calculado: cantidad * precio |
| `iva_importe` | decimal | Calculado |
| `total_linea` | decimal | Calculado |

### `GET /api/facturas.php`
Lista facturas con contacto asociado, filtros, orden y paginacion.

**Query params**

| Param | Tipo | Descripcion |
|-------|------|-------------|
| `buscar` | string | LIKE en numero, contacto, empresa o email |
| `estado` | enum | Filtra por estado |
| `desde` | `YYYY-MM-DD` | `fecha_emision >= ?` |
| `hasta` | `YYYY-MM-DD` | `fecha_emision <= ?` |
| `orden` | `numero` \| `fecha_emision` \| `fecha_vencimiento` \| `total` \| `estado` | Campo de ordenacion |
| `dir` | `asc` \| `desc` | Direccion |
| `pagina` | int | Pagina, defecto 1 |
| `limite` | int | 1-100, defecto 20 |

### `GET /api/facturas.php?id=1`
Devuelve una factura con sus lineas.

### `POST /api/facturas.php`
Crea una factura. El servidor genera `numero` y recalcula totales.

**Body JSON**
```json
{
  "contacto_id": 1,
  "estado": "borrador",
  "fecha_emision": "2026-07-09",
  "fecha_vencimiento": "2026-08-09",
  "notas": "Condiciones internas",
  "lineas": [
    {
      "concepto": "Servicio mensual",
      "cantidad": 1,
      "precio_unitario": 250,
      "iva_porcentaje": 21
    }
  ]
}
```

### `PUT /api/facturas.php?id=1`
Actualiza cabecera y lineas. Las lineas se reemplazan de forma atomica dentro de una transaccion.

### `DELETE /api/facturas.php?id=1`
Elimina la factura y sus lineas por `ON DELETE CASCADE`.

### `GET /api/facturas.php?action=exportar`
Descarga un CSV (`;`, UTF-8 con BOM) con **una fila por linea de factura**. Varias filas comparten el mismo `numero` cuando la factura tiene varias lineas.

Columnas: `numero`, `contacto_email`, `estado`, `fecha_emision`, `fecha_vencimiento`, `notas`, `concepto`, `cantidad`, `precio_unitario`, `iva_porcentaje`.

### `POST /api/facturas.php?action=importar`
Sube un CSV con el mismo formato que el export (`multipart/form-data`, campo `archivo`). Las filas se agrupan por `numero`: cada grupo se valida y crea como una factura nueva con todas sus lineas dentro de una transaccion.

- Si `numero` ya existe en la base de datos, el grupo se omite (no se actualiza, para no pisar facturas ya emitidas) y se reporta en `errores`.
- Si `numero` viene vacio, cada fila se trata como una factura independiente de una sola linea.
- `contacto_email` es obligatorio y debe existir en `contactos`; si no existe, la fila se rechaza.
- Si `numero` no se indica, el servidor genera uno nuevo con `generarNumeroFactura()` (mismo formato `FAC-YYYY-0001`).

**Respuesta**
```json
{ "ok": true, "data": { "creados": 2, "actualizados": 0, "errores": ["Fila 4: ..."], "total": 3 } }
```
`total` es el numero de facturas (grupos), no de filas del CSV.

---

## Productos

Base: `/api/productos.php`
Requiere sesión activa. Los métodos mutantes requieren `X-CSRF-Token`.

> Catálogo de productos/servicios usado como origen del selector de "Concepto" al crear líneas de factura.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_producto` | int | PK, auto |
| `codigo` | string\|null | Único, máx 40, solo letras/números/`.`/`_`/`-` |
| `nombre` | string | **Obligatorio**, máx 150 |
| `descripcion` | string\|null | máx 1000 |
| `precio` | decimal | >= 0 |
| `iva_porcentaje` | decimal | 0-100, defecto 21 |
| `stock` | decimal | >= 0, defecto 0 |
| `activo` | bool | defecto `true` |
| `proveedor_id` | int\|null | FK `proveedores`, opcional; `ON DELETE SET NULL` (si se borra el proveedor, el producto no se pierde) |

### `GET /api/productos.php`
Lista productos con filtros, orden y paginación.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en `codigo`, `nombre` o `descripcion` |
| `activo` | `0`\|`1` | Filtra por estado |
| `orden` | `codigo`\|`nombre`\|`precio`\|`stock`\|`activo`\|`created_at` | Campo de ordenación |
| `dir` | `asc`\|`desc` | Dirección |
| `pagina` | int | Página, defecto 1 |
| `limite` | int | 1-100, defecto 20 |

### `GET /api/productos.php?id=<id>`
Devuelve un producto.

### `POST /api/productos.php`
Crea un producto.

### `PUT /api/productos.php?id=<id>`
Actualiza un producto existente.

### `DELETE /api/productos.php?id=<id>`
Elimina un producto por ID.

### `GET /api/productos.php?action=exportar`
Descarga un CSV (`;`, UTF-8 con BOM) con todos los productos.

Columnas: `codigo`, `nombre`, `descripcion`, `precio`, `iva_porcentaje`, `stock`, `activo`, `proveedor_nombre`, `created_at`.

### `POST /api/productos.php?action=importar`
Sube un CSV con el mismo formato (`multipart/form-data`, campo `archivo`). Si `codigo` coincide con un producto existente, se actualiza (upsert); si no, se crea. El campo `activo` acepta `1`/`0`, `si`/`no`, `true`/`false`, `activo`. Si `proveedor_nombre` coincide (exacto) con un proveedor existente, se enlaza automáticamente; si no se encuentra, el producto se crea sin proveedor (no es un error bloqueante). Filas inválidas se listan en `errores`.

**Respuesta**
```json
{ "ok": true, "data": { "creados": 1, "actualizados": 1, "errores": [], "total": 3 } }
```

---

## Proveedores

Base: `/api/proveedores.php`
Requiere sesión activa. Los métodos mutantes requieren `X-CSRF-Token`.

> Empresas o personas a las que compras productos/servicios. Se enlazan opcionalmente desde `productos.proveedor_id`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_proveedor` | int | PK, auto |
| `nombre` | string | **Obligatorio**, máx 150 |
| `nif` | string\|null | Único, 8-9 caracteres alfanuméricos (NIF/NIE/CIF) |
| `email` | string\|null | máx 150, formato válido |
| `telefono` | string\|null | máx 30 |
| `direccion` | string\|null | máx 255 |
| `contacto_referencia` | string\|null | máx 150, persona de contacto dentro del proveedor |
| `notas` | string\|null | máx 1000 |
| `activo` | bool | defecto `true` |

### `GET /api/proveedores.php`
Lista con filtros `buscar` (nombre/NIF/email/contacto_referencia), `activo` (`0`\|`1`), `orden` (`nombre`\|`nif`\|`activo`\|`created_at`), `dir`, `pagina`, `limite`.

### `GET /api/proveedores.php?id=<id>` / `POST` / `PUT /api/proveedores.php?id=<id>` / `DELETE /api/proveedores.php?id=<id>`
CRUD estándar, mismo patrón que Contactos.

### `GET /api/proveedores.php?action=exportar` / `POST /api/proveedores.php?action=importar`
Mismo patrón CSV que el resto de módulos. Columnas: `nombre`, `nif`, `email`, `telefono`, `direccion`, `contacto_referencia`, `notas`, `activo`, `created_at`. Upsert por `nif` cuando está presente.

---

## Facturas Recurrentes

Base: `/api/facturas_recurrentes.php`
Requiere sesión activa. Los métodos mutantes requieren `X-CSRF-Token`. Sujeto a `modulos_visibilidad` (módulo propio `recurrentes`, categoría CRM — independiente del módulo `facturas`, así que se puede ocultar uno sin ocultar el otro).

> Una plantilla recurrente genera automáticamente una factura real cada cierto periodo (mensual/trimestral/anual), a través de `database/tareas/generar_facturas_recurrentes.php` (ejecutado por el servicio `cron`). Cada factura generada queda enlazada a la plantilla vía `facturas.recurrente_id`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_recurrente` | int | PK, auto |
| `contacto_id` | int | **Obligatorio** |
| `nombre` | string | **Obligatorio**, máx 150 — nombre interno, no aparece en la factura |
| `periodicidad` | enum | `mensual`\|`trimestral`\|`anual` |
| `dia_generacion` | int | 1-28, día del mes para los ciclos a partir del segundo |
| `fecha_inicio` | date | **Obligatorio**; la primera generación usa esta fecha directamente como `proxima_generacion` |
| `fecha_fin` | date\|null | Opcional; pasada esa fecha, el cron deja de generar |
| `dias_vencimiento` | int | 0-365, días hasta el vencimiento de cada factura generada |
| `activa` | bool | Si es `false`, el cron la ignora aunque `proxima_generacion` haya pasado |
| `enviar_email` | bool | Si es `true`, cada factura generada se envía automáticamente al contacto con el PDF adjunto |
| `lineas` | array | **Obligatorio**, al menos 1: `{ concepto, cantidad, precio_unitario, iva_porcentaje }` — mismo selector de producto que en facturas/presupuestos |

### `GET /api/facturas_recurrentes.php`
Lista con filtros `buscar`, `activa` (`0`\|`1`), paginación. Cada fila incluye `importe_estimado` (calculado a partir de las líneas).

### `GET /api/facturas_recurrentes.php?id=<id>`
Detalle con `lineas` y `historial` (últimas 12 facturas generadas desde esta plantilla).

### `POST /api/facturas_recurrentes.php`
Crea la plantilla. `proxima_generacion` se inicializa a `fecha_inicio`.

### `PUT /api/facturas_recurrentes.php?id=<id>`
Actualiza la plantilla y sus líneas (no toca `proxima_generacion`/`ultima_generacion`).

### `DELETE /api/facturas_recurrentes.php?id=<id>`
Elimina la plantilla. Las facturas ya generadas **no se borran** (`ON DELETE SET NULL` en `facturas.recurrente_id`).

### `POST /api/facturas_recurrentes.php?id=<id>&action=generar`
Genera una factura real inmediatamente (botón "Generar ahora"), sin esperar al cron, y avanza `proxima_generacion` al siguiente ciclo igual que lo haría el cron. Usa la misma función compartida (`generarFacturaDesdeRecurrente()` en `public/config/facturas_recurrentes_util.php`) para no duplicar lógica entre el botón manual y el cron.

### Script de generación automática
`database/tareas/generar_facturas_recurrentes.php`, ejecutado por el servicio `cron` junto a `recordatorios_facturas.php`:
1. Busca plantillas `activa = 1` con `proxima_generacion <= CURDATE()` y (`fecha_fin IS NULL` o `fecha_fin >= CURDATE()`).
2. Genera una factura por cada una, calcula totales, avanza `proxima_generacion`.
3. Si `enviar_email = 1`, envía la factura al contacto con el PDF adjunto (mismo motor que `factura_email.php`).
4. Idempotente: al avanzar `proxima_generacion` tras generar, una plantilla no vuelve a aparecer como pendiente hasta el siguiente ciclo real.

---

## Avisos

Base: `/api/avisos.php`
Requiere sesión activa. Componer y eliminar requiere además rol `administrador`. Distinto del sistema de **Notificaciones** ya existente (recordatorios personales basados en `actividades.recordatorio_at`, ver `notificaciones.php`): un Aviso es un mensaje que un administrador redacta y envía a otros usuarios, con seguimiento de lectura por destinatario.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_aviso` | int | PK, auto |
| `titulo` | string | **Obligatorio**, máx 150 |
| `mensaje` | string | **Obligatorio**, máx 2000 |
| `tipo` | enum | `info`\|`exito`\|`aviso`\|`urgente` |
| `creado_por` | int | FK `usuarios`, quien lo envió |
| `destinatarios` (solo al crear) | `"todos"` \| `int[]` | `"todos"` = todos los usuarios excepto quien lo envía; con array, se filtran los IDs válidos |

Por destinatario se guarda además `leido_at` (`NULL` = no leído).

### `GET /api/avisos.php`
Bandeja del usuario autenticado: avisos donde es destinatario, más recientes primero (máx 200). Devuelve `meta.no_leidos` con el total de no leídos, para el badge del sidebar. Filtro opcional `?no_leidos=1`.

### `GET /api/avisos.php?solo_conteo=1`
Devuelve solo `{ no_leidos: N }` — endpoint ligero usado para refrescar el badge sin traer la lista completa.

### `GET /api/avisos.php?vista=enviados` (admin)
Historial de avisos enviados por cualquier administrador (no solo los propios), con `total_destinatarios` y `total_leidos` por aviso.

### `GET /api/avisos.php?vista=usuarios` (admin)
Lista de usuarios disponibles como destinatarios (`id_usuario`, `nombre`, `rol`), excluyendo al propio admin. Usada para el selector de "usuarios específicos" al componer.

### `POST /api/avisos.php`
Envía un aviso nuevo. Body: `{ titulo, mensaje, tipo, destinatarios }`. Solo administradores (`403` en caso contrario). Inserta el aviso y una fila en `avisos_destinatarios` por cada destinatario resuelto, dentro de una transacción.

### `POST /api/avisos.php?action=marcar_todas_leidas`
Marca como leídos todos los avisos pendientes del usuario autenticado (cualquier rol).

### `PUT /api/avisos.php?id=<id>`
Marca un aviso como leído para el usuario autenticado (cualquier rol; solo afecta a su propia fila en `avisos_destinatarios`). `404` si el usuario no es destinatario de ese aviso.

### `DELETE /api/avisos.php?id=<id>` (admin)
Elimina el aviso y, por `ON DELETE CASCADE`, todas sus filas de `avisos_destinatarios` (deja de aparecer en la bandeja de todos sus destinatarios).

### Cómo se aplica en el cliente
- Badge rojo en el enlace "Avisos" del sidebar (visible para **todos** los roles, no solo admin), actualizado al iniciar sesión (`cargarBadgeAvisosInicial()`) y tras marcar como leído.
- La bandeja ("Mi bandeja") es visible para cualquier usuario autenticado.
- El botón "Nuevo Aviso" y la pestaña "Enviados" están marcados con `data-admin-only` y solo se muestran a administradores.
- `avisos` **no** forma parte del catálogo de `modulos_visibilidad` (no es ocultable): un administrador no puede ocultarse a sí mismo la vía para notificar a su equipo, ni dejar a sus usuarios sin forma de ver los avisos que les llegan.

---

## Visibilidad de módulos (admin)

Base: `/api/modulos_visibilidad.php`
Requiere sesión activa. `PUT` requiere además rol `administrador`.

> Controla qué secciones del panel ven las cuentas con rol `usuario`, tanto en la **interfaz** (sidebar oculto, navegación bloqueada) como en el **backend** (cada endpoint del módulo responde `403` si se llama directamente, aunque no se pase por el panel). No es un sistema de permisos por usuario individual: aplica igual a todos los no-administradores de la instalación. Los administradores siempre tienen acceso completo, sin excepción.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `modulo` | string | PK, identificador interno (coincide con el `data-section` del sidebar) |
| `etiqueta` | string | Nombre mostrado en el panel de administración |
| `categoria` | string | Agrupación visual (`CRM`, `Correo & Dominios`, etc.) |
| `visible` | bool | `true` = visible para usuarios no-admin (defecto) |

Módulos ocultables (15): `statistics`, `contactos`, `leads`, `oportunidades`, `presupuestos`, `productos`, `proveedores`, `facturas`, `file-manager`, `ftp`, `ssl`, `security`, `firewall`, `email`, `domains`. Deliberadamente **no** incluye `dashboard`, `configuracion` ni las secciones ya restringidas a admin (`users`, `logs`, `databases`, `backups`, `modulos`).

### `GET /api/modulos_visibilidad.php`
Cualquier usuario autenticado (admin o no) puede leerlo — lo necesita el propio panel para decidir qué ocultar en su sidebar. Devuelve todos los módulos con su estado actual.

### `PUT /api/modulos_visibilidad.php?modulo=<modulo>`
Solo administradores. Body: `{ "visible": true|false }`. Actualiza un módulo a la vez (guardado instantáneo desde el toggle de la interfaz, sin botón "Guardar" independiente). Devuelve `403` si el usuario no es administrador, `404` si el módulo no existe en el catálogo.

### Cómo se aplica en el cliente
`cpanel-core.js` llama a `aplicarVisibilidadModulos()` justo después de resolver el rol del usuario en el login. Para no-administradores:
- Oculta (`display: none`) el `<li class="nav-item">` de cada módulo no visible en el sidebar.
- Si tras ocultar los módulos una categoría del sidebar (`nav-section`, ej. "Seguridad & SSL") se queda sin ningún `nav-item` visible, oculta también la cabecera de esa categoría completa, para no dejar un título flotando sin nada debajo.
- Si la sección actualmente activa deja de ser visible, redirige automáticamente al dashboard.
- Bloquea también la navegación directa (clic en sidebar o `navegarA()`) a un módulo oculto.

En la pantalla de administración ("Módulos Visibles"), cada tarjeta de categoría tiene además un **interruptor maestro** en la cabecera: actívalo/desactívalo para mostrar u ocultar todos los módulos de esa categoría a la vez (dispara un `PUT` por módulo en paralelo). Queda en estado intermedio (`indeterminate`) si la categoría tiene una mezcla de módulos visibles y ocultos.

### Cómo se aplica en el backend (enforcement real)
Cada endpoint de un módulo ocultable llama a `verificarModuloVisible($pdo, '<modulo>')` (o `verificarModuloVisibleTexto()` en los endpoints de descarga de PDF, que no responden JSON) justo después de `require conexion.php`, antes de cualquier lógica del recurso. La función vive en `public/config/modulos_visibilidad.php`:

- Si el usuario es `administrador`, pasa siempre (bypass total).
- Si no, consulta `modulos_visibilidad` para ese módulo; si `visible = 0`, corta con `403` inmediatamente — antes de tocar `GET`, `POST`, `PUT`, `DELETE` ni las acciones `?action=exportar`/`?action=importar`/`?action=convertir`.
- Si el módulo no existe en el catálogo (por ejemplo, un endpoint interno sin toggle propio), se permite por defecto: solo se restringe lo que el admin ha marcado explícitamente.

Endpoints con esta comprobación: `contactos.php`, `leads.php`, `oportunidades.php`, `presupuestos.php`, `presupuesto_pdf.php`, `presupuesto_email.php`, `productos.php`, `proveedores.php`, `facturas.php`, `factura_pdf.php`, `factura_email.php`, `facturas_recurrentes.php` (módulo propio `recurrentes`), `estadisticas.php`, `cuentas_correo.php`, `dominios.php`. No hace falta protegerlo en `file-manager`/`ftp`/`ssl`/`security`/`firewall` porque esas secciones aún no tienen API propia (son marcador de posición en el sidebar).

Los administradores nunca llaman siquiera a la API de lectura para esto (bypass total): siempre ven el panel completo.

---

## Presupuestos

Base: `/api/presupuestos.php`
Requiere sesión activa. Los métodos mutantes requieren `X-CSRF-Token`.

> Un presupuesto se envía al cliente antes de facturar. Al aceptarse, se convierte en una factura real (una sola vez) mediante la acción `convertir`, copiando sus líneas y quedando enlazado vía `factura_id`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_presupuesto` | int | PK, auto |
| `numero` | string | Único, autogenerado `PRE-YYYY-0001` |
| `contacto_id` | int | **Obligatorio**, FK `contactos` |
| `estado` | enum | `borrador`\|`enviado`\|`aceptado`\|`rechazado`\|`expirado`\|`convertido` |
| `fecha_emision` | date | **Obligatorio** |
| `fecha_validez` | date\|null | No puede ser anterior a `fecha_emision` |
| `notas` | string\|null | máx 1000 |
| `lineas` | array | **Obligatorio**, al menos 1: `{ concepto, cantidad, precio_unitario, iva_porcentaje }` |
| `factura_id` | int\|null | Se rellena solo al convertir; no editable manualmente |
| `token_confirmacion` | string\|null | 64 hex, se genera al enviar por email por primera vez; usado por la página pública de confirmación |
| `token_confirmado_at` | datetime\|null | Se rellena cuando el cliente acepta/rechaza desde el enlace público |

Los totales (`base_imponible`, `iva_total`, `total`) se calculan en el servidor a partir de las líneas, igual que en facturas.

### `GET /api/presupuestos.php`
Lista con filtros `buscar`, `estado`, `desde`, `hasta`, `orden` (`numero`\|`fecha_emision`\|`fecha_validez`\|`total`\|`estado`), `dir`, `pagina`, `limite`.

### `GET /api/presupuestos.php?id=<id>`
Devuelve el presupuesto con sus `lineas` y datos del contacto.

### `POST /api/presupuestos.php`
Crea un presupuesto en estado `borrador` (o el indicado). Numeración automática.

### `PUT /api/presupuestos.php?id=<id>`
Actualiza un presupuesto. **Rechaza la edición si ya tiene `factura_id`** (ya convertido).

### `DELETE /api/presupuestos.php?id=<id>`
Elimina el presupuesto. Si estaba convertido, la factura ya generada **no se borra** (`ON DELETE SET NULL` en `factura_id`).

### `POST /api/presupuestos.php?id=<id>&action=convertir`
Convierte el presupuesto en una factura nueva (`estado='emitida'`, vencimiento a 30 días), copiando todas sus líneas dentro de una transacción. Marca el presupuesto como `convertido` y guarda `factura_id`.

- Solo se puede convertir **una vez**: si ya tiene `factura_id`, devuelve error indicando el número de factura existente.
- No exige que el estado sea `aceptado` (flexibilidad para el usuario), pero la UI solo destaca el botón cuando lo está.

**Respuesta**
```json
{ "ok": true, "data": { "factura_id": 12, "factura_numero": "FAC-2026-0007" } }
```

### `GET /api/presupuesto_pdf.php?id=<id>`
Descarga el PDF del presupuesto (mismo motor que `factura_pdf.php`).

### `POST /api/presupuesto_email.php`
Envía el presupuesto por email al contacto con el PDF adjunto. Body: `{ "id": <id> }`. Si el presupuesto seguía en `borrador`, pasa automáticamente a `enviado`.

Antes de enviar, si el presupuesto no tiene `token_confirmacion`, genera uno (`bin2hex(random_bytes(32))`, 64 hex) y lo guarda; si ya existe, lo reutiliza (para no invalidar un enlace ya enviado). El email incluye un botón con el enlace a `GET /presupuesto-confirmar.php?token=<token>`. La URL base se construye dinámicamente a partir de `$_SERVER['HTTP_HOST']` (funciona igual en local que en producción), salvo que se defina la variable de entorno `APP_URL`.

### `GET|POST /presupuesto-confirmar.php?token=<token>`
Página **pública, sin sesión** para que el cliente acepte o rechace el presupuesto desde el email, sin tener que escribirte ni que tú tengas que actualizar el estado a mano.

- **GET**: solo muestra el resumen del presupuesto (número, contacto, líneas, total, validez) con dos botones. **Nunca cambia el estado** — así un escáner de seguridad de email que pre-visita el enlace no lo confirma por accidente.
- **POST** (`token`, `accion`: `aceptar`\|`rechazar`): actualiza `estado` a `aceptado`/`rechazado` y guarda `token_confirmado_at`. Registra auditoría con `usuario_id = NULL` (acción del cliente, no de un usuario interno) y la IP de origen.
- Idempotente: si `token_confirmado_at` ya está relleno, muestra "Ya has respondido" sin permitir cambiar la decisión.
- Si el presupuesto ya tiene `factura_id` (convertido) o `fecha_validez` ya pasó, muestra un mensaje informativo en vez de los botones.
- Token con formato inválido → `400`; token bien formado pero inexistente → `404`.
- El enlace también se puede copiar manualmente desde el panel de detalle del presupuesto en el CRM (útil para probar en local sin depender del envío de email).

### `GET /api/presupuestos.php?action=exportar` / `POST /api/presupuestos.php?action=importar`
Mismo formato que facturas: una fila CSV por línea, agrupada por `numero`, con `contacto_email` en vez de ID. Ver [convención CSV](#import--export-csv-contactos-leads-oportunidades-productos-facturas-presupuestos) al final del documento.

---

## Autenticación

### `POST /auth/login.php`
Inicia sesión. No requiere sesión previa ni token CSRF. Es un formulario PHP (responde con redirect, no JSON).

**Request body** (form POST)
```
usuario=admin&password=secreto123
```

**Comportamiento:**
- Credenciales correctas, **sin 2FA**: `session_regenerate_id(true)`, crea sesión completa, redirige a cpanel.
- Credenciales correctas, **con 2FA activo**: genera OTP 6 dígitos, almacena en BD (expiración 10 min), envía email, establece `$_SESSION['2fa_pending']`, redirige a `/auth/verify-2fa.php`.
- Credenciales incorrectas: redirige a `/modules/site/login.html?error=credenciales`.

---

### `POST /api/logout.php`
Cierra la sesión activa. Destruye `$_SESSION`.

**Respuestas**

| Código | Condición | Body |
|--------|-----------|------|
| `200` | Sesión cerrada | `{ "ok": true, "data": null }` |

---

### `GET /api/get_user.php`
Devuelve datos del usuario autenticado. Usado para verificar sesión al cargar el panel.

**Query params**: `t` (timestamp anti-cache, ignorado)

**Respuestas**

| Código | Condición | Body |
|--------|-----------|------|
| `200` | Sesión activa | `{ "logged": true, "id_usuario": 1, "nombre": "…", "rol": "…", "email": "…", "created_at": "…" }` |
| `200` | Sin sesión | `{ "logged": false }` |

> `id_usuario` se usa en el cliente para identificar la fila propia en Gestión de Usuarios.

---

## Monitorización

### `GET /api/monitorizacion.php`
Métricas en tiempo real del servidor (llamado cada 3 s desde el dashboard).

**Query params**: `t` (timestamp anti-cache)

**Respuesta `200`**
```json
{
  "cpu": "12.5",
  "ram_usada": 1024,
  "ram_total": 8192,
  "disco": "45.2"
}
```
> `cpu` y `disco` en porcentaje (string). `ram_usada` / `ram_total` en MB (int).

**Error**
```json
{ "error": "No disponible" }
```

---

## Contactos

Base: `/api/contactos.php`  
Requiere sesión activa.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_contacto` | int | PK, auto |
| `nombre` | string | **Obligatorio**, max 100, solo letras/espacios/guiones/apóstrofes |
| `apellidos` | string\|null | max 100, mismo patrón que nombre |
| `email` | string\|null | max 255, formato RFC email |
| `telefono` | string\|null | max 30, español: 9 dígitos iniciando en 6-9 |
| `empresa` | string\|null | max 150 |
| `notas` | string\|null | max 500 |
| `creado_por` | int | FK → usuarios.id_usuario |
| `created_at` | datetime | Auto |

---

### `GET /api/contactos.php`
Lista todos los contactos, opcionalmente filtrados y ordenados.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en nombre, apellidos, email, empresa |
| `empresa` | string | LIKE en empresa (filtro adicional) |
| `desde` | `YYYY-MM-DD` | `DATE(created_at) >= ?` |
| `hasta` | `YYYY-MM-DD` | `DATE(created_at) <= ?` |
| `orden` | `nombre` \| `empresa` \| `created_at` | Campo de ordenación (defecto: `created_at`) |
| `dir` | `asc` \| `desc` | Dirección (defecto: `desc`) |

**Respuesta `200`**
```json
{ "ok": true, "data": [ { "id_contacto": 1, "nombre": "Ana", "..." : "..." }, "..." ] }
```

---

### `GET /api/contactos.php?id=<id>`
Devuelve un contacto por ID.

| Código | Condición |
|--------|-----------|
| `200` | Encontrado |
| `404` | No existe → `"error": "Contacto no encontrado"` |

---

### `POST /api/contactos.php`
Crea un nuevo contacto. Requiere `X-CSRF-Token`.

**Request body**
```json
{
  "nombre":    "Ana",
  "apellidos": "García",
  "email":     "ana@ejemplo.com",
  "telefono":  "612 345 678",
  "empresa":   "ACME",
  "notas":     "Cliente potencial"
}
```

| Código | Condición |
|--------|-----------|
| `200` | Creado |
| `400` | Validación fallida |
| `401` | Sin sesión |
| `403` | Token CSRF inválido |
| `409` | Posible duplicado (ver debajo) |
| `500` | Error BD |

#### Detección de duplicados
Antes de crear, se comprueba si ya existe un contacto con el mismo `email` o `telefono` (ignorando espacios/guiones):

- **Coincidencia por `email`** (columna `UNIQUE` en BD): bloqueo permanente, **no forzable** — insertar igualmente violaría la restricción de la base de datos. Respuesta `409` con `"forzable": false`.
- **Coincidencia solo por `telefono`** (email distinto o vacío): aviso "blando", **sí forzable**. Respuesta `409` con `"forzable": true`; repetir la petición con `?forzar=1` para crear igualmente.

```json
// 409 por email (no forzable)
{ "ok": false, "error": "Ya existe un contacto con ese email", "duplicados": [ {"id_contacto": 1, "nombre": "María", ...} ], "forzable": false }

// 409 por teléfono (forzable con ?forzar=1)
{ "ok": false, "error": "Posible contacto duplicado (mismo teléfono)", "duplicados": [ {...} ], "forzable": true }
```

La importación CSV (`?action=importar`) usa la misma lógica de coincidencia para decidir si actualiza (upsert) un contacto existente o crea uno nuevo: primero por `email`, y si no hay coincidencia, por `telefono`.

---

### `PUT /api/contactos.php?id=<id>`
Actualiza un contacto existente. Body idéntico al POST.

| Código | Condición |
|--------|-----------|
| `200` | Actualizado |
| `400` | Sin ID / validación |
| `404` | No existe |

---

### `DELETE /api/contactos.php?id=<id>`
Elimina un contacto por ID. Requiere `X-CSRF-Token`.

| Código | Condición |
|--------|-----------|
| `200` | Eliminado → `{ "ok": true, "data": { "deleted": 5 } }` |
| `400` | Sin ID |
| `404` | No existe |

### `GET /api/contactos.php?action=exportar`
Descarga un CSV (`;`, UTF-8 con BOM) con todos los contactos.

Columnas: `nombre`, `apellidos`, `email`, `telefono`, `empresa`, `notas`, `created_at`.

### `POST /api/contactos.php?action=importar`
Sube un CSV con el mismo formato (`multipart/form-data`, campo `archivo`, máx. 2000 filas). Cada fila se valida con las mismas reglas que `POST /api/contactos.php`.

- Si `email` coincide con un contacto existente, se **actualiza** (upsert); si no, se **crea**.
- Filas con errores de validación no interrumpen la importación: se listan en `errores` con el número de fila.

**Respuesta**
```json
{ "ok": true, "data": { "creados": 1, "actualizados": 1, "errores": [], "total": 2 } }
```

---

## Leads

Base: `/api/leads.php`  
Requiere sesión activa.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_lead` | int | PK, auto |
| `nombre` | string | **Obligatorio**, max 100, solo letras/espacios/guiones/apóstrofes |
| `email` | string\|null | max 255, formato email |
| `telefono` | string\|null | max 30, teléfono español (9 dígitos, 6-9) |
| `empresa` | string\|null | max 150 |
| `origen` | string\|null | max 50 |
| `estado` | enum | `nuevo` \| `contactado` \| `calificado` \| `convertido` \| `descartado` (defecto: `nuevo`) |
| `notas` | string\|null | max 500 |
| `creado_por` | int | FK → usuarios |
| `contacto_id` | int\|null | FK → contactos.id_contacto (ON DELETE SET NULL); se asigna al convertir |
| `created_at` | datetime | Auto |

---

### `GET /api/leads.php`
Lista leads, filtrados y ordenados opcionalmente.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en nombre, email, empresa |
| `estado` | enum | Filtro exacto: `nuevo` \| `contactado` \| `calificado` \| `convertido` \| `descartado` |
| `origen` | string | LIKE en campo origen |
| `desde` | `YYYY-MM-DD` | `DATE(created_at) >= ?` |
| `hasta` | `YYYY-MM-DD` | `DATE(created_at) <= ?` |
| `orden` | `nombre` \| `estado` \| `created_at` | Campo de ordenación (defecto: `created_at`) |
| `dir` | `asc` \| `desc` | Dirección (defecto: `desc`) |

**Respuesta `200`**
```json
{ "ok": true, "data": [ { "id_lead": 1, "nombre": "Carlos", "estado": "nuevo", "..." : "..." } ] }
```

---

### `GET /api/leads.php?id=<id>`
Devuelve un lead por ID.

| Código | Condición |
|--------|-----------|
| `200` | Encontrado |
| `404` | No existe |

---

### `POST /api/leads.php`
Crea un nuevo lead. Requiere `X-CSRF-Token`.

**Request body**
```json
{
  "nombre":   "Carlos",
  "email":    "carlos@empresa.com",
  "telefono": "666 123 456",
  "empresa":  "TechCorp",
  "origen":   "web",
  "estado":   "nuevo",
  "notas":    "Interesado en plan Pro"
}
```

| Código | Condición |
|--------|-----------|
| `200` | Creado |
| `400` | Validación fallida |
| `403` | Token CSRF inválido |

---

### `PUT /api/leads.php?id=<id>`
Actualiza un lead. Body idéntico al POST (todos los campos opcionales excepto nombre).

| Código | Condición |
|--------|-----------|
| `200` | Actualizado |
| `404` | No existe |

---

### `PUT /api/leads.php?id=<id>&action=convertir`
Convierte un lead a contacto. Requiere `X-CSRF-Token`.

Crea un nuevo registro en `contactos` con los datos del lead, actualiza el lead con `estado = 'convertido'` y `contacto_id = <nuevo_id>`, todo en una transacción atómica.

**Protecciones:**
- El lead ya fue convertido (`contacto_id IS NOT NULL`) → `400`
- Email duplicado en tabla `contactos` → `400`

**Respuesta `200`**
```json
{
  "ok": true,
  "data": {
    "contacto": { "id_contacto": 5, "nombre": "Carlos", "email": "carlos@empresa.com", "..." : "..." },
    "lead":     { "id_lead": 3, "estado": "convertido", "contacto_id": 5, "..." : "..." }
  }
}
```

| Código | Condición |
|--------|-----------|
| `200` | Convertido — devuelve contacto creado y lead actualizado |
| `400` | Lead ya convertido / email duplicado en contactos |
| `403` | Token CSRF inválido |
| `404` | Lead no encontrado |
| `500` | Error en la transacción (rollback automático) |

---

### `DELETE /api/leads.php?id=<id>`
Elimina un lead. Requiere `X-CSRF-Token`.

| Código | Condición |
|--------|-----------|
| `200` | Eliminado → `{ "ok": true, "data": { "deleted": 3 } }` |
| `404` | No existe |

### `GET /api/leads.php?action=exportar`
Descarga un CSV (`;`, UTF-8 con BOM) con todos los leads.

Columnas: `nombre`, `email`, `telefono`, `empresa`, `origen`, `estado`, `notas`, `created_at`.

### `POST /api/leads.php?action=importar`
Sube un CSV con el mismo formato (`multipart/form-data`, campo `archivo`). Si `email` coincide con un lead existente se actualiza (upsert); si no, se crea. Filas invalidas se listan en `errores` sin frenar el resto.

**Respuesta**: igual formato que contactos — `{ creados, actualizados, errores, total }`.

---

## Usuarios

Base: `/api/usuarios.php`  
Requiere sesión activa con rol `administrador`. Los endpoints mutantes requieren `X-CSRF-Token`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_usuario` | int | PK, auto |
| `nombre` | string | **Obligatorio**, max 100, único, alfanumérico + espacios/guiones/puntos/underscore |
| `email` | string\|null | max 255, único, formato email |
| `rol` | enum | `usuario` \| `administrador` |
| `password` | string | **Obligatorio en crear**, mín 8 car., debe contener letras y números |
| `created_at` | datetime | Auto |

> La contraseña **nunca se devuelve** en ninguna respuesta GET.

---

### `GET /api/usuarios.php`
Lista todos los usuarios. Opcionalmente filtrados.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en nombre y email |

**Respuesta `200`**
```json
{
  "ok": true,
  "data": [
    { "id_usuario": 1, "nombre": "admin", "email": "admin@landj.local", "rol": "administrador", "created_at": "…" }
  ]
}
```

---

### `GET /api/usuarios.php?id=<id>`
Devuelve un usuario por ID.

| Código | Condición |
|--------|-----------|
| `200` | Encontrado |
| `404` | No existe |

---

### `POST /api/usuarios.php`
Crea un nuevo usuario.

**Request body**
```json
{ "nombre": "Nuevo", "email": "nuevo@landj.local", "rol": "usuario", "password": "pass1234" }
```

| Código | Condición |
|--------|-----------|
| `200` | Creado |
| `400` | Validación fallida / nombre o email duplicado |
| `403` | Token CSRF inválido o rol no es administrador |

---

### `PUT /api/usuarios.php?id=<id>`
Actualiza un usuario. Si `password` va vacío, no se modifica.

**Protecciones:**
- Un administrador no puede cambiar su propio rol.

| Código | Condición |
|--------|-----------|
| `200` | Actualizado |
| `400` | Validación / nombre o email duplicado |
| `403` | Intento de cambio de propio rol |
| `404` | No existe |

---

### `DELETE /api/usuarios.php?id=<id>`
Elimina un usuario.

**Protecciones:**
- Un administrador no puede eliminarse a sí mismo.
- No se puede eliminar al único administrador del sistema.

| Código | Condición |
|--------|-----------|
| `200` | Eliminado → `{ "ok": true, "data": { "deleted": 2 } }` |
| `400` | Intento de auto-eliminación o último admin |
| `404` | No existe |

---

## Oportunidades

Base: `/api/oportunidades.php`  
Requiere sesión activa. Los endpoints mutantes requieren `X-CSRF-Token`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_oportunidad` | int | PK, auto |
| `titulo` | string | **Obligatorio**, max 150 |
| `descripcion` | string\|null | max 500 |
| `valor` | decimal\|null | ≥ 0 |
| `etapa` | enum | `prospecto` \| `propuesta` \| `negociacion` \| `cerrada_ganada` \| `cerrada_perdida` (defecto: `prospecto`) |
| `fecha_cierre_esperada` | date\|null | formato `YYYY-MM-DD` |
| `contacto_id` | int\|null | FK → contactos |
| `lead_id` | int\|null | FK → leads |
| `creado_por` | int | FK → usuarios |
| `created_at` | datetime | Auto |

> El GET lista también devuelve `contacto_nombre` y `lead_nombre` (JOIN).

---

### `GET /api/oportunidades.php`
Lista oportunidades filtradas y ordenadas.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en título, descripción, nombre contacto/lead |
| `etapa` | enum | Filtro exacto por etapa (whitelist) |
| `valor_min` | decimal | `valor >= ?` |
| `valor_max` | decimal | `valor <= ?` |
| `cierre_desde` | `YYYY-MM-DD` | `fecha_cierre_esperada >= ?` |
| `cierre_hasta` | `YYYY-MM-DD` | `fecha_cierre_esperada <= ?` |
| `orden` | `titulo` \| `valor` \| `etapa` \| `fecha_cierre_esperada` \| `created_at` | Defecto: `created_at` |
| `dir` | `asc` \| `desc` | Defecto: `desc` |

---

### `GET /api/oportunidades.php?id=<id>`
Detalle de una oportunidad (incluye `contacto_nombre`, `lead_nombre`).

| Código | Condición |
|--------|-----------|
| `200` | Encontrada |
| `404` | No existe |

---

### `POST /api/oportunidades.php`
Crea una nueva oportunidad.

**Request body**
```json
{
  "titulo": "Proyecto X",
  "descripcion": "Descripción opcional",
  "valor": 15000,
  "etapa": "prospecto",
  "fecha_cierre_esperada": "2026-06-30",
  "contacto_id": 1,
  "lead_id": null
}
```

| Código | Condición |
|--------|-----------|
| `200` | Creada |
| `400` | Validación fallida |
| `403` | Token CSRF inválido |

---

### `PUT /api/oportunidades.php?id=<id>`
Actualiza todos los campos. Body idéntico al POST.

---

### `PUT /api/oportunidades.php?id=<id>&action=etapa`
Mueve la oportunidad a otra etapa. Registra auditoría.

**Request body**
```json
{ "etapa": "propuesta" }
```

**Respuesta `200`**: devuelve la oportunidad actualizada completa (con nombres JOIN).

| Código | Condición |
|--------|-----------|
| `200` | Etapa actualizada |
| `400` | Etapa no válida |
| `404` | No existe |

---

### `DELETE /api/oportunidades.php?id=<id>`

| Código | Condición |
|--------|-----------|
| `200` | Eliminada → `{ "ok": true, "data": { "deleted": <id> } }` |
| `404` | No existe |

### `GET /api/oportunidades.php?action=exportar`
Descarga un CSV (`;`, UTF-8 con BOM). Las relaciones se exponen como email legible en vez de ID:

Columnas: `titulo`, `descripcion`, `valor`, `etapa`, `fecha_cierre_esperada`, `contacto_email`, `lead_email`, `created_at`.

### `POST /api/oportunidades.php?action=importar`
Sube un CSV con el mismo formato (`multipart/form-data`, campo `archivo`). `contacto_email` y/o `lead_email` se resuelven contra las tablas `contactos`/`leads`; si el email no existe, la fila se rechaza y se reporta en `errores`. No hay upsert: cada fila valida crea una oportunidad nueva.

**Respuesta**: `{ creados, actualizados: 0, errores, total }`.

---

## Actividades

Base: `/api/actividades.php`  
Requiere sesión activa. Los endpoints mutantes requieren `X-CSRF-Token`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_actividad` | int | PK, auto |
| `tipo` | enum | `nota` \| `llamada` \| `reunion` \| `tarea` \| `email` |
| `descripcion` | string | **Obligatoria**, max 500 |
| `fecha` | date\|null | formato `YYYY-MM-DD` |
| `contacto_id` | int\|null | FK → contactos |
| `lead_id` | int\|null | FK → leads |
| `oportunidad_id` | int\|null | FK → oportunidades |
| `creado_por` | int | FK → usuarios |
| `created_at` | datetime | Auto |

> Al menos una de las FKs (`contacto_id`, `lead_id`, `oportunidad_id`) debe ser no nula.

---

### `GET /api/actividades.php`
Lista actividades de una entidad, ordenadas por `COALESCE(fecha, created_at) DESC`.

**Query params** (se requiere al menos uno de los tres primeros)

| Param | Tipo | Descripción |
|-------|------|-------------|
| `contacto_id` | int | Actividades del contacto |
| `lead_id` | int | Actividades del lead |
| `oportunidad_id` | int | Actividades de la oportunidad |
| `limite` | int | Máx resultados (defecto: 100) |

---

### `GET /api/actividades.php?id=<id>`
Devuelve una actividad por ID.

---

### `POST /api/actividades.php`
Crea una actividad ligada a una entidad.

**Request body**
```json
{
  "tipo": "llamada",
  "descripcion": "Llamada de seguimiento",
  "fecha": "2026-04-19",
  "contacto_id": 3
}
```

| Código | Condición |
|--------|-----------|
| `200` | Creada |
| `400` | Descripción vacía / tipo inválido / sin FK |

---

### `PUT /api/actividades.php?id=<id>`
Actualiza tipo, descripción y fecha. Body igual que POST.

---

### `DELETE /api/actividades.php?id=<id>`

| Código | Condición |
|--------|-----------|
| `200` | Eliminada → `{ "ok": true, "data": { "deleted": <id> } }` |
| `404` | No existe |

---

## Auditoría

Base: `/api/auditoria.php`  
Requiere sesión activa con rol `administrador`. Solo soporta `GET`.

### `GET /api/auditoria.php`
Devuelve el log de auditoría paginado.

**Query params**

| Param | Tipo | Valores | Descripción |
|-------|------|---------|-------------|
| `tabla` | string | `contactos`, `leads`, `oportunidades`, `actividades`, `usuarios` | Filtra por entidad (whitelist) |
| `accion` | string | `crear`, `editar`, `eliminar` | Filtra por tipo de acción |
| `registro_id` | int | — | Filtra por PK del registro concreto |
| `limite` | int | máx 200, defecto 50 | Resultados por página |
| `offset` | int | defecto 0 | Desplazamiento para paginación |

**Respuesta `200`**
```json
{
  "ok": true,
  "total": 87,
  "data": [
    {
      "id_auditoria": 12,
      "tabla": "usuarios",
      "registro_id": 3,
      "accion": "eliminar",
      "usuario_id": 1,
      "usuario_nombre": "admin",
      "datos_antes":   { "nombre": "Samuel", "rol": "usuario" },
      "datos_despues": null,
      "created_at": "2026-04-18 14:32:00"
    }
  ]
}
```

> `total` es el total de registros que cumplen los filtros (independiente del `limite`/`offset`), útil para calcular la paginación en el cliente.

> Si la tabla `auditoria` no existe (volumen antiguo), `registrarAuditoria()` falla silenciosamente y solo escribe en `error_log`. Ejecutar `docker compose run --rm migrate` para crearla.

| Código | Condición |
|--------|-----------|
| `401` | Sin sesión |
| `403` | Rol distinto de `administrador` |
| `500` | Error de base de datos |

---

## Bases de Datos

Base: `/api/databases.php`  
Requiere sesión activa con rol `administrador`. Solo soporta `GET`.

### `GET /api/databases.php`
Devuelve estadísticas de las tablas de la base de datos activa.

**Query params**: ninguno

**Respuesta `200`**
```json
{
  "ok": true,
  "data": {
    "db": "landj_crm",
    "total_tablas": 6,
    "total_filas": 842,
    "total_bytes": 1572864,
    "tablas": [
      {
        "nombre": "contactos",
        "motor": "InnoDB",
        "filas": 120,
        "bytes": 262144,
        "colacion": "utf8mb4_unicode_ci",
        "actualizada": "2026-04-18 14:32:00"
      }
    ]
  }
}
```

> `bytes` = `Data_length + Index_length` de `SHOW TABLE STATUS`.  
> `actualizada` puede ser `null` en tablas vacías o con estadísticas no actualizadas; el cliente muestra `—` en ese caso.  
> La sesión ejecuta `SET SESSION information_schema_stats_expiry = 0` para forzar estadísticas frescas en MySQL 8 InnoDB (evita `Update_time = NULL`).

| Código | Condición |
|--------|-----------|
| `401` | Sin sesión |
| `403` | Rol distinto de `administrador` |
| `500` | Error de base de datos |

---

## Estructura de la tabla `auditoria`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id_auditoria` | BIGINT PK | Auto-incremental |
| `tabla` | VARCHAR(50) | Entidad afectada |
| `registro_id` | INT | PK del registro afectado |
| `accion` | ENUM | `crear` / `editar` / `eliminar` |
| `usuario_id` | INT FK→usuarios | NULL si el usuario fue eliminado (ON DELETE SET NULL) |
| `datos_antes` | JSON | Estado previo (NULL en crear) |
| `datos_despues` | JSON | Estado nuevo (NULL en eliminar) |
| `ip` | VARCHAR(45) | IPv4/IPv6 del cliente |
| `created_at` | TIMESTAMP | Auto |

Índices: `(tabla, registro_id)`, `(usuario_id)`, `(created_at)`.

---

## Códigos de error comunes

| Código | Significado |
|--------|-------------|
| `400` | Validación fallida o parámetro faltante |
| `401` | Sin sesión o sesión caducada |
| `403` | Sin permiso para esta acción (RBAC o CSRF) |
| `404` | Recurso no encontrado |
| `405` | Método HTTP no permitido |
| `500` | Error interno de base de datos o servidor |

---

## Actividad Reciente

Base: `/api/actividad_reciente.php`  
Requiere sesión activa. Solo soporta `GET`.

### `GET /api/actividad_reciente.php`
Devuelve los últimos 10 eventos de auditoría con tiempo relativo.  
Admins ven todos; usuarios solo ven los propios.

**Respuesta `200`**
```json
{
  "ok": true,
  "data": [
    {
      "tipo":    "success",
      "texto":   "admin creó un contacto (#5)",
      "tiempo":  "Hace 3 min",
      "usuario": "admin"
    }
  ]
}
```

> `tipo` puede ser `success` (crear), `info` (editar) o `danger` (eliminar).  
> `tiempo` es relativo al momento de la petición (calculado en PHP con `tiempoRelativo()`).

---

## Estadísticas CRM

Base: `/api/estadisticas.php`  
Requiere sesión activa. Solo soporta `GET`.

### `GET /api/estadisticas.php`
Devuelve métricas agregadas del CRM: `totales`, `valor_pipeline`, `tasa_conversion`, `leads_por_estado`, `oportunidades_por_etapa`, `actividades_por_tipo` y `ranking_comerciales`.

`ranking_comerciales` es un array de usuarios con al menos una oportunidad ganada o una factura emitida, ordenado por facturación total descendente:

```json
"ranking_comerciales": [
  {
    "id_usuario": 1, "nombre": "admin",
    "oportunidades_ganadas": 3, "valor_ganado": "8400.00",
    "facturas_emitidas": 5, "facturado_total": "4077.70"
  }
]
```

- Oportunidades: se atribuyen a `COALESCE(asignado_a, creado_por)` con `etapa = 'cerrada_ganada'`.
- Facturas: se atribuyen a `creado_por`, excluyendo `estado = 'cancelada'`.
- Solo lista usuarios con alguna de las dos métricas > 0 (no aparece un usuario sin actividad comercial).

**Respuesta `200`**
```json
{
  "ok": true,
  "data": {
    "leads_por_estado": [
      { "estado": "nuevo", "total": 12 },
      { "estado": "convertido", "total": 5 }
    ],
    "oportunidades_por_etapa": [
      { "etapa": "prospecto", "total": 3, "valor": 45000.00 }
    ],
    "valor_total_pipeline": 145000.00,
    "total_contactos": 38,
    "total_leads": 21
  }
}
```

---

## Dominios

Base: `/api/dominios.php`  
Requiere sesión activa. Los endpoints mutantes requieren `X-CSRF-Token`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_dominio` | int | PK, auto |
| `dominio` | string | **Obligatorio**, max 253, formato FQDN |
| `tipo` | enum | `principal` \| `subdominio` \| `addon` \| `parked` |
| `estado` | enum | `activo` \| `pendiente` \| `suspendido` |
| `ip` | string\|null | IPv4 o IPv6 |
| `ssl` | tinyint | 0 / 1 |
| `notas` | string\|null | max 500 |
| `created_at` | datetime | Auto |

### `GET /api/dominios.php`
Lista dominios filtrados y paginados.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en dominio e IP |
| `tipo` | enum | Filtro exacto por tipo |
| `estado` | enum | Filtro exacto por estado |
| `orden` | `dominio` \| `tipo` \| `estado` \| `created_at` | Defecto: `created_at` |
| `dir` | `asc` \| `desc` | Defecto: `desc` |
| `pagina` | int | Defecto: 1 |
| `limite` | int | Defecto: 20 |

**Respuesta `200`**
```json
{
  "ok": true,
  "data": [ { "id_dominio": 1, "dominio": "ejemplo.com", "tipo": "principal", "ssl": 1, "..." : "..." } ],
  "meta": { "total": 15, "pagina": 1, "limite": 20, "paginas": 1 }
}
```

### `POST /api/dominios.php`
```json
{ "dominio": "ejemplo.com", "tipo": "principal", "estado": "activo", "ip": "192.168.1.1", "ssl": 1, "notas": null }
```

### `PUT /api/dominios.php?id=<id>`
Body idéntico al POST.

### `DELETE /api/dominios.php?id=<id>`
`{ "ok": true, "data": { "deleted": <id> } }`

---

## Cuentas de Correo

Base: `/api/cuentas_correo.php`  
Requiere sesión activa. Los endpoints mutantes requieren `X-CSRF-Token`.

### Campos del recurso

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_cuenta` | int | PK, auto |
| `email` | string | **Obligatorio**, único, formato RFC email |
| `dominio` | string | Extraído automáticamente del email |
| `cuota` | int | MB, 0 = sin límite |
| `estado` | enum | `activo` \| `suspendido` |
| `notas` | string\|null | max 500 |
| `created_at` | datetime | Auto |

### `GET /api/cuentas_correo.php`

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en email y dominio |
| `estado` | enum | `activo` \| `suspendido` |
| `orden` | `email` \| `dominio` \| `cuota` \| `created_at` | Defecto: `created_at` |
| `dir` | `asc` \| `desc` | Defecto: `desc` |
| `pagina` | int | Defecto: 1 |
| `limite` | int | Defecto: 20 |

**Respuesta `200`**
```json
{
  "ok": true,
  "data": [ { "id_cuenta": 1, "email": "usuario@ejemplo.com", "dominio": "ejemplo.com", "cuota": 500, "estado": "activo" } ],
  "meta": { "total": 3, "pagina": 1, "limite": 20, "paginas": 1 }
}
```

> `cuota = 0` → sin límite. El cliente muestra "Sin límite" en morado.  
> `cuota >= 1024` → el cliente muestra `(cuota/1024).toFixed(1) + " GB"`.

### `POST /api/cuentas_correo.php`
```json
{ "email": "usuario@ejemplo.com", "cuota": 500, "estado": "activo", "notas": null }
```

### `PUT /api/cuentas_correo.php?id=<id>`
Body idéntico al POST.

### `DELETE /api/cuentas_correo.php?id=<id>`
`{ "ok": true, "data": { "deleted": <id> } }`

---

## Configuración de Cuenta

Base: `/api/configuracion.php`  
Requiere sesión activa. Actúa sobre el usuario autenticado (no requiere `?id`). Solo soporta `PUT`.

### `PUT /api/configuracion.php`
Actualiza nombre y/o email del usuario en sesión.

**Request body**
```json
{ "nombre": "NuevoNombre", "email": "nuevo@correo.com" }
```

| Código | Condición |
|--------|-----------|
| `200` | Actualizado → `{ "ok": true, "data": { "nombre": "NuevoNombre", "email": "..." } }` |
| `400` | Nombre vacío / formato email inválido / nombre o email duplicado |

> Actualiza también `$_SESSION['nombre']` para que el header refleje el cambio sin recargar.

---

### `PUT /api/configuracion.php?accion=password`
Cambia la contraseña del usuario autenticado.

**Request body**
```json
{ "actual": "contraseña_actual", "nueva": "nueva1234", "confirmar": "nueva1234" }
```

**Validaciones:**
- `actual` debe coincidir con el hash almacenado (`password_verify`)
- `nueva` ≥ 8 caracteres, ≤ 72, debe contener letras y números
- `nueva === confirmar`

| Código | Condición |
|--------|-----------|
| `200` | Cambiada → `{ "ok": true, "data": null }` |
| `400` | Contraseña actual incorrecta / validación fallida |

---

### `GET /api/configuracion.php?accion=2fa-status`
Devuelve el estado actual de la verificación en dos pasos del usuario autenticado.

**Respuesta `200`**
```json
{ "ok": true, "data": { "enabled": true, "has_email": true } }
```

> `has_email` indica si el usuario tiene email configurado (requisito para activar 2FA). Si es `false`, el toggle se deshabilita con mensaje explicativo.

---

### `PUT /api/configuracion.php?accion=2fa`
Activa o desactiva la verificación en dos pasos del usuario autenticado. Requiere `X-CSRF-Token`.

**Request body**
```json
{ "enabled": true }
```

| Código | Condición |
|--------|-----------|
| `200` | Estado actualizado → `{ "ok": true, "data": { "enabled": true } }` |
| `400` | Sin email configurado (no se puede activar 2FA sin email) |

---

## Verificación en dos pasos

### `GET /auth/verify-2fa.php`
Renderiza la página de verificación OTP. Requiere `$_SESSION['2fa_pending']` activo (se establece en el login cuando el usuario tiene 2FA activado).

Redirige a login si no hay sesión pendiente.

**Query params**

| Param | Valor | Descripción |
|-------|-------|-------------|
| `accion` | `reenviar` | Genera nuevo OTP, lo guarda en BD y lo envía por email; redirige de vuelta al formulario |

---

### `POST /auth/verify-2fa.php`
Valida el código OTP introducido por el usuario.

**Form data**
```
codigo=123456
```

**Lógica:**
- Valida con `hash_equals` (constante-time, evita timing attacks)
- Comprueba expiración (10 min desde generación)
- Acumula intentos fallidos (`two_factor_attempts`); al 3º fallo borra el OTP y redirige a login con `?error=2fa_bloqueado`
- En éxito: `session_regenerate_id(true)`, crea sesión completa, redirige al panel

| Código | Condición |
|--------|-----------|
| Redirect 302 → cpanel | Código correcto y no expirado |
| Redirect 302 → verify-2fa | Código incorrecto (< 3 intentos) |
| Redirect 302 → login?error=2fa_bloqueado | 3 intentos fallidos o código expirado |

---

## Copias de Seguridad

Base: `/api/backups.php`  
Requiere sesión activa con rol `administrador`.

### `GET /api/backups.php`
Lista los backups disponibles ordenados por fecha descendente.

**Respuesta `200`**
```json
{
  "ok": true,
  "data": {
    "total": 2,
    "total_bytes": 42500,
    "ultimo": "2026-04-20 15:10:10",
    "backups": [
      { "nombre": "backup_2026-04-20_151010.sql", "bytes": 21250, "fecha": "2026-04-20 15:10:10" }
    ]
  }
}
```

### `POST /api/backups.php`
Genera un nuevo backup SQL completo con `mysqldump`.

**Respuesta `200`**
```json
{ "ok": true, "data": { "nombre": "backup_2026-04-20_151010.sql", "bytes": 21250 } }
```

| Código | Condición |
|--------|-----------|
| `200` | Backup creado |
| `500` | Error ejecutando `mysqldump` |

### `GET /api/backups.php?descargar=<nombre>`
Descarga el archivo SQL indicado con `Content-Disposition: attachment`.

| Código | Condición |
|--------|-----------|
| `200` | Archivo enviado |
| `400` | Nombre de archivo inválido (path traversal bloqueado) |
| `404` | Backup no encontrado |

### `DELETE /api/backups.php?nombre=<nombre>`
Elimina el backup indicado.

| Código | Condición |
|--------|-----------|
| `200` | `{ "ok": true, "data": { "deleted": "<nombre>" } }` |
| `400` | Nombre inválido |
| `404` | No existe |

---

## Convenciones generales

- Todos los endpoints leen el body como `application/json`.
- Las fechas se devuelven en formato MySQL (`YYYY-MM-DD HH:MM:SS`); el cliente las formatea con `toLocaleDateString('es-ES')`.
- Los campos opcionales devuelven `null` (no se omiten).
- El parámetro `t=<timestamp>` en GETs es anti-cache y siempre se ignora.
- Los endpoints con paginación devuelven un objeto `meta` con `{ total, pagina, limite, paginas }`.
- `fetchSeguro()` inyecta `X-CSRF-Token` automáticamente en POST/PUT/DELETE/PATCH.
- Los endpoints mutantes validan el token CSRF vía header `X-CSRF-Token` (gestionado automáticamente por `fetchSeguro()` en el cliente).

### Import / Export CSV (contactos, leads, oportunidades, productos, proveedores, facturas, presupuestos)

- **Export**: `GET <endpoint>.php?action=exportar`. Requiere sesión (no CSRF, es GET). Devuelve `text/csv` con BOM UTF-8, separador `;` y `Content-Disposition: attachment`. Se abre con `window.open(url, '_blank')` en el cliente para reutilizar la sesión activa.
- **Import**: `POST <endpoint>.php?action=importar`, `multipart/form-data` con campo `archivo`. Requiere `X-CSRF-Token`. Límite de 2000 filas por archivo. Acepta `;` o `,` como separador (autodetectado) y quita el BOM si existe.
- La respuesta siempre sigue `{ ok: true, data: { creados, actualizados, errores: string[], total } }`; los `errores` referencian el número de fila del CSV (cabecera = fila 1) y **no interrumpen** el resto de la importación.
- Contactos, leads, productos y proveedores hacen **upsert** por una clave natural (`email`, `codigo` o `nif`); oportunidades, facturas y presupuestos solo **crean** (facturas y presupuestos omiten los `numero` que ya existen, para no sobrescribir documentos ya emitidos/enviados).
- La lógica compartida vive en `public/config/csv_util.php` (`csvDescargar()`, `csvLeerSubida()`, `csvBool()`, `csvFloat()`) y se reutiliza en los 7 endpoints. En el cliente, `importarCsvArchivo()` y `mostrarResultadoImportacion()` (en `cpanel-core.js`) son compartidas por todos los módulos.

### Recordatorios automáticos de cobro

`database/tareas/recordatorios_facturas.php` (ejecutado por el servicio `cron` de `docker-compose.yml`, cada `CRON_INTERVALO_SEGUNDOS` — 3600 por defecto):

1. Marca como `vencida` toda factura `emitida` cuya `fecha_vencimiento` ya pasó.
2. Para facturas `vencida` sin recordatorio en los últimos 7 días: envía un email al contacto con el PDF adjunto (plantilla `facturaDocumentoHtmlRecordatorio()`) y crea una **tarea interna** (`actividades`, `tipo='tarea'`, `recordatorio_at=NOW()`) para el comercial (`creado_por` de la factura). Actualiza `recordatorio_enviado_at`.
3. Para facturas `emitida` que vencen en los próximos 3 días: crea solo la tarea interna de aviso previo (sin email al cliente).
4. Es **idempotente**: antes de crear una tarea comprueba que no exista ya una pendiente (`completada = 0`) con el mismo texto para el mismo usuario.

Las tareas creadas aparecen automáticamente en la campana de notificaciones existente (`GET /api/notificaciones.php`), sin necesidad de UI nueva, porque ese sistema ya se basa en `actividades.recordatorio_at`.
