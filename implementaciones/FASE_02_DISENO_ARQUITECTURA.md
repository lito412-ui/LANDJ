# Fase 02 - Diseño y Arquitectura

Objetivo: transformar el análisis en una propuesta concreta de datos, pantallas y contratos (API).

## Estado actual
- Avance estimado: `100%`
- Completado: modelo de datos (5 tablas + auditoria), flujo de auth, RBAC, prototipos UI, mapa de navegación, contratos de API, esquema de auditoría, diseño de validaciones por entidad y seguridad anti-CSRF/XSS implementada (token + custom header + CSP + cabeceras HTTP).

## Checklist de tareas
- [x] Modelar datos del MVP (tablas: `usuarios`, `contactos`, `leads`, `oportunidades`, `actividades`)
- [x] Definir relaciones y reglas de integridad (FKs con ON DELETE, unicidad, NOT NULL)
- [x] Definir esquema para auditoría (quien/qué/cuando; cambios relevantes)
- [x] Diseñar flujo de autenticación (sesión PHP) y cierre de sesión
- [x] Diseñar RBAC (permisos por rol `usuario` / `administrador`)
- [x] Diseñar validaciones de entrada (tipo, formato, longitudes) y manejo de errores → [`VALIDACIONES.md`](./VALIDACIONES.md)
- [x] Definir seguridad anti-CSRF/anti-XSS (encoding, tokens, cabeceras) → [`SEGURIDAD_CSRF_XSS.md`](./SEGURIDAD_CSRF_XSS.md)
- [x] Prototipar pantallas principales (login, dashboard, listados, formularios, pipeline)
- [x] Definir mapa de navegación (de la vista principal a cada módulo)
- [x] Definir contratos de endpoints/acciones (rutas, payloads, respuestas y códigos de error)
- [x] Definir búsqueda/filtros (campos y paginación) → [`BUSQUEDA_FILTROS.md`](./BUSQUEDA_FILTROS.md)
- [x] Preparar estrategia de archivos/adjuntos → **excluido del MVP** (ver detalle abajo)

## Modelo de datos implementado (`database/init.sql`)

### Tablas y relaciones

| Tabla | Descripción | FK principales |
|-------|-------------|----------------|
| `auditoria` | Log de cambios: tabla, registro, acción, usuario, JSON antes/después | → `usuarios` (ON DELETE SET NULL) |
| `usuarios` | Cuentas del sistema con rol y hash Argon2id | — |
| `contactos` | Clientes/contactos del CRM | → `usuarios` (creado_por) |
| `leads` | Prospectos con estado y origen | → `usuarios`, → `contactos` (si convertido) |
| `oportunidades` | Negociaciones en pipeline por etapas | → `contactos`, → `leads`, → `usuarios` (asignado_a, creado_por) |
| `actividades` | Notas/tareas/llamadas ligadas a entidades | → `contactos`, → `leads`, → `oportunidades` |

### Etapas definidas (ENUMs)
- **leads.estado**: `nuevo`, `contactado`, `calificado`, `convertido`, `descartado`
- **oportunidades.etapa**: `prospecto`, `propuesta`, `negociacion`, `cerrada_ganada`, `cerrada_perdida`
- **actividades.tipo**: `nota`, `llamada`, `reunion`, `tarea`, `email`

## Entregables recomendados
- [x] Modelo de datos (esquema SQL en `database/init.sql`)
- [x] Matriz de permisos RBAC (en `MVP_HISTORIAS_Y_CRITERIOS.md`)
- [x] Lista de endpoints/acciones con ejemplos de request/response → [`CONTRATOS_API.md`](./CONTRATOS_API.md)
- [x] Prototipos UI/UX (vistas implementadas en `modules/dashboard/partials/sections/`)

## Criterio de "Hecho"
- Existe una especificación suficiente para implementar sin adivinar (datos + permisos + contratos + UI básica).

## Estrategia de archivos/adjuntos — excluido del MVP

### Decisión

Los adjuntos quedan **fuera del alcance del MVP**. Razones:

- Añaden una superficie de ataque significativa (validación de tipo MIME, path traversal, malware) que requiere más tiempo de hardening del que justifica el valor en el MVP.
- Necesitan una estrategia de almacenamiento decidida (disco local con volumen Docker vs. almacenamiento externo) antes de implementar.
- Las funciones core del CRM (contactos, leads, pipeline, actividades) no dependen de ellos.
- Se pueden añadir en una fase posterior sin cambios disruptivos al esquema existente (solo una tabla nueva y un endpoint nuevo).

### Diseño previsto para fase posterior

**Tabla `adjuntos`** (no creada en el MVP):

```sql
CREATE TABLE `adjuntos` (
  `id_adjunto`   INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre`       VARCHAR(255) NOT NULL,          -- nombre original del archivo
  `ruta`         VARCHAR(500) NOT NULL,          -- ruta interna (nunca expuesta al cliente)
  `mime`         VARCHAR(100) NOT NULL,          -- tipo MIME validado en servidor
  `tamaño`       INT          NOT NULL,          -- bytes
  `contacto_id`  INT          DEFAULT NULL,
  `lead_id`      INT          DEFAULT NULL,
  `oportunidad_id` INT        DEFAULT NULL,
  `subido_por`   INT          NOT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_adj_contacto`    FOREIGN KEY (`contacto_id`)    REFERENCES `contactos`(`id_contacto`)       ON DELETE CASCADE,
  CONSTRAINT `fk_adj_lead`        FOREIGN KEY (`lead_id`)        REFERENCES `leads`(`id_lead`)               ON DELETE CASCADE,
  CONSTRAINT `fk_adj_oportunidad` FOREIGN KEY (`oportunidad_id`) REFERENCES `oportunidades`(`id_oportunidad`) ON DELETE CASCADE,
  CONSTRAINT `fk_adj_usuario`     FOREIGN KEY (`subido_por`)     REFERENCES `usuarios`(`id_usuario`)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Reglas de validación previstas:**

| Regla | Valor |
|-------|-------|
| Tipos permitidos | `application/pdf`, `image/jpeg`, `image/png`, `image/webp` |
| Tamaño máximo | 10 MB por fichero |
| Validación | MIME real (finfo), no solo extensión |
| Almacenamiento | `storage/adjuntos/<entidad>/<id>/` fuera de `public/` |
| Servir ficheros | Via `public/api/adjuntos.php?id=<id>` con verificación de sesión (nunca ruta directa) |
| Nombre en disco | UUID generado, no el nombre original (evita path traversal) |

**Endpoints previstos:** `GET`, `POST` (upload multipart), `DELETE` en `/api/adjuntos.php`.

## Implementación vigente (referencia)
- Esquema completo: [`database/init.sql`](../database/init.sql)
- Estructura de carpetas y rutas: [`README.md`](../README.md)
