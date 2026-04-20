<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

csrfValidar();

require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/auditoria.php';

$meId   = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

function ok($data = null): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }

try {
    if ($method !== 'PUT') { err('Método no permitido', 405); exit; }

    $b = body();

    // ─── Cambiar contraseña ───────────────────────────────────────────────
    if ($accion === 'password') {
        $actual    = $b['actual']    ?? '';
        $nueva     = $b['nueva']     ?? '';
        $confirmar = $b['confirmar'] ?? '';

        if ($actual === '')    { err('La contraseña actual es obligatoria'); exit; }
        if ($nueva === '')     { err('La nueva contraseña es obligatoria'); exit; }
        if ($nueva !== $confirmar) { err('Las contraseñas no coinciden'); exit; }
        if (strlen($nueva) < 8)    { err('La contraseña debe tener al menos 8 caracteres'); exit; }
        if (strlen($nueva) > 72)   { err('La contraseña no puede superar 72 caracteres'); exit; }
        if (!preg_match('/[a-zA-Z]/', $nueva) || !preg_match('/[0-9]/', $nueva)) {
            err('La contraseña debe contener letras y números'); exit;
        }

        $s = $pdo->prepare("SELECT contraseña_hash FROM usuarios WHERE id_usuario = ?");
        $s->execute([$meId]);
        $row = $s->fetch();
        if (!$row || !password_verify($actual, $row['contraseña_hash'])) {
            err('La contraseña actual no es correcta'); exit;
        }

        $hash = password_hash($nueva, PASSWORD_ARGON2ID);
        $pdo->prepare("UPDATE usuarios SET contraseña_hash = ? WHERE id_usuario = ?")
            ->execute([$hash, $meId]);

        registrarAuditoria($pdo, 'usuarios', $meId, 'editar', null, null);
        ok();
        exit;
    }

    // ─── Actualizar perfil (nombre + email) ───────────────────────────────
    $nombre = trim($b['nombre'] ?? '');
    $email  = trim($b['email']  ?? '');

    if ($nombre === '') { err('El nombre es obligatorio'); exit; }
    if (strlen($nombre) > 100) { err('El nombre no puede superar 100 caracteres'); exit; }
    if (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s_\-\.]+$/u', $nombre)) {
        err('El nombre contiene caracteres no permitidos'); exit;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        err('El email no es válido'); exit;
    }

    $existe = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE nombre = ? AND id_usuario != ?");
    $existe->execute([$nombre, $meId]);
    if ($existe->fetch()) { err('Ya existe un usuario con ese nombre'); exit; }

    if ($email !== '') {
        $existeEmail = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
        $existeEmail->execute([$email, $meId]);
        if ($existeEmail->fetch()) { err('Ya existe un usuario con ese email'); exit; }
    }

    $sAntes = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = ?");
    $sAntes->execute([$meId]);
    $antes = $sAntes->fetch() ?: null;

    $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id_usuario = ?")
        ->execute([$nombre, $email !== '' ? $email : null, $meId]);

    $sDespues = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = ?");
    $sDespues->execute([$meId]);
    $despues = $sDespues->fetch() ?: null;

    registrarAuditoria($pdo, 'usuarios', $meId, 'editar', $antes, $despues);

    // Actualizar sesión
    $_SESSION['nombre'] = $nombre;

    ok(['nombre' => $nombre, 'email' => $email !== '' ? $email : null]);

} catch (Throwable $e) {
    error_log('[configuracion] ' . $e->getMessage());
    err('Error interno del servidor', 500);
}
