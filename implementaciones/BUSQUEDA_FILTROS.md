# Diseño de Búsqueda, Filtros y Paginación — LANDJ CRM

---

## Principios generales

| Principio | Descripción |
|-----------|-------------|
| **Búsqueda debounced** | El cliente espera 400 ms tras el último keystroke antes de lanzar la petición |
| **Server-side siempre** | El filtrado y paginado ocurre en SQL, nunca filtrando arrays en JS |
| **Parámetros via query string** | `GET /api/recurso.php?buscar=&filtro=&pagina=&limite=` |
| **Respuesta con metadatos** | Junto con `data[]` se devuelve `total`, `pagina` y `paginas` para que el cliente pueda renderizar la paginación |
| **Combinable** | Búsqueda + filtros + paginación se pueden usar juntos en la misma petición |

---

## Estructura de respuesta paginada

```json
{
  "ok": true,
  "data": [ { … }, { … } ],
  "meta": {
    "total":   87,
    "pagina":   2,
    "limite":  20,
    "paginas":  5
  }
}
```

> Los listados sin paginación explícita (ej: dropdowns de FK) devuelven solo `data[]` sin `meta`.

---

## Por entidad

### Contactos (`/api/contactos.php`) ✅ parcialmente implementado

**Búsqueda textual** — parámetro `?buscar=`

| Campo | Tipo de match |
|-------|--------------|
| `nombre` | LIKE `%término%` |
| `apellidos` | LIKE `%término%` |
| `email` | LIKE `%término%` |
| `empresa` | LIKE `%término%` |

**Filtros adicionales** — pendiente de implementar

| Parámetro | Valores | SQL |
|-----------|---------|-----|
| `empresa` | string exacto | `empresa = ?` |
| `desde` | fecha ISO `YYYY-MM-DD` | `created_at >= ?` |
| `hasta` | fecha ISO `YYYY-MM-DD` | `created_at <= ?` |

**Ordenación**

| Parámetro | Valores permitidos | Defecto |
|-----------|-------------------|---------|
| `orden` | `nombre`, `empresa`, `created_at` | `created_at` |
| `dir` | `asc`, `desc` | `desc` |

**Paginación**

| Parámetro | Defecto | Máximo |
|-----------|---------|--------|
| `pagina` | `1` | — |
| `limite` | `20` | `100` |

---

### Leads (`/api/leads.php`) — pendiente

**Búsqueda textual** — parámetro `?buscar=`

| Campo | Tipo de match |
|-------|--------------|
| `nombre` | LIKE `%término%` |
| `email` | LIKE `%término%` |
| `empresa` | LIKE `%término%` |

**Filtros**

| Parámetro | Valores | SQL |
|-----------|---------|-----|
| `estado` | `nuevo`, `contactado`, `calificado`, `convertido`, `descartado` | `estado = ?` |
| `origen` | string | `origen = ?` |
| `desde` | fecha ISO | `created_at >= ?` |
| `hasta` | fecha ISO | `created_at <= ?` |

**Ordenación**

| Parámetro | Valores permitidos | Defecto |
|-----------|-------------------|---------|
| `orden` | `nombre`, `estado`, `created_at` | `created_at` |
| `dir` | `asc`, `desc` | `desc` |

**Paginación** — igual que Contactos (pagina/limite).

---

### Oportunidades (`/api/oportunidades.php`) — pendiente

**Búsqueda textual** — parámetro `?buscar=`

| Campo | Tipo de match |
|-------|--------------|
| `titulo` | LIKE `%término%` |
| `descripcion` | LIKE `%término%` |

**Filtros**

| Parámetro | Valores | SQL |
|-----------|---------|-----|
| `etapa` | `prospecto`, `propuesta`, `negociacion`, `cerrada_ganada`, `cerrada_perdida` | `etapa = ?` |
| `asignado_a` | int (id_usuario) | `asignado_a = ?` |
| `valor_min` | decimal | `valor >= ?` |
| `valor_max` | decimal | `valor <= ?` |
| `cierre_desde` | fecha ISO | `fecha_cierre_esperada >= ?` |
| `cierre_hasta` | fecha ISO | `fecha_cierre_esperada <= ?` |

**Ordenación**

| Parámetro | Valores permitidos | Defecto |
|-----------|-------------------|---------|
| `orden` | `titulo`, `valor`, `etapa`, `fecha_cierre_esperada`, `created_at` | `created_at` |
| `dir` | `asc`, `desc` | `desc` |

**Vista pipeline** — `?vista=pipeline` agrupa por etapa sin paginar (devuelve un objeto `{ prospecto: [], propuesta: [], … }`).

---

### Actividades (`/api/actividades.php`) — pendiente

**Filtros** (sin búsqueda textual libre)

| Parámetro | Valores | SQL |
|-----------|---------|-----|
| `contacto_id` | int | `contacto_id = ?` |
| `lead_id` | int | `lead_id = ?` |
| `oportunidad_id` | int | `oportunidad_id = ?` |
| `tipo` | `nota`, `llamada`, `reunion`, `tarea`, `email` | `tipo = ?` |
| `completada` | `0`, `1` | `completada = ?` |
| `desde` | fecha ISO | `fecha >= ?` |
| `hasta` | fecha ISO | `fecha <= ?` |

**Ordenación**

| Parámetro | Valores permitidos | Defecto |
|-----------|-------------------|---------|
| `orden` | `fecha`, `tipo`, `created_at` | `fecha` |
| `dir` | `asc`, `desc` | `asc` |

**Paginación** — pagina/limite con defecto de 50 por ser listados de historial.

---

### Auditoría (`/api/auditoria.php`) ✅ implementado

| Parámetro | Descripción |
|-----------|-------------|
| `tabla` | Filtra por entidad (`contactos`, `leads`, …) |
| `registro_id` | Filtra por PK del registro |
| `limite` | Máx 200, defecto 50 |
| `offset` | Para paginación manual |

> La auditoría usa `offset` directo en lugar del patrón `pagina` por ser un log append-only.

---

## Patrón de implementación PHP (plantilla)

```php
// 1. Parámetros de entrada — whitelist de columnas ordenables
$columnasPermitidas = ['nombre', 'created_at', 'empresa'];
$orden  = in_array($_GET['orden'] ?? '', $columnasPermitidas) ? $_GET['orden'] : 'created_at';
$dir    = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$limite = min(max((int)($_GET['limite'] ?? 20), 1), 100);
$pagina = max((int)($_GET['pagina'] ?? 1), 1);
$offset = ($pagina - 1) * $limite;

// 2. Construcción dinámica de WHERE
$where  = [];
$params = [];

$buscar = trim($_GET['buscar'] ?? '');
if ($buscar !== '') {
    $like     = '%' . $buscar . '%';
    $where[]  = '(nombre LIKE ? OR apellidos LIKE ? OR email LIKE ? OR empresa LIKE ?)';
    array_push($params, $like, $like, $like, $like);
}

// Filtro por campo exacto (ejemplo: estado para leads)
if (!empty($_GET['estado'])) {
    $estadosValidos = ['nuevo', 'contactado', 'calificado', 'convertido', 'descartado'];
    if (in_array($_GET['estado'], $estadosValidos, true)) {
        $where[]  = 'estado = ?';
        $params[] = $_GET['estado'];
    }
}

$clausulaWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 3. Total para calcular páginas
$total   = (int) $pdo->prepare("SELECT COUNT(*) FROM tabla $clausulaWhere")
                     ->execute($params) ? /* fetchColumn */ 0 : 0;
$paginas = (int) ceil($total / $limite);

// 4. Consulta paginada
$sql = "SELECT * FROM tabla $clausulaWhere ORDER BY $orden $dir LIMIT ? OFFSET ?";
$s   = $pdo->prepare($sql);
$s->execute([...$params, $limite, $offset]);

ok([
    'data'  => $s->fetchAll(),
    'meta'  => compact('total', 'pagina', 'limite', 'paginas'),
]);
```

---

## Patrón JS (plantilla para futuros módulos)

```js
// Estado del listado
let estadoBusqueda = { buscar: '', estado: '', orden: 'created_at', dir: 'desc', pagina: 1 };
let buscarTimer = null;

// Construir query string desde el estado
function buildQuery(estado) {
    const p = new URLSearchParams();
    Object.entries(estado).forEach(([k, v]) => { if (v) p.set(k, v); });
    return p.toString() ? '?' + p.toString() : '';
}

// Cargar con estado actual
async function cargar() {
    const r = await fetchSeguro('/api/recurso.php' + buildQuery(estadoBusqueda));
    const d = await r.json();
    if (d.ok) {
        renderTabla(d.data);
        renderPaginacion(d.meta);
    }
}

// Debounce en el buscador
inputBuscar.addEventListener('input', e => {
    clearTimeout(buscarTimer);
    estadoBusqueda.buscar = e.target.value.trim();
    estadoBusqueda.pagina = 1;
    buscarTimer = setTimeout(cargar, 400);
});
```

---

## Componente de paginación (UI)

La paginación se renderiza como una barra debajo de la tabla:

```
← Anterior   1  2  [3]  4  5   Siguiente →     Mostrando 41–60 de 87
```

- Se muestran máximo 5 páginas visibles con `…` si hay más
- Los botones anterior/siguiente se deshabilitan en los extremos
- Al cambiar de página se hace scroll al `section-header` activo

---

## Estado actual por módulo

| Módulo | Búsqueda textual | Filtros | Ordenación | Paginación |
|--------|:---:|:---:|:---:|:---:|
| Contactos | ✅ implementado | ✅ empresa, desde, hasta | ✅ nombre/empresa/created_at + dir | ✅ implementado |
| Leads | ✅ implementado | ✅ estado, origen, desde, hasta | ✅ nombre/estado/created_at + dir | ✅ implementado |
| Oportunidades | ✅ implementado (+ descripción) | ✅ etapa, valor_min/max, cierre_desde/hasta | ✅ titulo/valor/etapa/fecha_cierre/created_at + dir | ❌ pendiente |
| Facturas | ✅ numero/contacto/empresa/email | ✅ estado, desde, hasta | ✅ numero/fecha_emision/fecha_vencimiento/total/estado + dir | ✅ implementado |
| Actividades | ✅ por entidad_id | ❌ pendiente | ❌ pendiente | ❌ pendiente |
| Auditoría | ✅ por tabla/accion/registro_id | ✅ tabla (whitelist), accion (whitelist) | — | ✅ offset/limite + total |
