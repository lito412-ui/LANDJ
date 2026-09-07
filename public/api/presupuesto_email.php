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
require __DIR__ . '/../config/modulos_visibilidad.php';
verificarModuloVisible($pdo, 'presupuestos');
require __DIR__ . '/../config/mailer.php';
require __DIR__ . '/../config/presupuestos_documentos.php';
require __DIR__ . '/../config/auditoria.php';

function presupuestoEmailErr(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    presupuestoEmailErr('Metodo no permitido', 405);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$id = (int) ($payload['id'] ?? ($_GET['id'] ?? 0));
if ($id <= 0) {
    presupuestoEmailErr('ID requerido');
    exit;
}

try {
    $grupoId = obtenerIdGrupoActual();
    if (!recursoPerteneceAlGrupo($pdo, 'presupuestos', 'id_presupuesto', $id, $grupoId)) {
        presupuestoEmailErr('Presupuesto no encontrado', 404);
        exit;
    }
    $presupuesto = presupuestoDocumentoCargar($pdo, $id);
    if (!$presupuesto) {
        presupuestoEmailErr('Presupuesto no encontrado', 404);
        exit;
    }
    if (empty($presupuesto['contacto_email']) || !filter_var($presupuesto['contacto_email'], FILTER_VALIDATE_EMAIL)) {
        presupuestoEmailErr('El contacto no tiene un email valido');
        exit;
    }

    if (!esSmtpNegocioConfigurado() && !esSmtpSistemaConfigurado()) {
        presupuestoEmailErr('El servidor de correo (SMTP) no está configurado. Ve a Configuración > Servidor de Correo (SMTP) para activarlo.', 400);
        exit;
    }

    // Genera el token de confirmacion publica la primera vez que se envia
    // (si ya existe, se reutiliza para no invalidar un enlace ya enviado)
    if (empty($presupuesto['token_confirmacion'])) {
        $token = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE presupuestos SET token_confirmacion = ? WHERE id_presupuesto = ? AND id_grupo = ?")
            ->execute([$token, $id, $grupoId]);
        $presupuesto['token_confirmacion'] = $token;
    }

    // Detección robusta de URL base para cualquier hosting/proxy/puerto
    $envUrl = trim(getenv('APP_URL') ?: '');
    if ($envUrl !== '') {
        $baseUrl = rtrim($envUrl, '/');
    } else {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
        $scheme = $isHttps ? 'https' : 'http';
        $hostRaw = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (strpos($hostRaw, ',') !== false) {
            $parts = explode(',', $hostRaw);
            $hostRaw = trim($parts[0]);
        }
        $baseUrl = $scheme . '://' . $hostRaw;
    }
    $urlConfirmacion = $baseUrl . '/presupuesto-confirmar.php?token=' . $presupuesto['token_confirmacion'];

    $pdf = presupuestoDocumentoPdf($presupuesto);
    $ok = enviarEmailNegocio(
        $presupuesto['contacto_email'],
        'Presupuesto ' . $presupuesto['numero'],
        presupuestoDocumentoHtmlEmail($presupuesto, $urlConfirmacion),
        [[
            'data' => $pdf,
            'name' => presupuestoDocumentoNombre($presupuesto),
            'encoding' => 'base64',
            'type' => 'application/pdf',
        ]]
    );

    if (!$ok) {
        presupuestoEmailErr('No se pudo enviar el email. Revisa los datos de conexión SMTP en Configuración > Servidor de Correo.', 500);
        exit;
    }

    // Al enviarse por primera vez, si seguia en borrador pasa automaticamente a "enviado"
    if ($presupuesto['estado'] === 'borrador') {
        $pdo->prepare("UPDATE presupuestos SET estado = 'enviado' WHERE id_presupuesto = ? AND id_grupo = ?")->execute([$id, $grupoId]);
        registrarAuditoria($pdo, 'presupuestos', $id, 'editar', ['estado' => 'borrador'], ['estado' => 'enviado']);
    }

    echo json_encode(['ok' => true, 'data' => ['sent' => true]]);
} catch (Throwable $e) {
    error_log('[presupuesto_email] ' . $e->getMessage());
    presupuestoEmailErr('Error al enviar presupuesto', 500);
}
