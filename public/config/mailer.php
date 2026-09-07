<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Obtiene un valor de la tabla `system_config` (configuración del panel admin).
 */
function getDbConfig(string $clave, ?int $grupoId = null): string
{
    static $cache = [];
    $grupoId = $grupoId ?? obtenerIdGrupoActual();
    $cacheKey = $grupoId . ':' . $clave;
    if (isset($cache[$cacheKey])) return $cache[$cacheKey];

    global $pdo;
    if (!isset($pdo)) {
        try {
            require_once __DIR__ . '/conexion.php';
        } catch (Throwable $e) {}
    }

    if (isset($pdo)) {
        try {
            $s = $pdo->prepare('SELECT valor FROM system_config WHERE id_grupo = ? AND clave = ? AND valor IS NOT NULL AND valor != ""');
            $s->execute([$grupoId, $clave]);
            $row = $s->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['valor'] !== null && $row['valor'] !== '') {
                $cache[$cacheKey] = (string) $row['valor'];
                return $cache[$cacheKey];
            }
        } catch (Throwable $e) {}
    }

    $cache[$cacheKey] = '';
    return '';
}

/**
 * Comprueba si el SMTP del SISTEMA (.env) está configurado.
 * Se usa para 2FA y seguridad.
 */
function esSmtpSistemaConfigurado(): bool
{
    $host = trim(getenv('SMTP_HOST') ?: '');
    $user = trim(getenv('SMTP_USER') ?: '');
    $pass = trim(getenv('SMTP_PASS') ?: '');
    return ($host !== '' && $user !== '' && $pass !== '');
}

/**
 * Comprueba si el SMTP de NEGOCIO (Base de Datos - panel admin) está configurado.
 * Se usa para Facturas y Presupuestos a clientes.
 */
function esSmtpNegocioConfigurado(?int $grupoId = null): bool
{
    $host = getDbConfig('smtp_host', $grupoId);
    $user = getDbConfig('smtp_user', $grupoId);
    $pass = getDbConfig('smtp_pass', $grupoId);
    return ($host !== '' && $user !== '' && $pass !== '');
}

/**
 * Envía un email utilizando una instancia configurada de PHPMailer.
 */
function ejecutarEnvio(
    string $host,
    int $port,
    string $user,
    string $pass,
    string $fromEmail,
    string $fromName,
    string $encryption,
    string $to,
    string $subject,
    string $htmlBody,
    array $attachments = []
): bool {
    $mail = new PHPMailer(true);

    try {
        $toClean = strtolower(trim($to));
        if (!filter_var($toClean, FILTER_VALIDATE_EMAIL)) {
            error_log("[mailer] Destinatario no válido: '{$to}'");
            return false;
        }

        $mail->isSMTP();
        $mail->SMTPDebug   = SMTP::DEBUG_OFF;
        $mail->Debugoutput = 'error_log';
        $mail->Host        = $host;
        $mail->SMTPAuth    = true;
        $mail->Username    = $user;
        $mail->Password    = str_replace(' ', '', $pass);

        $encLower = strtolower(trim($encryption));
        if ($encLower === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encLower === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPAutoTLS = false;
        }

        $mail->Port    = $port > 0 ? $port : 587;
        $mail->CharSet = 'UTF-8';

        $remitente = trim($fromEmail) ?: $user;
        $mail->setFrom($remitente, $fromName);
        $mail->addAddress($toClean);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;

        foreach ($attachments as $att) {
            if (!empty($att['path'])) {
                $mail->addAttachment(
                    $att['path'],
                    $att['name'] ?? '',
                    $att['encoding'] ?? PHPMailer::ENCODING_BASE64,
                    $att['type'] ?? ''
                );
            } elseif (isset($att['data'])) {
                $mail->addStringAttachment(
                    $att['data'],
                    $att['name'] ?? 'adjunto',
                    $att['encoding'] ?? PHPMailer::ENCODING_BASE64,
                    $att['type'] ?? 'application/octet-stream'
                );
            }
        }

        $mail->send();
        error_log("[mailer] Email enviado a {$toClean} vía {$host}");
        return true;

    } catch (Exception $e) {
        error_log("[mailer] Error enviando a {$to} vía {$host}: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * CANAL SISTEMA (2FA, avisos de seguridad, verificación).
 * Lee EXCLUSIVAMENTE las variables de entorno del servidor (.env).
 */
function enviarEmailSistema(string $to, string $subject, string $htmlBody, array $attachments = []): bool
{
    if (!esSmtpSistemaConfigurado()) {
        error_log('[mailer] No se puede enviar correo del sistema: SMTP del sistema (.env) no configurado');
        return false;
    }

    $host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $port       = (int) (getenv('SMTP_PORT') ?: 587);
    $user       = getenv('SMTP_USER') ?: '';
    $pass       = getenv('SMTP_PASS') ?: '';
    $from       = getenv('MAIL_FROM') ?: $user;
    $encryption = getenv('SMTP_ENCRYPTION') ?: 'tls';

    return ejecutarEnvio($host, $port, $user, $pass, $from, 'L&J CRM - Seguridad', $encryption, $to, $subject, $htmlBody, $attachments);
}

/**
 * CANAL NEGOCIO (Presupuestos, Facturas, correos a contactos/clientes).
 * Lee de la configuración guardada por el administrador en la Base de Datos (system_config).
 * No usa el .env: cada empresa debe configurar sus propias credenciales.
 */
function enviarEmailNegocio(string $to, string $subject, string $htmlBody, array $attachments = [], ?int $grupoId = null): bool
{
    if (esSmtpNegocioConfigurado($grupoId)) {
        $host       = getDbConfig('smtp_host', $grupoId);
        $port       = (int) (getDbConfig('smtp_port', $grupoId) ?: 587);
        $user       = getDbConfig('smtp_user', $grupoId);
        $pass       = getDbConfig('smtp_pass', $grupoId);
        $from       = getDbConfig('smtp_from', $grupoId) ?: $user;
        $encryption = getDbConfig('smtp_encryption', $grupoId) ?: 'tls';

        return ejecutarEnvio($host, $port, $user, $pass, $from, 'L&J CRM', $encryption, $to, $subject, $htmlBody, $attachments);
    }

    error_log('[mailer] No se puede enviar correo de negocio: el SMTP del grupo no está configurado');
    return false;
}

/**
 * Alias general para correos salientes del CRM (presupuestos, facturas, etc.).
 */
function enviarEmail(string $to, string $subject, string $htmlBody, array $attachments = [], ?int $grupoId = null): bool
{
    return enviarEmailNegocio($to, $subject, $htmlBody, $attachments, $grupoId);
}

/**
 * Plantilla HTML para códigos de verificación 2FA.
 */
function plantilla2FA(string $nombre, string $codigo): string
{
    $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Inter,-apple-system,BlinkMacSystemFont,Arial,sans-serif;">
  <div style="max-width:480px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:28px 32px;">
      <h1 style="margin:0;color:#fff;font-size:1.6rem;font-weight:700;letter-spacing:-0.01em;">L&amp;J CRM</h1>
      <p style="margin:4px 0 0;color:rgba(255,255,255,.85);font-size:.9rem;">Verificación de Seguridad</p>
    </div>
    <div style="padding:32px;">
      <p style="margin:0 0 8px;color:#1e293b;font-size:1rem;">Hola, <strong>{$nombreSeguro}</strong></p>
      <p style="margin:0 0 24px;color:#64748b;font-size:.9rem;line-height:1.5;">
        Introduce el siguiente código en la pantalla de verificación para acceder a tu cuenta. Caduca en <strong>10 minutos</strong>.
      </p>
      <div style="background:#f8fafc;border:2px dashed #cbd5e1;border-radius:12px;padding:20px;text-align:center;margin-bottom:24px;">
        <span style="font-size:2.4rem;font-weight:800;letter-spacing:.3em;color:#667eea;font-family:monospace;">{$codigoSeguro}</span>
      </div>
      <p style="margin:0;color:#94a3b8;font-size:.78rem;line-height:1.5;">
        Si no has intentado iniciar sesión en L&amp;J CRM, ignora este correo.<br>
        Nunca compartas este código con nadie.
      </p>
    </div>
  </div>
</body>
</html>
HTML;
}
