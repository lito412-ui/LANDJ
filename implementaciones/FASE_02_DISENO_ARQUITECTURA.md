# Fase 02 - Diseño y Arquitectura

Objetivo: transformar el análisis en una propuesta concreta de datos, pantallas y contratos (API).

## Estado actual
- Avance estimado: `100%`
- Completado: modelo de datos (8 tablas + auditoria), flujo de auth, RBAC, prototipos UI, mapa de navegación, contratos de API, esquema de auditoría, diseño de validaciones por entidad y seguridad anti-CSRF/XSS implementada.

## Checklist de tareas
- [x] Modelar datos del MVP: `usuarios`, `contactos`, `leads`, `oportunidades`, `actividades`, `auditoria`
- [x] Ampliar modelo con hosting: `dominios`, `cuentas_correo`
- [x] Definir relaciones y reglas de integridad (FKs con ON DELETE, unicidad, NOT NULL)
- [x] Definir esquema para auditoría (quien/qué/cuando; cambios relevantes)
- [x] Diseñar flujo de autenticación (sesión PHP) y cierre de sesión
- [x] Diseñar RBAC (permisos por rol `usuario` / `administrador`)
- [x] Diseñar validaciones de entrada (tipo, formato, longitudes) → [`VALIDACIONES.md`](./VALIDACIONES.md)
- [x] Definir seguridad anti-CSRF/anti-XSS (encoding, tokens, cabeceras) → [`SEGURIDAD_CSRF_XSS.md`](./SEGURIDAD_CSRF_XSS.md)
- [x] Prototipar pantallas principales (login, dashboard, listados, formularios, pipeline)
- [x] Definir mapa de navegación (de la vista principal a cada módulo)
- [x] Definir contratos de endpoints/acciones → [`CONTRATOS_API.md`](./CONTRATOS_API.md)
- [x] Definir búsqueda/filtros (campos y paginación) → [`BUSQUEDA_FILTROS.md`](./BUSQUEDA_FILTROS.md)
- [x] Preparar estrategia de archivos/adjuntos → **excluido del MVP** (ver detalle abajo)

---

## Modelo de datos implementado (`database/init.sql` + migrations 005/006)

### Tablas y relaciones

| Tabla | Descripción | FK principales |
|-------|-------------|----------------|
| `usuarios` | Cuentas del sistema con rol y hash Argon2id | — |
| `contactos` | Clientes/contactos del CRM | → `usuarios` (creado_por) |
| `leads` | Prospectos con estado y origen | → `usuarios`, → `contactos` (si convertido) |
| `oportunidades` | Negociaciones en pipeline por etapas | → `contactos`, → `leads`, → `usuarios` |
| `actividades` | Notas/tareas/llamadas ligadas a entidades | → `contactos`, → `leads`, → `oportunidades` |
| `auditoria` | Log de cambios: tabla, registro, acción, JSON antes/después | → `usuarios` (ON DELETE SET NULL) |
| `dominios` | Dominios web con tipo, estado, IP y SSL | — |
| `cuentas_correo` | Cuentas de email con cuota y estado | — |

### ENUMs definidos

| Tabla | Campo | Valores |
|-------|-------|---------|
| `leads` | `estado` | `nuevo`, `contactado`, `calificado`, `convertido`, `descartado` |
| `oportunidades` | `etapa` | `prospecto`, `propuesta`, `negociacion`, `cerrada_ganada`, `cerrada_perdida` |
| `actividades` | `tipo` | `nota`, `llamada`, `reunion`, `tarea`, `email` |
| `dominios` | `tipo` | `principal`, `subdominio`, `addon`, `parked` |
| `dominios` | `estado` | `activo`, `pendiente`, `suspendido` |
| `cuentas_correo` | `estado` | `activo`, `suspendido` |

---

## Entregables
- [x] Modelo de datos (`database/init.sql` + migrations)
- [x] Matriz de permisos RBAC (`MVP_HISTORIAS_Y_CRITERIOS.md`)
- [x] Contratos de API (`CONTRATOS_API.md`)
- [x] Prototipos UI implementados en `modules/dashboard/partials/sections/`

## Criterio de "Hecho"
Existe una especificación suficiente para implementar sin adivinar: datos, permisos, contratos y UI básica.

---

## Estrategia de archivos/adjuntos — excluido del MVP

### Decisión
Los adjuntos quedan **fuera del alcance del MVP**. Razones:
- Añaden una superficie de ataque significativa que requiere más tiempo de hardening.
- Necesitan una estrategia de almacenamiento decidida (disco local vs. almacenamiento externo).
- Las funciones core del CRM no dependen de ellos.
- Se pueden añadir en una fase posterior sin cambios disruptivos al esquema.

### Diseño previsto para fase posterior

**Tabla `adjuntos`** (no creada en el MVP):

```sql
CREATE TABLE `adjuntos` (
  `id_adjunto`     INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre`         VARCHAR(255) NOT NULL,
  `ruta`           VARCHAR(500) NOT NULL,
  `mime`           VARCHAR(100) NOT NULL,
  `tamaño`         INT          NOT NULL,
  `contacto_id`    INT          DEFAULT NULL,
  `lead_id`        INT          DEFAULT NULL,
  `oportunidad_id` INT          DEFAULT NULL,
  `subido_por`     INT          NOT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_adj_contacto`    FOREIGN KEY (`contacto_id`)    REFERENCES `contactos`(`id_contacto`)        ON DELETE CASCADE,
  CONSTRAINT `fk_adj_lead`        FOREIGN KEY (`lead_id`)        REFERENCES `leads`(`id_lead`)                ON DELETE CASCADE,
  CONSTRAINT `fk_adj_oportunidad` FOREIGN KEY (`oportunidad_id`) REFERENCES `oportunidades`(`id_oportunidad`) ON DELETE CASCADE,
  CONSTRAINT `fk_adj_usuario`     FOREIGN KEY (`subido_por`)     REFERENCES `usuarios`(`id_usuario`)          ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Reglas de validación previstas:**

| Regla | Valor |
|-------|-------|
| Tipos permitidos | `application/pdf`, `image/jpeg`, `image/png`, `image/webp` |
| Tamaño máximo | 10 MB por fichero |
| Validación | MIME real (finfo), no solo extensión |
| Almacenamiento | `storage/adjuntos/<entidad>/<id>/` fuera de `public/` |
| Servir ficheros | Via `public/api/adjuntos.php?id=<id>` con verificación de sesión |
| Nombre en disco | UUID generado (evita path traversal) |

**Endpoints previstos:** `GET`, `POST` (multipart), `DELETE` en `/api/adjuntos.php`.

---

## Implementación vigente (referencia)
- Esquema completo: [`database/init.sql`](../database/init.sql)
- Estructura de carpetas: [`README.md`](../README.md)
- Contratos API: [`CONTRATOS_API.md`](./CONTRATOS_API.md)
