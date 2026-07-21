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
| `500` | Error BD |

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
Devuelve métricas agregadas del CRM.

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
