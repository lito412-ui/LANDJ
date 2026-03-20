# Fase 04 - Pruebas y Validacion

Objetivo: asegurar que el CRM funciona, que las reglas de seguridad se respetan y que no hay regresiones.

## Estado actual
- Avance estimado: `0%`
- Pendiente principal: crear plan de pruebas formal y ejecutar pruebas de seguridad/usabilidad.

## Checklist de tareas
- [ ] Elaborar plan de pruebas (funcionales, regresion, humo)
- [ ] Definir criterios de aceptacion por modulo (login, contactos, leads, pipeline, actividades)
- [ ] Ejecutar pruebas manuales de flujos criticos
- [ ] Probar acceso con roles distintos (autenticado vs no autenticado; permisos por rol)
- [ ] Probar CRUD completo en contactos/clientes (alta, edicion, detalle, baja con confirmacion)
- [ ] Probar CRUD completo en leads (alta, edicion, transformacion)
- [ ] Probar pipeline (cambio de etapa y persistencia)
- [ ] Probar actividades/notas (crear/editar/ver por entidad)
- [ ] Probar busqueda y filtros (resultados correctos y consistentes)
- [ ] Probar paginacion (si aplica) y ordenamiento definido
- [ ] Probar validaciones (servidor rechaza datos invalidos)
- [ ] Probar manejo de errores (mensajes claros, sin fugas de informacion)
- [ ] Pruebas de seguridad basicas
- [ ] Probar inyeccion SQL (inputs con caracteres especiales)
- [ ] Probar XSS (campos con etiquetas y scripts)
- [ ] Probar CSRF (acciones sensibles sin token -> debe fallar)
- [ ] Probar bypass de autorizacion (acceder a endpoints directos sin rol)
- [ ] Verificar proteccion de datos (solo se ven datos del alcance del rol)
- [ ] Revisar auditoria (logs de cambios en acciones clave)

## Entregables recomendados
- Lista de casos de prueba (manual)
- Registro de incidencias (bugs) y estado (abierto/corregido)
- Evidencia de pruebas (capturas o notas del entorno)

## Criterio de "Hecho"
- Los flujos criticos pasan sin fallos y los problemas de seguridad de severidad alta quedan resueltos o documentados con mitigacion.

