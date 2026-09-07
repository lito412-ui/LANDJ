<?php
/**
 * API: Configuración del sistema (SMTP, etc.)
 * Solo accesible para administradores.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

// Solo admins
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}
if (($_SESSION['rol'] ?? '') !== 'administrador') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Solo los administradores pueden gestionar la configuración del sistema']);
    exit;
}

csrfValidar();
require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/auditoria.php';

$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';
$grupoId = obtenerIdGrupoActual();

function ok($data = null): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }

/**
 * Obtiene un valor de system_config perteneciente exclusivamente al grupo actual.
 */
function getConfig(PDO $pdo, int $grupoId, string $clave): string {
    try {
        $s = $pdo->prepare('SELECT valor FROM system_config WHERE id_grupo = ? AND clave = ?');
        $s->execute([$grupoId, $clave]);
        $row = $s->fetch();
        if ($row && $row['valor'] !== null && $row['valor'] !== '') {
            return $row['valor'];
        }
    } catch (Throwable $e) {}
    return '';
}

try {
    // ── GET: leer configuración SMTP actual ─────────────────────
    if ($method === 'GET' && $accion === 'smtp') {
        $activePass = getConfig($pdo, $grupoId, 'smtp_pass');
        ok([
            'smtp_host'       => getConfig($pdo, $grupoId, 'smtp_host'),
            'smtp_port'       => getConfig($pdo, $grupoId, 'smtp_port') ?: '587',
            'smtp_user'       => getConfig($pdo, $grupoId, 'smtp_user'),
            'smtp_pass'       => '', // Nunca devolver la contraseña en texto plano
            'smtp_from'       => getConfig($pdo, $grupoId, 'smtp_from'),
            'smtp_encryption' => getConfig($pdo, $grupoId, 'smtp_encryption') ?: 'tls',
            'has_password'    => ($activePass !== ''),
        ]);
        exit;
    }

    // ── PUT: guardar configuración SMTP ─────────────────────────
    if ($method === 'PUT' && $accion === 'smtp') {
        $b = body();

        $host       = trim($b['smtp_host']       ?? '');
        $port       = (int) ($b['smtp_port']      ?? 587);
        $user       = trim($b['smtp_user']        ?? '');
        $pass       = trim($b['smtp_pass']        ?? ''); // Vacío = mantener anterior
        $from       = trim($b['smtp_from']        ?? '');
        $encryption = trim($b['smtp_encryption']  ?? 'tls');

        if ($host === '') { err('El servidor SMTP (host) es obligatorio'); exit; }
        if ($port < 1 || $port > 65535) { err('Puerto SMTP no válido'); exit; }
        if ($user === '') { err('El usuario SMTP es obligatorio'); exit; }
        if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            err('La dirección "De" no es un email válido'); exit;
        }
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            err('Tipo de cifrado no válido'); exit;
        }

        $configs = [
            'smtp_host'       => $host,
            'smtp_port'       => (string) $port,
            'smtp_user'       => $user,
            'smtp_from'       => $from ?: $user,
            'smtp_encryption' => $encryption,
        ];

        if ($pass !== '') {
            $pass = str_replace(' ', '', $pass);
            $configs['smtp_pass'] = $pass;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO system_config (id_grupo, clave, valor) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );

        foreach ($configs as $clave => $valor) {
            $stmt->execute([$grupoId, $clave, $valor]);
        }

        $auditData = $configs;
        unset($auditData['smtp_pass']);
        registrarAuditoria($pdo, 'system_config', 0, 'editar', null, $auditData);

        ok(['message' => 'Configuración SMTP guardada correctamente']);
        exit;
    }

    // ── POST: probar configuración SMTP ─────────────────────────
    if ($method === 'POST' && $accion === 'smtp-test') {
        $b = body();
        $destino = trim($b['destino'] ?? '');

        if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            err('Email de destino no válido'); exit;
        }

        $host       = getConfig($pdo, $grupoId, 'smtp_host');
        $port       = (int) (getConfig($pdo, $grupoId, 'smtp_port') ?: 587);
        $user       = getConfig($pdo, $grupoId, 'smtp_user');
        $pass       = getConfig($pdo, $grupoId, 'smtp_pass');
        $from       = getConfig($pdo, $grupoId, 'smtp_from') ?: $user;
        $encryption = getConfig($pdo, $grupoId, 'smtp_encryption') ?: 'tls';

        if (!$host || !$user || !$pass) {
            err('Configura y guarda el host, usuario y contraseña SMTP antes de probar'); exit;
        }

        require_once __DIR__ . '/../../vendor/autoload.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->SMTPDebug  = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = str_replace(' ', '', $pass);
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAutoTLS = false;
            }

            $mail->setFrom($from, 'L&J CRM');
            $mail->addAddress($destino);
            $mail->isHTML(true);
            $mail->Subject = 'Prueba de Correo SMTP — L&J CRM';
            $mail->Body    = '<h2>¡Configuración SMTP correcta!</h2><p>Este es un email de prueba enviado desde tu panel de <strong>L&J CRM</strong>.</p><p>Si has recibido este mensaje, el servidor de correo está correctamente configurado para enviar presupuestos y facturas a tus clientes.</p>';

            $mail->send();
            ok(['message' => "Email de prueba enviado con éxito a {$destino}"]);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            err('Error SMTP: ' . $mail->ErrorInfo);
        }
        exit;
    }

    err('Acción no reconocida', 404);

} catch (Throwable $e) {
    error_log('[system_config] ' . $e->getMessage());
    err('Error interno del servidor: ' . $e->getMessage(), 500);
}
