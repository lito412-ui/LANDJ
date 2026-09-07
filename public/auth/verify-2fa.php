<?php
session_start();
require_once __DIR__ . '/../config/seguridad.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/mailer.php';

// Sin sesión pendiente → volver al login
if (empty($_SESSION['2fa_pending']['user_id'])) {
    header("Location: /modules/site/login.html");
    exit;
}

$pending = $_SESSION['2fa_pending'];
$userId  = (int) $pending['user_id'];
$csrfToken = csrfGenerar();

$error   = '';
$reenvio = false;

// ── Procesamiento POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    if (!$tokenEnviado || !hash_equals($_SESSION['csrf_token'] ?? '', $tokenEnviado)) {
        $error = 'Token de seguridad inválido. Por favor, recarga la página.';
    } else {
        $accion = $_POST['accion'] ?? 'verificar';

        // ── Reenvío de código (POST con Rate Limiting y CSRF) ─────────────────
        if ($accion === 'reenviar') {
            $ahora = time();
            $ultimoReenvio = (int) ($_SESSION['2fa_pending']['last_resend'] ?? 0);
            $tiempoRestante = 60 - ($ahora - $ultimoReenvio);

            if ($tiempoRestante > 0) {
                $error = "Debes esperar {$tiempoRestante} segundo(s) antes de solicitar un nuevo código.";
            } else {
                try {
                    $row = $pdo->prepare("SELECT email, nombre, two_factor_attempts FROM usuarios WHERE id_usuario = ?");
                    $row->execute([$userId]);
                    $u = $row->fetch();

                    if (!$u) {
                        unset($_SESSION['2fa_pending']);
                        header("Location: /modules/site/login.html?error=credenciales");
                        exit;
                    }

                    // Si ya está bloqueado por demasiados intentos, no reenviar
                    if ((int)$u['two_factor_attempts'] >= 3) {
                        $pdo->prepare("
                            UPDATE usuarios
                               SET two_factor_code = NULL, two_factor_expires_at = NULL, two_factor_attempts = 0
                             WHERE id_usuario = ?
                        ")->execute([$userId]);
                        unset($_SESSION['2fa_pending']);
                        header("Location: /modules/site/login.html?error=2fa_bloqueado");
                        exit;
                    }

                    if (!empty($u['email'])) {
                        $codigo     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        $codigoHash = password_hash($codigo, PASSWORD_DEFAULT);
                        $expira     = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                        // IMPORTANTE: Se actualiza el código pero SE PRESERVA two_factor_attempts
                        $pdo->prepare("
                            UPDATE usuarios
                               SET two_factor_code       = ?,
                                   two_factor_expires_at = ?
                             WHERE id_usuario = ?
                        ")->execute([$codigoHash, $expira, $userId]);

                        $_SESSION['2fa_pending']['last_resend'] = $ahora;

                        $html = plantilla2FA($u['nombre'], $codigo);
                        $enviado = enviarEmailSistema(trim($u['email']), 'Tu código de verificación — L&J CRM', $html);
                        if ($enviado) {
                            $reenvio = true;
                        } else {
                            $error = 'No se pudo enviar el correo de verificación. Revisa la configuración del servidor SMTP en el archivo .env.';
                        }
                    } else {
                        $error = 'Tu cuenta no tiene una dirección de correo asociada para recibir el código.';
                    }
                } catch (PDOException $e) {
                    error_log('[verify-2fa reenviar] ' . $e->getMessage());
                    $error = 'Error interno al reenviar el código.';
                }
            }
        }

        // ── Verificación del código (POST) ────────────────────────────────────
        elseif ($accion === 'verificar') {
            $codigo = preg_replace('/\D/', '', $_POST['codigo'] ?? '');

            try {
                $stmt = $pdo->prepare("
                    SELECT two_factor_code, two_factor_expires_at, two_factor_attempts
                      FROM usuarios
                     WHERE id_usuario = ?
                ");
                $stmt->execute([$userId]);
                $row = $stmt->fetch();

                if (!$row) {
                    unset($_SESSION['2fa_pending']);
                    header("Location: /modules/site/login.html?error=credenciales");
                    exit;
                }

                $intentos = (int) $row['two_factor_attempts'];

                // Demasiados intentos fallidos
                if ($intentos >= 3) {
                    $pdo->prepare("
                        UPDATE usuarios
                           SET two_factor_code = NULL, two_factor_expires_at = NULL, two_factor_attempts = 0
                         WHERE id_usuario = ?
                    ")->execute([$userId]);
                    unset($_SESSION['2fa_pending']);
                    header("Location: /modules/site/login.html?error=2fa_bloqueado");
                    exit;
                }

                // Código expirado
                if (!$row['two_factor_expires_at'] || new DateTime() > new DateTime($row['two_factor_expires_at'])) {
                    $error = 'El código ha caducado. Solicita uno nuevo.';
                // Código incorrecto (validado con password_verify)
                } elseif (!password_verify($codigo, (string) $row['two_factor_code'])) {
                    $pdo->prepare("UPDATE usuarios SET two_factor_attempts = two_factor_attempts + 1 WHERE id_usuario = ?")
                        ->execute([$userId]);
                    $restantes = 3 - ($intentos + 1);
                    $error = $restantes > 0
                        ? "Código incorrecto. Te quedan {$restantes} intento(s)."
                        : 'Código incorrecto. Acceso bloqueado.';

                    if ($restantes <= 0) {
                        $pdo->prepare("
                            UPDATE usuarios
                               SET two_factor_code = NULL, two_factor_expires_at = NULL, two_factor_attempts = 0
                             WHERE id_usuario = ?
                        ")->execute([$userId]);
                        unset($_SESSION['2fa_pending']);
                        header("Location: /modules/site/login.html?error=2fa_bloqueado");
                        exit;
                    }
                } else {
                    // ── ÉXITO ─────────────────────────────────────────────────
                    $pdo->prepare("
                        UPDATE usuarios
                           SET two_factor_code = NULL, two_factor_expires_at = NULL, two_factor_attempts = 0
                         WHERE id_usuario = ?
                    ")->execute([$userId]);

                    unset($_SESSION['2fa_pending']);
                    session_regenerate_id(true);

                    $_SESSION['user_id']  = $pending['user_id'];
                    $_SESSION['nombre']   = $pending['nombre'];
                    $_SESSION['rol']      = $pending['rol'];
                    $_SESSION['id_grupo'] = (int) ($pending['id_grupo'] ?? 1);

                    header("Location: /admin/cpanel.php");
                    exit;
                }

            } catch (PDOException $e) {
                error_log('[verify-2fa] ' . $e->getMessage());
                $error = 'Error interno. Inténtalo de nuevo.';
            }
        }
    }
}

// ── HTML ──────────────────────────────────────────────────────────────────────
$nombre = htmlspecialchars($pending['nombre'], ENT_QUOTES, 'UTF-8');
$errorHtml = $error
    ? '<div class="verify-error"><span>&#9888;</span> ' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>'
    : '';
$reenvioHtml = $reenvio
    ? '<div class="verify-success"><span>&#10003;</span> Código reenviado a tu correo.</div>'
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/site/style.css">
    <link rel="stylesheet" href="/assets/css/site/verify-2fa.css">
    <link rel="icon" type="image/png" href="/assets/img/logo_proyecto.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <title>L&J - Verificación</title>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="logo">L&J</h1>
                <p class="subtitle">Verificación en dos pasos</p>
            </div>

            <div class="verify-info">
                <div class="verify-icon">&#128274;</div>
                <p>Hemos enviado un código de 6 dígitos al correo asociado a <strong><?= $nombre ?></strong>.<br>
                Introdúcelo antes de que caduque.</p>
            </div>

            <?= $errorHtml ?>
            <?= $reenvioHtml ?>

            <form class="login-form" method="POST" action="/auth/verify-2fa.php" id="verifyForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="accion" value="verificar">
                <div class="input-group">
                    <label for="codigo" class="input-label">Código de verificación</label>
                    <input
                        type="text"
                        id="codigo"
                        name="codigo"
                        class="input-field verify-code-input"
                        placeholder="000000"
                        maxlength="6"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        autofocus
                        required
                    >
                </div>

                <div class="verify-timer" id="verify-timer">
                    Caduca en <span id="timer-countdown">10:00</span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Verificar</button>
                </div>
            </form>

            <div class="verify-footer">
                <span>¿No has recibido el código?</span>
                <form method="POST" action="/auth/verify-2fa.php" style="display:inline;" id="resendForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="accion" value="reenviar">
                    <button type="submit" class="forgot-link" style="background:none;border:none;padding:0;font:inherit;cursor:pointer;text-decoration:underline;">Reenviar</button>
                </form>
                &nbsp;·&nbsp;
                <a href="/modules/site/login.html" class="forgot-link">Volver al login</a>
            </div>
        </div>
    </div>

    <script src="/assets/js/verify-2fa.js"></script>
</body>
</html>
