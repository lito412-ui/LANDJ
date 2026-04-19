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

## Autenticación

### `POST /api/login.php`
Inicia sesión. No requiere sesión previa ni token CSRF.

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
Lista todos los contactos, opcionalmente filtrados.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en nombre, apellidos, email, empresa |

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
Lista leads, filtrados opcionalmente.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `buscar` | string | LIKE en nombre, email, empresa |
| `estado` | string | Filtro exacto por estado (enum whitelist) |

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

## Oportunidades *(pendiente de implementar)*

Base: `/api/oportunidades.php`

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/oportunidades.php` | Listar (con `?etapa=` y `?asignado=`) |
| `GET` | `/api/oportunidades.php?id=<id>` | Detalle |
| `POST` | `/api/oportunidades.php` | Crear |
| `PUT` | `/api/oportunidades.php?id=<id>` | Actualizar datos |
| `PUT` | `/api/oportunidades.php?id=<id>&action=etapa` | Mover de etapa → body: `{ "etapa": "propuesta" }` |
| `DELETE` | `/api/oportunidades.php?id=<id>` | Eliminar |

---

## Actividades *(pendiente de implementar)*

Base: `/api/actividades.php`

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
Requiere sesión activa con rol `administrador`. Solo soporta `GET`.

### `GET /api/auditoria.php`
Devuelve el log de auditoría paginado.

**Query params**

| Param | Tipo | Descripción |
|-------|------|-------------|
| `tabla` | string | Filtra por entidad: `contactos`, `leads`, `usuarios`, etc. |
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
      "tabla": "usuarios",
      "registro_id": 3,
      "accion": "eliminar",
      "usuario_id": 1,
      "usuario_nombre": "admin",
      "datos_antes":   { "nombre": "Samuel", "rol": "usuario" },
      "datos_despues": null,
      "ip": "172.18.0.1",
      "created_at": "2026-04-18 14:32:00"
    }
  ]
}
```

> Si la tabla `auditoria` no existe (volumen antiguo), `registrarAuditoria()` falla silenciosamente y solo escribe en `error_log`. Ejecutar `docker compose run --rm migrate` para crearla.

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

## Convenciones generales

- Todos los endpoints leen el body como `application/json`.
- Las fechas se devuelven en formato MySQL (`YYYY-MM-DD HH:MM:SS`); el cliente las formatea con `toLocaleDateString('es-ES')`.
- Los campos opcionales devuelven `null` (no se omiten).
- El parámetro `t=<timestamp>` en GETs es anti-cache y siempre se ignora.
- Los endpoints mutantes validan el token CSRF vía header `X-CSRF-Token` (gestionado automáticamente por `fetchSeguro()` en el cliente).
