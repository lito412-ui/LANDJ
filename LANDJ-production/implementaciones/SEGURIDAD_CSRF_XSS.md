# Seguridad anti-CSRF y anti-XSS — LANDJ CRM

---

## Anti-XSS

### Cabeceras HTTP de seguridad

Emitidas en todas las páginas y endpoints a través de `public/config/seguridad.php`:

| Cabecera | Valor | Propósito |
|----------|-------|-----------|
| `X-Content-Type-Options` | `nosniff` | Impide que el navegador infiera el MIME type; evita ataques de tipo MIME sniffing |
| `X-Frame-Options` | `DENY` | Bloquea la carga en iframes; mitiga clickjacking |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | No filtra rutas internas en el Referer hacia dominios externos |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` | Deshabilita APIs del navegador no usadas |
| `Content-Security-Policy` | Ver detalle abajo | Whitelist de fuentes de contenido |

### Content Security Policy (CSP)

```
default-src 'self';
script-src  'self';
style-src   'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com;
font-src    'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:;
img-src     'self' data:;
connect-src 'self';
frame-ancestors 'none';
```

| Directiva | Justificación |
|-----------|---------------|
| `script-src 'self'` | Solo scripts propios; bloquea `<script>` inline y eval |
| `style-src 'unsafe-inline'` | Necesario para Font Awesome y estilos dinámicos de JS (`.style.width`) |
| `frame-ancestors 'none'` | Equivalente más fuerte que X-Frame-Options para navegadores modernos |
| `connect-src 'self'` | Solo fetch/XHR al mismo origen; bloquea exfiltración a dominios externos |

### Escape de salida

| Capa | Cómo | Dónde |
|------|------|-------|
| **PHP → HTML** | `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` | Cualquier variable de usuario impresa en HTML |
| **JS → DOM** | `element.textContent = valor` / función `esc()` | Módulo Contactos: celdas de tabla, avatares |
| **JS → innerHTML** | Solo con `esc()` aplicado al dato antes de interpolar | `renderTabla()` en Contactos |

> Regla: **nunca** usar `innerHTML = valorUsuario` sin pasar antes por `esc()`.

---

## Anti-CSRF

### Estrategia: Synchronizer Token + Custom Header

El proyecto combina dos técnicas complementarias:

1. **Synchronizer Token Pattern** — se genera un token criptográfico aleatorio por sesión (`bin2hex(random_bytes(32))`) que se incluye en el HTML como `<meta name="csrf-token">`.
2. **Custom Request Header** — el cliente JS lee ese token y lo envía como `X-CSRF-Token: <token>` en cada petición mutante. Los navegadores no pueden enviar cabeceras custom en solicitudes cross-origin sin pasar por preflight de CORS, lo que bloquea los ataques CSRF convencionales.

### Flujo completo

```
1. Usuario carga /modules/dashboard/cpanel.php
   └─ PHP genera/recupera token de sesión
   └─ <meta name="csrf-token" content="abc123..."> en el <head>

2. JS lee el token al cargar:
   const _csrfToken = document.querySelector('meta[name="csrf-token"]').content

3. En cada POST/PUT/DELETE el wrapper fetchSeguro() añade:
   headers: { 'X-CSRF-Token': _csrfToken }

4. PHP (seguridad.php → csrfValidar()) comprueba:
   hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])
   └─ Si no coincide → 403 Forbidden
```

### Archivos implicados

| Archivo | Rol |
|---------|-----|
| `public/config/seguridad.php` | Genera token, función `csrfMeta()`, función `csrfValidar()` |
| `public/modules/dashboard/cpanel.php` | `session_start()` + `require seguridad.php` + `csrfGenerar()` antes del HTML |
| `public/modules/dashboard/partials/head.php` | `<?php csrfMeta(); ?>` dentro de `<head>` |
| `public/api/contactos.php` | `require seguridad.php` + `csrfValidar()` tras auth check |
| `public/api/auditoria.php` | `require seguridad.php` (GET solo lectura; sin csrfValidar) |
| `public/api/monitorizacion.php` | `require seguridad.php` (GET solo lectura) |
| `public/api/get_user.php` | `require seguridad.php` (GET solo lectura) |
| `public/assets/js/dashboard/cpanel-script.js` | `fetchSeguro()` wrapper; sustituye a `fetch()` en todo el módulo |

### ¿Por qué no SameSite=Strict solo?

`SameSite=Strict` en la cookie de sesión es una capa adicional de defensa (configurar en `php.ini` o `session_set_cookie_params`), pero no se usa como única protección porque:
- Algunos proxies y redireccionamientos rompen `Strict`
- El token + custom header funciona incluso si el atributo de cookie se pierde

### Endpoints y métodos protegidos

| Endpoint | GET | POST | PUT | DELETE |
|----------|:---:|:----:|:---:|:------:|
| `/api/contactos.php` | — | ✓ CSRF | ✓ CSRF | ✓ CSRF |
| `/api/auditoria.php` | — | — | — | — |
| `/api/get_user.php` | — | — | — | — |
| `/api/monitorizacion.php` | — | — | — | — |
| *Futuros: leads, oportunidades* | — | ✓ CSRF | ✓ CSRF | ✓ CSRF |

---

## Checklist de implementación

- [x] `public/config/seguridad.php` — cabeceras HTTP + CSRF token
- [x] CSP configurado permitiendo Google Fonts y Font Awesome
- [x] `csrfMeta()` en `head.php`
- [x] `cpanel.php` llama a `session_start()` + `csrfGenerar()` antes de HTML
- [x] `contactos.php` llama a `csrfValidar()` tras verificar sesión
- [x] `fetchSeguro()` en JS inyecta `X-CSRF-Token` automáticamente en mutantes
- [x] Cabeceras de seguridad presentes en todas las APIs
- [ ] `session_set_cookie_params` con `SameSite=Lax`, `HttpOnly=true`, `Secure=true` (para producción HTTPS)
- [ ] CSRF en endpoints de login/logout
