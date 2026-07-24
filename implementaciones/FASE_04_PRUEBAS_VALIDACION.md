# Fase 04 - Pruebas y Validación

Objetivo: asegurar que el CRM funciona, que las reglas de seguridad se respetan y que no hay regresiones.

## Estado actual
- Avance estimado: `0%`
- Pendiente: crear plan de pruebas formal y ejecutar pruebas de seguridad/usabilidad.

## Checklist de tareas

### Planificación
- [ ] Elaborar plan de pruebas (funcionales, regresión, humo)
- [ ] Definir criterios de aceptación por módulo

### Flujos críticos
- [ ] Autenticación: login correcto, login incorrecto, cierre de sesión, acceso directo sin sesión
- [ ] RBAC: acceder a secciones admin sin serlo debe ser denegado (UI + API)
- [ ] CRUD contactos: alta, edición, detalle lateral con actividades, baja con confirmación
- [ ] CRUD leads: alta, edición, conversión a contacto, estado "convertido" bloqueado
- [ ] Pipeline oportunidades: alta, edición, cambio de etapa con confirm, filtros avanzados
- [ ] Actividades: crear/editar/eliminar ligadas a contactos, leads y oportunidades
- [ ] Dominios: alta, edición con SSL toggle, validación formato dominio e IP, eliminación
- [ ] Cuentas de correo: alta, edición, cuota formateada (MB/GB/sin límite), eliminación
- [ ] Gestión de usuarios (admin): alta, edición de rol, protección auto-eliminación y último admin
- [ ] Auditoría (admin): diff expandible, filtros por entidad y acción, paginación
- [ ] Bases de datos (admin): estadísticas actualizadas, botón phpMyAdmin
- [ ] Copias de seguridad (admin): crear, descargar, eliminar
- [ ] Configuración: cambiar nombre/email, cambiar contraseña (verificación actual), selector de tema
- [ ] Tema claro/oscuro: persiste en localStorage, contraste en todos los módulos

### Búsqueda y filtros
- [ ] Búsqueda debounced (400 ms) en todos los listados CRM y hosting
- [ ] Filtros avanzados: panel colapsable, badge de filtros activos, botón "Limpiar"
- [ ] Paginación: navegar entre páginas, cambio de página resetea filtros correctamente
- [ ] Ordenación asc/desc con icono reactivo

### Seguridad
- [ ] Inyección SQL: inputs con caracteres especiales (`'`, `"`, `;`, `--`)
- [ ] XSS: campos con `<script>`, `<img onerror>`, `javascript:` — todos deben ser escapados
- [ ] CSRF: enviar PUT/POST/DELETE sin `X-CSRF-Token` → debe devolver `403`
- [ ] Bypass de autorización: acceder a endpoints admin con rol `usuario` → debe devolver `403`
- [ ] Verificar que `config/` es inaccesible directamente desde el navegador
- [ ] Auditoría registrada en todas las operaciones CRUD

### Accesibilidad y UX
- [ ] Contraste WCAG AA en tema claro y oscuro
- [ ] Navegación por teclado en formularios y modales
- [ ] Estados vacíos (`crm-empty`) y carga (`crm-loading`) visibles en todos los listados
- [ ] Mensajes de error claros en formularios (validación inline)
- [ ] Toasts de confirmación/error con tiempo adecuado

## Entregables recomendados
- Lista de casos de prueba ejecutados
- Registro de incidencias (bugs) y estado (abierto/corregido)
- Evidencia de pruebas (capturas o notas del entorno)

## Criterio de "Hecho"
Los flujos críticos pasan sin fallos y los problemas de seguridad de severidad alta quedan resueltos o documentados con mitigación.

## Referencia de rutas (smoke manual)
- Login: `/modules/site/login.html` → POST a `/auth/login.php`
- Panel: `/modules/dashboard/cpanel.php` (requiere sesión activa)
- Sesión en cliente: `GET /api/get_user.php`
- Panel admin (guard): `/admin/cpanel.php` → redirige a `/modules/dashboard/cpanel.php`
