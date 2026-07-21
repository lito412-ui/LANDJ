# Fase 01 - Analisis y Planificacion

Objetivo: concretar que vas a construir (MVP), para quien y con que criterios de calidad.

## Estado actual
- Avance estimado: `100%`
- Cerrada con el documento de historias y criterios: [`MVP_HISTORIAS_Y_CRITERIOS.md`](MVP_HISTORIAS_Y_CRITERIOS.md).

## Checklist de tareas
- [x] Recolectar requisitos funcionales (que tareas hara el CRM)
- [x] Recolectar requisitos no funcionales (seguridad, auditoria, rendimiento, disponibilidad)
- [x] Definir actores y roles (quien ve/crea/edita/elimina)
- [x] Definir alcance del MVP (prioridad MoSCoW o similar)
- [x] Escribir historia/epic: login y gestion de usuarios
- [x] Escribir historia/epic: clientes/contactos
- [x] Escribir historia/epic: leads
- [x] Escribir historia/epic: oportunidades + pipeline
- [x] Escribir historia/epic: actividades/notas
- [x] Escribir historia/epic: busqueda y filtros
- [x] Definir criterios de aceptacion por historia (Given/When/Then o equivalente)
- [x] Definir supuestos y dependencias (DB, librerias, hosting, correo, etc.)
- [x] Definir riesgos (seguridad de acceso, integridad de datos, complejidad UI)
- [x] Definir plan de comunicacion y cadencia (si trabajas con sprints)

## Entregables recomendados
- Alcance MVP (documento corto) → ver tabla de alcance en [`MVP_HISTORIAS_Y_CRITERIOS.md`](MVP_HISTORIAS_Y_CRITERIOS.md)
- Lista de historias con criterios de aceptacion → mismo archivo (epics 1–6)
- Matriz de roles/permisos (alto nivel) → sección “Matriz de permisos” en [`MVP_HISTORIAS_Y_CRITERIOS.md`](MVP_HISTORIAS_Y_CRITERIOS.md)
- Lista de requisitos no funcionales (priorizados) → sección “Requisitos no funcionales” en [`MVP_HISTORIAS_Y_CRITERIOS.md`](MVP_HISTORIAS_Y_CRITERIOS.md)

## Criterio de "Hecho"
- Hay un MVP claramente delimitado y las historias tienen criterios de aceptacion verificables.

## Alcance técnico ya cubierto en el repo (referencia)
- Panel web, login y despliegue Docker descritos en [`README.md`](../README.md); detalle de carpetas en [`implementaciones/README.md`](README.md).

## Siguientes pasos (Fase 02)
1. Modelar datos: tablas `contactos`, `leads`, `oportunidades`, `actividades` (y relaciones) acordes a las historias del MVP.
2. Definir contratos de API o acciones PHP por módulo (rutas, parámetros, respuestas).
3. Detallar reglas de integridad (FK, unicidad, borrado) y estrategia de permisos por rol en cada entidad.
