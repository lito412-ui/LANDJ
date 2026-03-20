# Fase 03 - Implementacion y Desarrollo

Objetivo: construir el CRM de forma incremental hasta el MVP funcionando end-to-end.

## Estado actual
- Avance estimado: `25%`
- Ya implementado: login/logout con sesion, estructura base Docker/PHP/MySQL y panel inicial.
- Pendiente principal: modulos core CRM (contactos, leads, oportunidades, actividades) con persistencia real.

## Checklist de tareas (MVP primero)
- [x] Preparar estructura de proyecto (carpetas, convenciones, nombrado, rutas)
- [x] Configurar entorno local (DB, variables de entorno, seeds de datos)
- [ ] Implementar capa de acceso a datos (consultas parametrizadas / ORM si aplica)
- [ ] Implementar migraciones o scripts de creacion de tablas del MVP
- [x] Implementar autenticacion (hash de contrasena, login, logout, sesion)
- [x] Implementar control de acceso (RBAC) para rutas y acciones
- [ ] Implementar CRUD base para contactos/clientes
- [ ] Implementar pantalla/listado de contactos (filtros basicos)
- [ ] Implementar pantalla/formulario de contacto (crear/editar)
- [ ] Implementar vista de detalle de contacto
- [ ] Implementar eliminacion con confirmacion y reglas de integridad
- [ ] Implementar CRUD base para leads
- [ ] Implementar pantalla/listado de leads (filtros basicos)
- [ ] Implementar pantalla/formulario de lead (crear/editar)
- [ ] Implementar transformacion de lead (a contacto o a oportunidad) segun MVP
- [ ] Implementar manejo de pipeline
- [ ] Definir etapas del pipeline y persistirlas
- [ ] Implementar listado de oportunidades agrupadas por etapa
- [ ] Implementar movimiento de oportunidades entre etapas (segun UX definida)
- [ ] Implementar CRUD para actividades/notas
- [ ] Implementar asociacion de actividades con contacto/lead/oportunidad
- [ ] Implementar listado de actividades (por entidad y por usuario)
- [ ] Implementar formulario de actividad (crear/editar) y detalle
- [ ] Implementar busqueda y filtros en listados (campos acordados en fase 02)
- [ ] Implementar paginacion basica en listados (si aplica)
- [ ] Implementar validaciones de servidor (y mensajes claros en UI)
- [ ] Implementar manejo de errores consistente (codigos y mensajes)
- [ ] Implementar auditoria de cambios (al menos para cambios de estado y ediciones importantes)
- [ ] Actualizar documentacion tecnica minima (README del modulo + endpoints principales)

## Checklist de UX (minimo viable)
- [x] Navegacion consistente (menu y estados activos)
- [x] Formularios con validaciones y feedback (exitos/errores)
- [ ] Estados vacios (sin datos) y mensajes de carga si aplica
- [ ] Permisos reflejados en la UI (botones/acciones segun rol)

## Criterio de "Hecho"
- El usuario puede: entrar, gestionar contactos, gestionar leads, mover oportunidades en el pipeline y crear actividades, con permisos correctos.

