<?php
session_start();
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/site/login.html");
    exit;
}

$user_form = trim($_POST['username'] ?? '');
$pass_form = $_POST['password'] ?? '';

if ($user_form === '' || $pass_form === '') {
    header("Location: /modules/site/login.html?error=credenciales");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre = ? LIMIT 1");
    $stmt->execute([$user_form]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass_form, $user['contraseña_hash'])) {
        header("Location: /modules/site/login.html?error=credenciales");
        exit;
    }

    // ── 2FA activo ────────────────────────────────────────────────────────────
    if (!empty($user['two_factor_enabled'])) {
        $codigo  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expira  = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $pdo->prepare("
            UPDATE usuarios
               SET two_factor_code       = ?,
                   two_factor_expires_at = ?,
                   two_factor_attempts   = 0
             WHERE id_usuario = ?
        ")->execute([$codigo, $expira, $user['id_usuario']]);

        // Guardar estado pendiente en sesión (sin crear sesión completa)
        $_SESSION['2fa_pending'] = [
            'user_id' => $user['id_usuario'],
            'nombre'  => $user['nombre'],
            'rol'     => $user['rol'],
        ];

        // Enviar email con el código
        if (!empty($user['email'])) {
            $html = plantilla2FA($user['nombre'], $codigo);
            enviarEmail($user['email'], 'Tu código de verificación — L&J CRM', $html);
        }

        // En desarrollo: loguear el código para poder probarlo sin SMTP
        error_log("[2FA] Código para {$user['nombre']} ({$user['id_usuario']}): {$codigo}");

        header("Location: /auth/verify-2fa.php");
        exit;
    }

    // ── Login directo (2FA no activo) ─────────────────────────────────────────
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id_usuario'];
    $_SESSION['nombre']  = $user['nombre'];
    $_SESSION['rol']     = $user['rol'];

    header("Location: /admin/cpanel.php");
    exit;

} catch (PDOException $e) {
    error_log('[login] ' . $e->getMessage());
    header("Location: /modules/site/login.html?error=servidor");
    exit;
}
