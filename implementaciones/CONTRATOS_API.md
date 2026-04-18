# Contratos de API — LANDJ CRM

Todos los endpoints devuelven `Content-Type: application/json`.  
Las respuestas siguen el envelope estándar:

```json
{ "ok": true,  "data": <payload> }
{ "ok": false, "error": "<mensaje legible>" }
```

La sesión PHP es el mecanismo de autenticación. Sin sesión activa → `401 Unauthorized`.

---

## Autenticación

### `POST /api/login.php`
Inicia sesión. No requiere sesión previa.

**Request body**
```json
{ "usuario": "admin", "password": "secreto123" }
```

**Respuestas**

| Código | Condición | Body |
|--------|-----------|------|
| `200` | Credenciales correctas | `{ "ok": true, "data": { "nombre": "Admin", "rol": "administrador" } }` |
| `401` | Credenciales incorrectas | `{ "ok": false, "error": "Credenciales incorrectas" }` |
| `400` | Campos vacíos | `{ "ok": false, "error": "Usuario y contraseña son obligatorios" }` |

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
| `200` | Sesión activa | `{ "ok": true, "logged": true, "nombre": "…", "rol": "…", "email": "…", "created_at": "…" }` |
| `200` | Sin sesión | `{ "ok": true, "logged": false }` |

---

## Monitorización

### `GET /api/monitorizacion.php`
Métricas en tiempo real del servidor (se llama cada 3 s desde el dashboard).

**Query params**: `t` (timestamp anti-cache)

**Respuesta `200`**
```json
{
  "ok": true,
  "data": {
    "cpu": "12.5",
    "ram_usada": 1024,
    "ram_total": 8192,
    "disco": "45.2"
  }
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
| `telefono` | string\|null | max 30, español: 9 dígitos iniciando en 6-9 (espacios/guiones permitidos) |
| `empresa` | string\|null | max 150 |
| `notas` | string\|null | max 500 |
| `creado_por` | int | FK → usuarios.id_usuario |
| `created_at` | datetime | Auto (MySQL DEFAULT) |

---

### `GET /api/contactos.php`
Lista todos los contactos, opcionalmente filtrados.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | Búsqueda LIKE en nombre, apellidos, email, empresa |

**Respuesta `200`**
```json
{ "ok": true, "data": [ { "id_contacto": 1, "nombre": "Ana", ... }, ... ] }
```

---

### `GET /api/contactos.php?id=<id>`
Devuelve un contacto por ID.

**Respuestas**

| Código | Condición | Body |
|--------|-----------|------|
| `200` | Encontrado | `{ "ok": true, "data": { … } }` |
| `404` | No existe | `{ "ok": false, "error": "Contacto no encontrado" }` |

---

### `POST /api/contactos.php`
Crea un nuevo contacto.

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

**Respuestas**

| Código | Condición | Body |
|--------|-----------|------|
| `200` | Creado | `{ "ok": true, "data": { … contacto creado … } }` |
| `400` | Validación fallida | `{ "ok": false, "error": "El nombre es obligatorio; Teléfono español inválido" }` |
| `401` | Sin sesión | `{ "ok": false, "error": "No autorizado" }` |
| `500` | Error BD | `{ "ok": false, "error": "Error de base de datos" }` |

---

### `PUT /api/contactos.php?id=<id>`
Actualiza un contacto existente. Body idéntico al POST.

**Respuestas**

| Código | Condición | Body |
|--------|-----------|------|
| `200` | Actualizado | `{ "ok": true, "data": { … contacto actualizado … } }` |
| `400` | Sin ID / validación | `{ "ok": false, "error": "…" }` |
| `404` | No existe | `{ "ok": false, "error": "Contacto no encontrado" }` |

---

### `DELETE /api/contactos.php?id=<id>`
Elimina un contacto por ID.

**Respuestas**

| Código | Condición | Body |
|--------|-----------|------|
| `200` | Eliminado | `{ "ok": true, "data": { "deleted": 5 } }` |
| `400` | Sin ID | `{ "ok": false, "error": "ID requerido" }` |
| `404` | No existe | `{ "ok": false, "error": "Contacto no encontrado" }` |

---

## Leads *(pendiente de implementar)*

Base: `/api/leads.php`

### Campos previstos

| Campo | Tipo | Restricciones |
|-------|------|---------------|
| `id_lead` | int | PK, auto |
| `nombre` | string | Obligatorio, max 150 |
| `email` | string\|null | max 255, formato email |
| `telefono` | string\|null | max 30, teléfono español |
| `estado` | enum | `nuevo` \| `contactado` \| `calificado` \| `convertido` \| `descartado` |
| `origen` | string\|null | max 100 |
| `notas` | string\|null | max 500 |
| `contacto_id` | int\|null | FK → contactos (si convertido) |
| `creado_por` | int | FK → usuarios |

### Endpoints previstos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/leads.php` | Listar (con `?buscar=` y `?estado=`) |
| `GET` | `/api/leads.php?id=<id>` | Detalle |
| `POST` | `/api/leads.php` | Crear |
| `PUT` | `/api/leads.php?id=<id>` | Actualizar |
| `PUT` | `/api/leads.php?id=<id>&action=convertir` | Convertir a contacto |
| `DELETE` | `/api/leads.php?id=<id>` | Eliminar |

---

## Oportunidades *(pendiente de implementar)*

Base: `/api/oportunidades.php`

### Endpoints previstos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/oportunidades.php` | Listar (con `?etapa=` y `?asignado=`) |
| `GET` | `/api/oportunidades.php?id=<id>` | Detalle |
| `POST` | `/api/oportunidades.php` | Crear |
| `PUT` | `/api/oportunidades.php?id=<id>` | Actualizar datos |
| `PUT` | `/api/oportunidades.php?id=<id>&action=etapa` | Mover de etapa (body: `{ "etapa": "propuesta" }`) |
| `DELETE` | `/api/oportunidades.php?id=<id>` | Eliminar |

---

## Actividades *(pendiente de implementar)*

Base: `/api/actividades.php`

### Endpoints previstos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/actividades.php?contacto_id=<id>` | Actividades de un contacto |
| `GET` | `/api/actividades.php?lead_id=<id>` | Actividades de un lead |
| `POST` | `/api/actividades.php` | Crear actividad/nota |
| `PUT` | `/api/actividades.php?id=<id>` | Editar |
| `DELETE` | `/api/actividades.php?id=<id>` | Eliminar |

---

## Auditoría

Base: `/api/auditoria.php`  
Requiere sesión activa con rol `administrador`.  
Solo soporta `GET`.

### `GET /api/auditoria.php`
Devuelve el log de auditoría paginado, opcionalmente filtrado.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `tabla` | string | Filtra por entidad: `contactos`, `leads`, etc. |
| `registro_id` | int | Filtra por PK del registro concreto |
| `limite` | int | Resultados por página (máx 200, defecto 50) |
| `offset` | int | Desplazamiento para paginación |

**Respuesta `200`**
```json
{
  "ok": true,
  "count": 2,
  "data": [
    {
      "id_auditoria": 12,
      "tabla": "contactos",
      "registro_id": 5,
      "accion": "editar",
      "usuario_id": 1,
      "usuario_nombre": "Admin",
      "datos_antes":   { "nombre": "Ana", "email": "viejo@mail.com", "..." : "..." },
      "datos_despues": { "nombre": "Ana", "email": "nuevo@mail.com", "..." : "..." },
      "ip": "192.168.1.10",
      "created_at": "2026-04-18 14:32:00"
    }
  ]
}
```

**Respuestas de error**

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
| `usuario_id` | INT FK→usuarios | NULL si fue el sistema; survives user delete (SET NULL) |
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
| `403` | Sin permiso para esta acción (RBAC) |
| `404` | Recurso no encontrado |
| `405` | Método HTTP no permitido |
| `500` | Error interno de base de datos o servidor |

---

## Convenciones generales

- Todos los endpoints leen el body como `application/json` (excepto login, que puede ser form-data).
- Las fechas se devuelven en formato MySQL (`YYYY-MM-DD HH:MM:SS`); el cliente las formatea.
- Los campos opcionales devuelven `null` (no se omiten) para facilitar el mapeo en el cliente.
- El parámetro `t=<timestamp>` en GETs es anti-cache y siempre se ignora.
