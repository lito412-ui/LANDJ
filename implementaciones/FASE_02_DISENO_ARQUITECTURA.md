# Fase 02 - Diseno y Arquitectura

Objetivo: transformar el analisis en una propuesta concreta de datos, pantallas y contratos (API).

## Estado actual
- Avance estimado: `40%`
- Pendiente principal: cerrar diseno de datos y contrato de API para pasar a un CRM funcional.

## Checklist de tareas
- [ ] Modelar datos del MVP (tablas: usuarios, roles, contactos, leads, oportunidades, actividades, etc.)
- [ ] Definir relaciones y reglas de integridad (claves, restricciones, unicidad)
- [ ] Definir esquema para auditoria (quien/que/cuando; cambios relevantes)
- [x] Disenar flujo de autenticacion (sesion/JWT segun stack) y cierre de sesion
- [x] Disenar RBAC (permisos por rol) para cada modulo
- [ ] Disenar validaciones de entrada (tipo, formato, longitudes) y manejo de errores
- [ ] Definir seguridad anti-CSRF/anti-XSS (encoding, tokens, cabeceras, etc.)
- [x] Prototipar pantallas principales (login, dashboard, listados, formularios, pipeline)
- [x] Definir mapa de navegacion (de la vista principal a cada modulo)
- [ ] Definir contratos de endpoints/acciones (rutas, payloads, respuestas y codigos de error)
- [ ] Definir busqueda/filtros (campos y paginacion)
- [ ] Preparar estrategia de archivos/adjuntos (si entra en el MVP) o excluirlo del alcance

## Entregables recomendados
- Modelo de datos (diagrama ER o esquema SQL)
- Matriz de permisos RBAC
- Lista de endpoints/acciones con ejemplos de request/response
- Prototipos UI/UX (wireframes simples)

## Criterio de "Hecho"
- Existe una especificacion suficiente para implementar sin adivinar (datos + permisos + contratos + UI basica).

