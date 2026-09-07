<?php
// Solo accesible para administradores del sistema
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    http_response_code(403);
    die('Acceso denegado. Solo administradores pueden acceder a esta herramienta.');
}

require_once __DIR__ . '/config/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$resultado = null;
$smtpLog   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destino = trim($_POST['destino'] ?? '');

    if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
        $resultado = ['ok' => false, 'msg' => 'Email de destino no válido'];
    } else {
        // Capturar el debug de SMTP en un array
        $mail = new PHPMailer(true);
        ob_start();
        try {
            $mail->isSMTP();
            $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function (string $str, int $level) use (&$smtpLog) {
                $smtpLog[] = trim($str);
            };
            $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER') ?: '';
            $pass = getenv('SMTP_PASS') ?: '';
            $mail->Password   = str_replace(' ', '', $pass);
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)(getenv('SMTP_PORT') ?: 587);
            $mail->CharSet    = 'UTF-8';

            $from = getenv('MAIL_FROM') ?: $mail->Username;
            $mail->setFrom($from, 'L&J CRM Test');
            $mail->addAddress($destino);
            $mail->isHTML(true);
            $mail->Subject = 'Test SMTP — L&J CRM';
            $mail->Body    = '<p>Email de prueba enviado correctamente desde L&amp;J CRM.</p>';

            $mail->send();
            $resultado = ['ok' => true, 'msg' => "Email enviado correctamente a {$destino}"];
        } catch (Exception) {
            $resultado = ['ok' => false, 'msg' => 'Error: ' . $mail->ErrorInfo];
        }
        ob_end_clean();
    }
}

$smtpUser = getenv('SMTP_USER') ?: '';
$smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$smtpPort = getenv('SMTP_PORT') ?: '587';
$smtpPass = getenv('SMTP_PASS') ?: '';
$passLen  = strlen(str_replace(' ', '', $smtpPass));
$passOk   = $passLen > 0 ? ($passLen === 16 ? '✓ 16 caracteres' : "✗ $passLen caracteres (debería ser 16)") : '✗ No configurada';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test SMTP — L&J</title>
    <style>
        body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        h2 { color: #667eea; }
        .card { background: #1e293b; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: .4rem; color: #94a3b8; font-size: .85rem; }
        input { width: 320px; padding: 8px 12px; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #e2e8f0; font-size: 1rem; }
        button { margin-top: 1rem; padding: 10px 24px; background: #667eea; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; }
        .ok  { color: #34d399; }
        .err { color: #f87171; }
        .log { background: #0f172a; border-radius: 8px; padding: 1rem; font-size: .78rem; line-height: 1.6; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; }
        .row { display: flex; gap: 1rem; align-items: baseline; margin-bottom: .5rem; }
        .key { color: #94a3b8; width: 140px; flex-shrink: 0; }
        .val { color: #f1f5f9; }
    </style>
</head>
<body>
<h2>🔧 Diagnóstico SMTP — L&J CRM</h2>

<div class="card">
    <h3 style="margin:0 0 1rem;color:#cbd5e1">Configuración activa</h3>
    <div class="row"><span class="key">Host:</span>     <span class="val"><?= htmlspecialchars($smtpHost) ?></span></div>
    <div class="row"><span class="key">Puerto:</span>   <span class="val"><?= htmlspecialchars($smtpPort) ?></span></div>
    <div class="row"><span class="key">Usuario:</span>  <span class="val"><?= htmlspecialchars($smtpUser) ?></span></div>
    <div class="row"><span class="key">Contraseña:</span><span class="val"><?= $passOk ?></span></div>
    <div class="row"><span class="key">Extensión OpenSSL:</span><span class="val"><?= extension_loaded('openssl') ? '<span class="ok">✓ activa</span>' : '<span class="err">✗ no disponible</span>' ?></span></div>
</div>

<?php if ($resultado): ?>
<div class="card">
    <p class="<?= $resultado['ok'] ? 'ok' : 'err' ?>">
        <?= $resultado['ok'] ? '✓' : '✗' ?> <?= htmlspecialchars($resultado['msg']) ?>
    </p>
</div>
<?php endif; ?>

<?php if ($smtpLog): ?>
<div class="card">
    <h3 style="margin:0 0 1rem;color:#cbd5e1">Conversación SMTP</h3>
    <div class="log"><?= htmlspecialchars(implode("\n", $smtpLog)) ?></div>
</div>
<?php endif; ?>

<div class="card">
    <h3 style="margin:0 0 1rem;color:#cbd5e1">Enviar email de prueba</h3>
    <form method="POST">
        <label for="destino">Dirección de destino</label>
        <input type="email" id="destino" name="destino" value="<?= htmlspecialchars($smtpUser) ?>" required>
        <br><button type="submit">Enviar test</button>
    </form>
</div>
</body>
</html>
