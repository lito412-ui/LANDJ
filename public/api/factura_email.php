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
verificarModuloVisible($pdo, 'facturas');
require __DIR__ . '/../config/mailer.php';
require __DIR__ . '/../config/facturas_documentos.php';

function facturaEmailErr(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    facturaEmailErr('Metodo no permitido', 405);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$id = (int) ($payload['id'] ?? ($_GET['id'] ?? 0));
if ($id <= 0) {
    facturaEmailErr('ID requerido');
    exit;
}

try {
    if (!recursoPerteneceAlGrupo($pdo, 'facturas', 'id_factura', $id, obtenerIdGrupoActual())) {
        facturaEmailErr('Factura no encontrada', 404);
        exit;
    }
    $factura = facturaDocumentoCargar($pdo, $id);
    if (!$factura) {
        facturaEmailErr('Factura no encontrada', 404);
        exit;
    }
    if (empty($factura['contacto_email']) || !filter_var($factura['contacto_email'], FILTER_VALIDATE_EMAIL)) {
        facturaEmailErr('El contacto no tiene un email valido');
        exit;
    }

    $grupoId = obtenerIdGrupoActual();
    if (!esSmtpNegocioConfigurado($grupoId)) {
        facturaEmailErr('El servidor de correo (SMTP) no está configurado. Ve a Configuración > Servidor de Correo (SMTP) para activarlo.', 400);
        exit;
    }

    $pdf = facturaDocumentoPdf($factura);
    $ok = enviarEmailNegocio(
        $factura['contacto_email'],
        'Factura ' . $factura['numero'],
        facturaDocumentoHtmlEmail($factura),
        [[
            'data' => $pdf,
            'name' => facturaDocumentoNombre($factura),
            'encoding' => 'base64',
            'type' => 'application/pdf',
        ]],
        $grupoId
    );

    if (!$ok) {
        facturaEmailErr('No se pudo enviar el email. Revisa los datos de conexión SMTP en Configuración > Servidor de Correo.', 500);
        exit;
    }

    echo json_encode(['ok' => true, 'data' => ['sent' => true]]);
} catch (Throwable $e) {
    error_log('[factura_email] ' . $e->getMessage());
    facturaEmailErr('Error al enviar factura', 500);
}
