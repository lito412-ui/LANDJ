<?php

/**
 * Corta la ejecución con 403 si el usuario actual (no administrador) no
 * tiene acceso al módulo indicado, según la tabla `modulos_visibilidad`
 * gestionada desde la sección admin "Módulos Visibles".
 *
 * A diferencia del control de solo-interfaz, esta comprobación se ejecuta
 * en el propio endpoint: aunque alguien llame a la API directamente (curl,
 * DevTools, etc.) sin pasar por el panel, sigue sin poder acceder si el
 * módulo está oculto para su rol.
 *
 * Los administradores siempre pasan la comprobación (bypass total).
 * Debe llamarse DESPUÉS de validar la sesión y de tener `$pdo` disponible.
 */
function verificarModuloVisible(PDO $pdo, string $modulo): void
{
    if (!moduloVisibleParaUsuario($pdo, $modulo)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'No tienes acceso a este módulo']);
        exit;
    }
}

/**
 * Igual que verificarModuloVisible() pero para endpoints que no responden
 * en JSON (por ejemplo, descargas de PDF como factura_pdf.php).
 */
function verificarModuloVisibleTexto(PDO $pdo, string $modulo): void
{
    if (!moduloVisibleParaUsuario($pdo, $modulo)) {
        http_response_code(403);
        echo 'No tienes acceso a este módulo';
        exit;
    }
}

function moduloVisibleParaUsuario(PDO $pdo, string $modulo): bool
{
    if (($_SESSION['rol'] ?? '') === 'administrador') {
        return true;
    }

    $s = $pdo->prepare("SELECT visible FROM modulos_visibilidad WHERE modulo = ? LIMIT 1");
    $s->execute([$modulo]);
    $visible = $s->fetchColumn();

    // Si el modulo no esta en el catalogo de "ocultables", se permite por
    // defecto: solo se restringe lo que el admin ha marcado explicitamente.
    return $visible === false || (int) $visible === 1;
}
