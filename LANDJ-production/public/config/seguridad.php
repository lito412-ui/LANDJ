<?php

/**
 * Emite las cabeceras de seguridad HTTP y gestiona el token CSRF.
 * Incluir al inicio de cada página PHP y endpoint API.
 *
 * Uso en páginas HTML:   require_once + csrfMeta() en <head>
 * Uso en APIs REST:      require_once + csrfValidar() en métodos mutantes (POST/PUT/DELETE)
 */

// ─── Cabeceras de seguridad HTTP ──────────────────────────────────────────────

if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self'; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
        "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; " .
        "img-src 'self' data:; " .
        "connect-src 'self'; " .
        "frame-ancestors 'none';"
    );
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────

function csrfGenerar(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Imprime el <meta> con el token para que JS lo lea. */
function csrfMeta(): void
{
    $token = htmlspecialchars(csrfGenerar(), ENT_QUOTES, 'UTF-8');
    echo "<meta name=\"csrf-token\" content=\"{$token}\">\n";
}

/**
 * Valida el token en métodos mutantes (POST, PUT, DELETE).
 * Llama a esta función en los endpoints API después de verificar la sesión.
 * Si falla, responde 403 y termina la ejecución.
 */
function csrfValidar(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
        return;
    }

    $tokenEnviado  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $tokenSesion   = $_SESSION['csrf_token']        ?? '';

    if (!$tokenSesion || !hash_equals($tokenSesion, $tokenEnviado)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido']);
        exit;
    }
}
