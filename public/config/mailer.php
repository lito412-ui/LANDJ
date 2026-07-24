<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * EnvÃ­a un email HTML vÃ­a SMTP (PHPMailer).
 * Credenciales leÃ­das de variables de entorno definidas en .env / docker-compose.
 */
function enviarEmail(string $to, string $subject, string $htmlBody, array $attachments = []): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = 'error_log';
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER') ?: '';
        $pass = getenv('SMTP_PASS') ?: '';
        $mail->Password   = str_replace(' ', '', $pass);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) (getenv('SMTP_PORT') ?: 587);
        $mail->CharSet    = 'UTF-8';

        $from     = getenv('MAIL_FROM') ?: $mail->Username;
        $mail->setFrom($from, 'L&J CRM');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        foreach ($attachments as $attachment) {
            if (!empty($attachment['path'])) {
                $mail->addAttachment(
                    $attachment['path'],
                    $attachment['name'] ?? '',
                    $attachment['encoding'] ?? PHPMailer::ENCODING_BASE64,
                    $attachment['type'] ?? ''
                );
                continue;
            }

            if (isset($attachment['data'])) {
                $mail->addStringAttachment(
                    $attachment['data'],
                    $attachment['name'] ?? 'adjunto',
                    $attachment['encoding'] ?? PHPMailer::ENCODING_BASE64,
                    $attachment['type'] ?? 'application/octet-stream'
                );
            }
        }

        $mail->send();
        error_log("[mailer] Email enviado a {$to}");
        return true;

    } catch (Exception) {
        error_log("[mailer] Error al enviar a {$to}: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Genera el HTML del email de cÃ³digo 2FA.
 */
function plantilla2FA(string $nombre, string $codigo): string
{
    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Inter,Arial,sans-serif;">
  <div style="max-width:480px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:28px 32px;">
      <h1 style="margin:0;color:#fff;font-size:1.6rem;font-weight:700;letter-spacing:-0.01em;">L&amp;J CRM</h1>
      <p style="margin:4px 0 0;color:rgba(255,255,255,.8);font-size:.9rem;">Verificación en dos pasos</p>
    </div>
    <div style="padding:32px;">
      <p style="margin:0 0 8px;color:#1e293b;font-size:1rem;">Hola, <strong>{$nombre}</strong></p>
      <p style="margin:0 0 24px;color:#64748b;font-size:.9rem;line-height:1.5;">
        Introduce este código en la pantalla de verificación. Caduca en <strong>10 minutos</strong>.
      </p>
      <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;">
        <span style="font-size:2.2rem;font-weight:800;letter-spacing:.3em;color:#667eea;">{$codigo}</span>
      </div>
      <p style="margin:0;color:#94a3b8;font-size:.78rem;line-height:1.5;">
        Si no has intentado iniciar sesiónn, ignora este correo.<br>
        Nunca compartas este código con nadie.
      </p>
    </div>
  </div>
</body>
</html>
HTML;
}
