# Seguimiento - CRM

Usa este archivo como tablero. Marca cada tarea como `[x]` cuando este completada.

## Resumen de avance (global)
- Peso total del proyecto: `100%`
- Formula recomendada: `avance_global = suma(avance_fase * peso_fase)`
- Avance estimado actual del proyecto: `38%` (Fase 01 cerrada al 100%; resto de fases según backlog)

## Pesos por fase
- Fase 01 - Analisis y Planificacion: `15%`
- Fase 02 - Diseno y Arquitectura: `20%`
- Fase 03 - Implementacion y Desarrollo: `40%`
- Fase 04 - Pruebas y Validacion: `15%`
- Fase 05 - Despliegue y Documentacion: `10%`

## Fase 01 - Analisis y Planificacion (Peso: 15% | Avance: 100%)
- [x] Definir alcance del MVP (que entra / que no entra)
- [x] Definir actores y roles (admin, agente, vendedor, etc.)
- [x] Escribir historias de usuario (epics) con criterios de aceptacion → [`MVP_HISTORIAS_Y_CRITERIOS.md`](MVP_HISTORIAS_Y_CRITERIOS.md)
- [x] Definir requisitos no funcionales (seguridad, rendimiento, auditoria)
- [x] Definir entidades principales del CRM (alto nivel)
- [x] Plan de entregables (documentos, prototipos y versiones)

## Fase 02 - Diseno y Arquitectura (Peso: 20% | Avance: 40%)
- [ ] Modelar datos (tablas/relaciones) para el MVP
- [x] Definir flujo de autenticacion y permisos (RBAC)
- [x] Prototipos UI/UX (pantallas basicas) y mapa de navegacion
- [ ] Definir API/contratos (endpoints o acciones) para cada modulo
- [ ] Disenar logica de auditoria y seguridad (CSRF/XSS/validaciones)

## Fase 03 - Implementacion y Desarrollo (Peso: 40% | Avance: 25%)
- [x] Preparar estructura del proyecto (carpetas, convenciones, rutas)
- [x] Implementar autenticacion + sesion
- [ ] Implementar CRUD base para: clientes/contactos (MVP)
- [ ] Implementar CRUD para: leads (MVP)
- [ ] Implementar oportunidades + pipeline por etapas
- [ ] Implementar actividades/notas (seguimiento)
- [ ] Implementar busqueda, filtros y paginacion basica
- [ ] Integrar validaciones y manejo de errores consistente

## Fase 04 - Pruebas y Validacion (Peso: 15% | Avance: 0%)
- [ ] Plan de pruebas (funcionales y de regresion)
- [ ] Pruebas manuales de flujos criticos (login -> CRUD -> pipeline)
- [ ] Pruebas de seguridad basicas (inyeccion, XSS, CSRF, roles)
- [ ] Revisar accesibilidad basica (navegacion por teclado, contraste)
- [ ] Cerrar brechas de usabilidad (formularios, mensajes, estados vacios)

## Fase 05 - Despliegue y Documentacion (Peso: 10% | Avance: 40%)
- [x] Preparar entorno (config de DB, variables, y script de deploy)
- [x] Documentar instalacion/ejecucion (README del proyecto)
- [x] Documentar arquitectura (modelo de datos + flujos)
- [ ] Ejecutar prueba final end-to-end con un caso realista
- [ ] Tag/version para entrega

## Posibles mejoras (post-MVP)

### Mejoras de alto impacto (prioridad alta)
- [ ] Dashboard comercial con metricas (estado: pendiente | impacto: alto | esfuerzo: medio)
- [ ] Importacion/exportacion CSV para contactos, leads y oportunidades (estado: pendiente | impacto: alto | esfuerzo: bajo)
- [ ] Notificaciones y recordatorios de tareas (estado: pendiente | impacto: alto | esfuerzo: medio)
- [ ] Historial/auditoria visible por entidad (estado: pendiente | impacto: alto | esfuerzo: medio)
- [ ] Busqueda global unificada (estado: pendiente | impacto: alto | esfuerzo: medio)

### Mejoras tecnicas (prioridad media)
- [ ] API REST versionada para integraciones futuras (estado: pendiente | impacto: medio-alto | esfuerzo: medio-alto)
- [ ] Tests automatizados minimos (smoke + integracion) (estado: pendiente | impacto: alto | esfuerzo: medio)
- [ ] Hardening de seguridad adicional (rate limit, bloqueo por intentos, CSP) (estado: pendiente | impacto: alto | esfuerzo: medio)
- [ ] Logging estructurado para incidencias y soporte (estado: pendiente | impacto: medio | esfuerzo: medio)
- [ ] Unificar o eliminar JS duplicado entre `public/assets/js/` y la carpeta historica `LANDJ/` (estado: pendiente | impacto: medio | esfuerzo: bajo)

### Mejoras de producto (prioridad media-baja)
- [ ] Modulo de etiquetas y segmentacion de contactos (estado: pendiente | impacto: medio | esfuerzo: medio)
- [ ] Plantillas de email y seguimiento de envios (estado: pendiente | impacto: medio | esfuerzo: medio)
- [ ] Kanban drag-and-drop real para pipeline (estado: pendiente | impacto: medio-alto | esfuerzo: medio)
- [ ] Informes exportables (PDF/CSV) por periodo (estado: pendiente | impacto: medio | esfuerzo: bajo)
- [ ] Multilenguaje y personalizacion basica de interfaz (estado: pendiente | impacto: medio-bajo | esfuerzo: medio)

## Siguiente iteracion recomendada (3 mejoras concretas)
- [ ] Implementar CRUD de contactos (base del CRM)
- [ ] Implementar CRUD de leads + conversion a oportunidad
- [ ] Endurecer login (rate limit, intentos fallidos y mensajes no reveladores)

