<?php
session_start();
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/mailer.php';

// Sin sesión pendiente → volver al login
if (empty($_SESSION['2fa_pending']['user_id'])) {
    header("Location: /modules/site/login.html");
    exit;
}

$pending = $_SESSION['2fa_pending'];
$userId  = (int) $pending['user_id'];

$error   = '';
$reenvio = false;

// ── Reenvío de código ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['accion'] ?? '') === 'reenviar') {
    try {
        $row = $pdo->prepare("SELECT email, nombre FROM usuarios WHERE id_usuario = ?");
        $row->execute([$userId]);
        $u = $row->fetch();

        if ($u && !empty($u['email'])) {
            $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $pdo->prepare("
                UPDATE usuarios
                   SET two_factor_code       = ?,
                       two_factor_expires_at = ?,
                       two_factor_attempts   = 0
                 WHERE id_usuario = ?
            ")->execute([$codigo, $expira, $userId]);

            $html = plantilla2FA($u['nombre'], $codigo);
            enviarEmail($u['email'], 'Tu código de verificación — L&J CRM', $html);
            error_log("[2FA] Reenvío para usuario {$userId}: {$codigo}");

            $reenvio = true;
        }
    } catch (PDOException $e) {
        error_log('[verify-2fa reenviar] ' . $e->getMessage());
    }
}

// ── Verificación del código ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            // Usuario no encontrado — limpiar sesión
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
        // Código incorrecto
        } elseif (!hash_equals((string) $row['two_factor_code'], $codigo)) {
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
            // ── ÉXITO ─────────────────────────────────────────────────────────
            $pdo->prepare("
                UPDATE usuarios
                   SET two_factor_code = NULL, two_factor_expires_at = NULL, two_factor_attempts = 0
                 WHERE id_usuario = ?
            ")->execute([$userId]);

            unset($_SESSION['2fa_pending']);
            session_regenerate_id(true);

            $_SESSION['user_id'] = $pending['user_id'];
            $_SESSION['nombre']  = $pending['nombre'];
            $_SESSION['rol']     = $pending['rol'];

            header("Location: /admin/cpanel.php");
            exit;
        }

    } catch (PDOException $e) {
        error_log('[verify-2fa] ' . $e->getMessage());
        $error = 'Error interno. Inténtalo de nuevo.';
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
                <a href="/auth/verify-2fa.php?accion=reenviar" class="forgot-link">Reenviar</a>
                &nbsp;·&nbsp;
                <a href="/modules/site/login.html" class="forgot-link">Volver al login</a>
            </div>
        </div>
    </div>

    <script>
        // Contador regresivo de 10 minutos
        let segundos = 600;
        const el = document.getElementById('timer-countdown');
        const intervalo = setInterval(() => {
            segundos--;
            if (segundos <= 0) {
                clearInterval(intervalo);
                el.textContent = 'expirado';
                el.style.color = '#ef4444';
                document.querySelector('button[type=submit]').disabled = true;
                return;
            }
            const m = String(Math.floor(segundos / 60)).padStart(2, '0');
            const s = String(segundos % 60).padStart(2, '0');
            el.textContent = `${m}:${s}`;
            if (segundos <= 60) el.style.color = '#ef4444';
        }, 1000);

        // Solo permitir dígitos en el campo
        document.getElementById('codigo').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    </script>
</body>
</html>
