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
    // Permite login con nombre de usuario O con correo electrónico
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE LOWER(nombre) = LOWER(?) OR LOWER(email) = LOWER(?) LIMIT 1");
    $stmt->execute([$user_form, $user_form]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass_form, $user['contraseña_hash'])) {
        header("Location: /modules/site/login.html?error=credenciales");
        exit;
    }

    // Comprobar si el email está verificado (para nuevas cuentas registradas)
    if (isset($user['email_verificado']) && (int)$user['email_verificado'] === 0) {
        $_SESSION['registro_pendiente'] = [
            'id_usuario' => $user['id_usuario'],
            'nombre'     => $user['nombre'],
            'email'      => $user['email']
        ];
        header("Location: /modules/site/registro.html?paso=verificar&user_id=" . $user['id_usuario'] . "&email=" . urlencode($user['email']));
        exit;
    }

    // ── 2FA activo ────────────────────────────────────────────────────────────
    if (!empty($user['two_factor_enabled'])) {
        $userEmail = strtolower(trim($user['email'] ?? ''));

        if ($userEmail === '') {
            error_log("[login 2fa] El usuario {$user['nombre']} tiene 2FA activo pero no tiene email configurado.");
            header("Location: /modules/site/login.html?error=sin_email_2fa");
            exit;
        }

        if (!esSmtpSistemaConfigurado()) {
            error_log("[login 2fa] El servidor SMTP del sistema (.env) no está configurado.");
            header("Location: /modules/site/login.html?error=smtp_sistema_no_configurado");
            exit;
        }

        $codigo     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);
        $expira     = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $pdo->prepare("
            UPDATE usuarios
               SET two_factor_code       = ?,
                   two_factor_expires_at = ?,
                   two_factor_attempts   = 0
             WHERE id_usuario = ?
        ")->execute([$codigoHash, $expira, $user['id_usuario']]);

        // Guardar estado pendiente en sesión (sin crear sesión completa)
        $_SESSION['2fa_pending'] = [
            'user_id'     => $user['id_usuario'],
            'nombre'      => $user['nombre'],
            'rol'         => $user['rol'],
            'id_grupo'    => (int) ($user['id_grupo'] ?? 1),
            'last_resend' => time(),
        ];

        // Enviar email con el código al usuario usando el canal de sistema
        $html = plantilla2FA($user['nombre'], $codigo);
        $enviado = enviarEmailSistema($userEmail, 'Tu código de verificación — L&J CRM', $html);

        if (!$enviado) {
            error_log("[login 2fa] Falló el envío del código 2FA a {$userEmail}");
            header("Location: /modules/site/login.html?error=smtp_error");
            exit;
        }

        header("Location: /auth/verify-2fa.php");
        exit;
    }

    // ── Login directo (2FA no activo) ─────────────────────────────────────────
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id_usuario'];
    $_SESSION['nombre']   = $user['nombre'];
    $_SESSION['rol']      = $user['rol'];
    $_SESSION['id_grupo'] = (int) ($user['id_grupo'] ?? 1);

    header("Location: /admin/cpanel.php");
    exit;

} catch (PDOException $e) {
    error_log('[login] ' . $e->getMessage());
    header("Location: /modules/site/login.html?error=servidor");
    exit;
}
