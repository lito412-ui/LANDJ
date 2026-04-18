# Fase 02 - Diseño y Arquitectura

Objetivo: transformar el análisis en una propuesta concreta de datos, pantallas y contratos (API).

## Estado actual
- Avance estimado: `60%`
- Completado: modelo de datos completo con 5 tablas y relaciones, flujo de auth, RBAC, prototipos UI y mapa de navegación.
- Pendiente principal: contratos de API por módulo, validaciones de entrada y estrategia de seguridad CSRF/XSS.

## Checklist de tareas
- [x] Modelar datos del MVP (tablas: `usuarios`, `contactos`, `leads`, `oportunidades`, `actividades`)
- [x] Definir relaciones y reglas de integridad (FKs con ON DELETE, unicidad, NOT NULL)
- [ ] Definir esquema para auditoría (quien/qué/cuando; cambios relevantes)
- [x] Diseñar flujo de autenticación (sesión PHP) y cierre de sesión
- [x] Diseñar RBAC (permisos por rol `usuario` / `administrador`)
- [ ] Diseñar validaciones de entrada (tipo, formato, longitudes) y manejo de errores
- [ ] Definir seguridad anti-CSRF/anti-XSS (encoding, tokens, cabeceras)
- [x] Prototipar pantallas principales (login, dashboard, listados, formularios, pipeline)
- [x] Definir mapa de navegación (de la vista principal a cada módulo)
- [ ] Definir contratos de endpoints/acciones (rutas, payloads, respuestas y códigos de error)
- [ ] Definir búsqueda/filtros (campos y paginación)
- [ ] Preparar estrategia de archivos/adjuntos o excluirlo del alcance

## Modelo de datos implementado (`database/init.sql`)

### Tablas y relaciones

| Tabla | Descripción | FK principales |
|-------|-------------|----------------|
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
- [ ] Lista de endpoints/acciones con ejemplos de request/response
- [x] Prototipos UI/UX (vistas implementadas en `modules/dashboard/partials/sections/`)

## Criterio de "Hecho"
- Existe una especificación suficiente para implementar sin adivinar (datos + permisos + contratos + UI básica).

## Implementación vigente (referencia)
- Esquema completo: [`database/init.sql`](../database/init.sql)
- Estructura de carpetas y rutas: [`README.md`](../README.md)
