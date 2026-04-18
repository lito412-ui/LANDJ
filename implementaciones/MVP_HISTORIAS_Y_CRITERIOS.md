# MVP — Historias de usuario y criterios de aceptación

Documento de cierre de **Fase 01 — Análisis y planificación**: epics alineados con el alcance del CRM del proyecto L&J y criterios verificables (Given / When / Then).

## Alcance del MVP (resumen)

| Área | Incluido en MVP | Fuera de MVP (post-entrega) |
|------|-----------------|----------------------------|
| Autenticación y usuarios | Login, sesión, roles básicos, alta controlada de usuarios | SSO, 2FA, recuperación de contraseña por email |
| Contactos | CRUD de clientes/contactos con datos mínimos | Importación masiva, duplicados avanzados |
| Leads | CRUD y conversión a contacto u oportunidad | Scoring automático, integraciones externas |
| Oportunidades | Pipeline por etapas y movimiento entre columnas | Informes financieros complejos |
| Actividades | Notas y tareas ligadas a entidades | Calendario sync, notificaciones push |
| Búsqueda | Filtros básicos y búsqueda por texto en listados | Búsqueda global tipo “command palette” |
| Infra | Docker, MySQL, panel web educativo | Kubernetes, alta disponibilidad |

## Actores y roles (recordatorio)

| Rol | Descripción breve |
|-----|-------------------|
| No autenticado | Solo landing y login. |
| `usuario` | Usuario autenticado con acceso al panel y operaciones según permisos de módulo. |
| `administrador` | Gestión de usuarios del sistema y permisos amplios en configuración. |

---

## Epic 1 — Login y gestión de usuarios

**Objetivo:** acceso seguro al sistema y administración básica de cuentas.

### Historia 1.1 — Iniciar sesión
- **Como** usuario registrado  
- **Quiero** iniciar sesión con nombre de usuario y contraseña  
- **Para** acceder al panel según mi rol  

**Criterios de aceptación**
1. **Given** credenciales válidas en base de datos, **when** envío el formulario de login, **then** se crea sesión y accedo al panel (`/admin/cpanel.php`).
2. **Given** credenciales incorrectas, **when** envío el formulario, **then** no se crea sesión y recibo mensaje genérico (sin revelar si falló usuario o contraseña).
3. **Given** ya tengo sesión activa, **when** abrí el login de nuevo, **then** puedo ser redirigido al panel o el flujo queda definido sin bucles infinitos.

### Historia 1.2 — Cerrar sesión
- **Como** usuario autenticado  
- **Quiero** cerrar sesión  
- **Para** que nadie más use mi cuenta en ese navegador  

**Criterios de aceptación**
1. **Given** sesión iniciada, **when** uso logout, **then** la sesión se invalida y no puedo llamar a APIs protegidas sin volver a autenticarme.

### Historia 1.3 — Registro / alta de usuarios (alcance acordado)
- **Como** administrador (o proceso controlado en desarrollo)  
- **Quiero** dar de alta usuarios con rol  
- **Para** que el equipo use el CRM  

**Criterios de aceptación**
1. **Given** rol administrador, **when** creo un usuario con email y rol, **then** queda persistido y puede iniciar sesión según política de contraseñas.
2. **Given** entorno académico, el registro público puede estar desactivado o limitado; quedar explícito en despliegue.

---

## Epic 2 — Clientes / contactos

**Objetivo:** mantener un directorio de personas u organizaciones con las que se trabaja.

### Historia 2.1 — Listar contactos
- **Como** usuario autenticado  
- **Quiero** ver un listado de contactos  
- **Para** localizar y abrir el detalle  

**Criterios de aceptación**
1. **Given** contactos en BD, **when** abro el listado, **then** veo campos acordados (nombre, email, teléfono, etc.) y paginación si el volumen lo requiere.
2. **Given** sin datos, **when** abro el listado, **then** veo estado vacío con mensaje claro.

### Historia 2.2 — Crear y editar contacto
- **Como** usuario  
- **Quiero** crear y editar contactos  
- **Para** mantener la información al día  

**Criterios de aceptación**
1. **Given** datos válidos, **when** guardo, **then** el contacto persiste y aparece en el listado.
2. **Given** datos inválidos (email mal formado, campos obligatorios vacíos), **when** guardo, **then** el servidor rechaza y muestro errores comprensibles.

### Historia 2.3 — Eliminar contacto
- **Como** usuario con permiso  
- **Quiero** eliminar un contacto con confirmación  
- **Para** corregir duplicados o bajas  

**Criterios de aceptación**
1. **Given** confirmación explícita, **when** elimino, **then** el contacto deja de listarse y se respetan integridad referencial (leads/actividades asociadas según reglas del MVP).

---

## Epic 3 — Leads

**Objetivo:** captar oportunidades iniciales y convertirlas en contacto u oportunidad.

### Historia 3.1 — CRUD de leads
- **Como** usuario  
- **Quiero** crear, ver, editar y eliminar leads  
- **Para** hacer seguimiento de posibles clientes  

**Criterios de aceptación**
1. **Given** lead creado, **when** lo busco en el listado, **then** aparece con estado y origen definidos en el modelo.
2. **Given** lead eliminado, **when** confirmo borrado, **then** desaparece o se marca según decisión de diseño (borrado lógico opcional).

### Historia 3.2 — Conversión
- **Como** usuario  
- **Quiero** convertir un lead en contacto y/o oportunidad  
- **Para** continuar el flujo comercial  

**Criterios de aceptación**
1. **Given** lead válido, **when** convierto, **then** se crean los registros destino y el lead queda resuelto o enlazado sin duplicar datos críticos sin aviso.

---

## Epic 4 — Oportunidades y pipeline

**Objetivo:** visualizar el embudo de ventas y mover oportunidades entre etapas.

### Historia 4.1 — Tablero por etapas
- **Como** usuario  
- **Quiero** ver oportunidades agrupadas por etapa  
- **Para** priorizar seguimiento  

**Criterios de aceptación**
1. **Given** oportunidades en distintas etapas, **when** abro el pipeline, **then** cada tarjeta aparece en la columna correcta.

### Historia 4.2 — Mover entre etapas
- **Como** usuario  
- **Quiero** cambiar la etapa de una oportunidad  
- **Para** reflejar el avance real  

**Criterios de aceptación**
1. **Given** permisos adecuados, **when** muevo de etapa A a B, **then** el cambio persiste y es visible para otros usuarios (misma fuente de verdad).

---

## Epic 5 — Actividades y notas

**Objetivo:** dejar constancia de interacciones y tareas.

### Historia 5.1 — Actividades asociadas
- **Como** usuario  
- **Quiero** crear actividades/notas vinculadas a un contacto, lead u oportunidad  
- **Para** no perder el contexto  

**Criterios de aceptación**
1. **Given** entidad existente, **when** añado una actividad, **then** aparece en el historial ordenado por fecha.
2. **Given** actividad con fecha futura, **when** la listo, **then** puedo distinguirla (opcional MVP).

---

## Epic 6 — Búsqueda y filtros

**Objetivo:** encontrar registros sin recorrer listados largos.

### Historia 6.1 — Filtros en listados
- **Como** usuario  
- **Quiero** filtrar por campos clave (estado, fecha, propietario si aplica)  
- **Para** acotar resultados  

**Criterios de aceptación**
1. **Given** filtros aplicados, **when** busco, **then** el listado solo muestra coincidencias.
2. **Given** sin resultados, **when** aplico filtros, **then** veo mensaje de “sin resultados”.

### Historia 6.2 — Búsqueda por texto
- **Como** usuario  
- **Quiero** buscar por nombre o email en contactos/leads  
- **Para** ir directo al registro  

**Criterios de aceptación**
1. **Given** texto parcial, **when** ejecuto búsqueda, **then** devuelve coincidencias razonables (prefijo o contiene, según se defina en Fase 2).

---

## Matriz de permisos (alto nivel)

| Acción | No auth | usuario | administrador |
|--------|---------|---------|----------------|
| Ver landing / login | Sí | — | — |
| Usar panel autenticado | No | Sí | Sí |
| CRUD contactos/leads (MVP) | No | Sí* | Sí |
| Gestionar usuarios del sistema | No | No** | Sí |

\*Según reglas de “propietario” si se implementan en Fase 2.  
\*\*Salvo que el MVP reserve solo administración a `administrador`.

---

## Requisitos no funcionales (priorizados para el MVP)

| Prioridad | Requisito | Nota |
|-----------|-----------|------|
| Alta | Autenticación con contraseña hasheada (no almacenar en claro) | Ya alineado con PHP `password_hash`. |
| Alta | Consultas parametrizadas (mitigar SQLi) | PDO preparado. |
| Alta | Sesión segura en servidor | PHP session; revisar flags de cookie en producción. |
| Media | Tiempo de respuesta aceptable en listados (&lt; 2 s con datos de prueba) | Validar en Fase 4. |
| Media | Disponibilidad local con Docker reproducible | Documentado en README. |
| Baja | Auditoría completa de cambios | Post-MVP salvo decisión explícita. |

---

## Criterio de cierre Fase 01

- [x] Epics del MVP descritos (este documento).
- [x] Criterios de aceptación por historia en formato Given/When/Then.
- [x] Matriz de roles y RNF priorizados referenciados.
