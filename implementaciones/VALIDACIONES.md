# Diseño de Validaciones de Entrada — LANDJ CRM

Referencia única para las reglas de validación del proyecto.  
Todo campo que aparezca aquí debe validarse **en cliente (JS) y en servidor (PHP)** con las mismas reglas.  
El servidor es la línea de defensa real; el cliente solo mejora la UX.

---

## Principios generales

| Principio | Descripción |
|-----------|-------------|
| **Trim siempre** | Todos los campos se recortan de espacios antes de validar |
| **Vacío ≠ null** | Un campo opcional vacío se guarda como `NULL` en BD, no como `""` |
| **Sanitizar en salida** | Los datos se escapan al mostrar (`htmlspecialchars` en PHP, `textContent`/`esc()` en JS), no al guardar |
| **Mensajes concretos** | El error indica qué está mal, no solo "campo inválido" |
| **Un error a la vez por campo** | Se muestra el primer fallo encontrado (no acumular mensajes por campo) |

---

## Tipos de validador reutilizables

| ID | Descripción | Patrón / Regla |
|----|-------------|----------------|
| `NOMBRE` | Letras (con tildes y ñ), espacios, guion, apóstrofe | `/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s'\-]+$/u` |
| `EMAIL` | Formato estándar RFC | `filter_var(FILTER_VALIDATE_EMAIL)` / `/^[^\s@]+@[^\s@]+\.[^\s@]+$/` |
| `TEL_ES` | Teléfono español: 9 dígitos, inicia en 6–9. Permite espacios y guiones como separadores | Dígitos limpios: `/^[6-9]\d{8}$/` |
| `DECIMAL` | Número positivo con hasta 2 decimales | `/^\d{1,10}(\.\d{1,2})?$/` |
| `FECHA` | Fecha ISO `YYYY-MM-DD` válida | `strtotime()` / `Date.parse()` + rango razonable |
| `TEXTO_LIBRE` | Sin restricción de caracteres, solo longitud | — |
| `ENUM` | Valor debe pertenecer a un conjunto fijo | Lista blanca explícita |
| `BOOL` | 0 ó 1 | `in_array($v, [0, 1], true)` |

---

## Normalización automática (no es validación, es corrección silenciosa)

| Campo | Acción al perder el foco (blur) |
|-------|--------------------------------|
| `nombre`, `apellidos`, `titulo` | Primera letra de cada palabra en mayúscula (`toUpperCase` en JS / `ucwords` en PHP) |
| `email` | Convertir a minúsculas |
| `telefono` | Eliminar caracteres no numéricos, espacios y guiones excepto como separadores |

---

## Validaciones por entidad

### Usuarios (`usuarios`)

| Campo | Tipo | ¿Obligatorio? | Longitud | Validador | Mensaje de error |
|-------|------|:---:|----------|-----------|-----------------|
| `nombre` | string | ✓ | max 100 | `NOMBRE` | "Solo letras, espacios, guiones y apóstrofes" |
| `email` | string | — | max 255 | `EMAIL` | "Formato de email inválido" |
| `contraseña` | string | ✓ | min 8, max 72 | min longitud + al menos 1 letra y 1 número | "Mínimo 8 caracteres, con letras y números" |
| `rol` | enum | ✓ | — | `ENUM`: `usuario`, `administrador` | "Rol no válido" |

---

### Contactos (`contactos`)

| Campo | Tipo | ¿Obligatorio? | Longitud | Validador | Mensaje de error |
|-------|------|:---:|----------|-----------|-----------------|
| `nombre` | string | ✓ | max 100 | `NOMBRE` | "El nombre solo puede contener letras, espacios, guiones y apóstrofes" |
| `apellidos` | string | — | max 100 | `NOMBRE` | "Los apellidos solo pueden contener letras, espacios, guiones y apóstrofes" |
| `email` | string | — | max 255 | `EMAIL` | "Introduce un email válido (ej: ana@empresa.com)" |
| `telefono` | string | — | max 30 | `TEL_ES` | "Teléfono español inválido (ej: 612 345 678)" |
| `empresa` | string | — | max 150 | `TEXTO_LIBRE` | "La empresa no puede superar 150 caracteres" |
| `notas` | string | — | max 500 | `TEXTO_LIBRE` | "Las notas no pueden superar 500 caracteres" |

---

### Leads (`leads`)

| Campo | Tipo | ¿Obligatorio? | Longitud | Validador | Mensaje de error |
|-------|------|:---:|----------|-----------|-----------------|
| `nombre` | string | ✓ | max 100 | `NOMBRE` | "El nombre solo puede contener letras, espacios, guiones y apóstrofes" |
| `email` | string | — | max 255 | `EMAIL` | "Introduce un email válido" |
| `telefono` | string | — | max 30 | `TEL_ES` | "Teléfono español inválido (ej: 612 345 678)" |
| `empresa` | string | — | max 150 | `TEXTO_LIBRE` | "La empresa no puede superar 150 caracteres" |
| `origen` | string | — | max 50 | `TEXTO_LIBRE` | "El origen no puede superar 50 caracteres" |
| `estado` | enum | ✓ | — | `ENUM`: `nuevo`, `contactado`, `calificado`, `convertido`, `descartado` | "Estado no válido" |
| `notas` | string | — | max 500 | `TEXTO_LIBRE` | "Las notas no pueden superar 500 caracteres" |

---

### Oportunidades (`oportunidades`)

| Campo | Tipo | ¿Obligatorio? | Longitud | Validador | Mensaje de error |
|-------|------|:---:|----------|-----------|-----------------|
| `titulo` | string | ✓ | max 150 | `TEXTO_LIBRE` (no vacío) | "El título es obligatorio" |
| `descripcion` | string | — | max 1000 | `TEXTO_LIBRE` | "La descripción no puede superar 1000 caracteres" |
| `valor` | decimal | — | — | `DECIMAL` ≥ 0 | "Introduce un importe válido (ej: 1500.00)" |
| `etapa` | enum | ✓ | — | `ENUM`: `prospecto`, `propuesta`, `negociacion`, `cerrada_ganada`, `cerrada_perdida` | "Etapa no válida" |
| `fecha_cierre_esperada` | date | — | — | `FECHA`, no en el pasado | "La fecha de cierre no puede ser anterior a hoy" |
| `contacto_id` | int | — | — | FK existente o null | — |
| `lead_id` | int | — | — | FK existente o null | — |

---

### Actividades (`actividades`)

| Campo | Tipo | ¿Obligatorio? | Longitud | Validador | Mensaje de error |
|-------|------|:---:|----------|-----------|-----------------|
| `tipo` | enum | ✓ | — | `ENUM`: `nota`, `llamada`, `reunion`, `tarea`, `email` | "Tipo de actividad no válido" |
| `descripcion` | string | ✓ | max 1000 | `TEXTO_LIBRE` (no vacío) | "La descripción es obligatoria" |
| `fecha` | datetime | — | — | `FECHA` ISO o null | "Fecha no válida" |
| `completada` | bool | ✓ | — | `BOOL` | — |

---

## Reglas de teléfono español detalladas

```
Formato aceptado: [6-9]XXXXXXXX  (9 dígitos en total)
Prefijos válidos: 6xx (móvil), 7xx (móvil/VoIP), 8xx (geográfico nuevo), 9xx (geográfico)
Separadores permitidos: espacios y guiones (se eliminan antes de validar)

Ejemplos válidos:   612345678 · 612 345 678 · 612-345-678 · 912 000 001
Ejemplos inválidos: 512345678 (empieza en 5) · 61234567 (8 dígitos) · +34612345678 (prefijo país)

Regex de dígitos limpios: /^[6-9]\d{8}$/
```

> El prefijo internacional `+34` se rechaza intencionadamente en esta versión — si en el futuro se acepta, se añadirá una migración de validación.

---

## Manejo de errores en la respuesta API

```json
// Error de validación campo único
{ "ok": false, "error": "El email no es válido" }

// Error de validación múltiple (varios campos fallidos simultáneamente)
{ "ok": false, "error": "El nombre es obligatorio; Teléfono español inválido" }
```

Los mensajes múltiples se separan con `; ` y el cliente los muestra como un toast de error.  
Los errores de campo individual se muestran inline bajo el campo correspondiente.

---

## Comportamiento cliente vs servidor

| Momento | Dónde | Qué hace |
|---------|-------|----------|
| `input` | JS | Valida en tiempo real, muestra error inline |
| `blur` | JS | Valida + normaliza (capitalización, minúsculas email) |
| `submit` | JS | Valida todos los campos, bloquea envío si hay errores |
| `POST/PUT` | PHP | Revalida todo, responde con `400` si falla |

---

## CSS de estados de validación

| Clase | Color | Cuándo se aplica |
|-------|-------|-----------------|
| `.form-input--error` | Rojo `#dc2626`, fondo `#fff5f5` | Campo con error activo |
| `.form-input--ok` | Verde `#16a34a`, fondo `#f0fdf4` | Campo válido y no vacío |
| `.form-error` | Texto rojo, `font-size: 0.75rem` | Span de mensaje bajo el campo |
| `.form-counter` | Gris `#94a3b8`, `font-size: 0.72rem` | Contador de caracteres (notas, descripción) |
