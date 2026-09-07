<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'No autorizado';
    exit;
}

require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/modulos_visibilidad.php';
verificarModuloVisibleTexto($pdo, 'facturas');
require __DIR__ . '/../config/facturas_documentos.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'ID requerido';
    exit;
}

try {
    $factura = facturaDocumentoCargar($pdo, $id);
    if (!$factura) {
        http_response_code(404);
        echo 'Factura no encontrada';
        exit;
    }

    $pdf = facturaDocumentoPdf($factura);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . facturaDocumentoNombre($factura) . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
} catch (Throwable $e) {
    error_log('[factura_pdf] ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al generar PDF';
}
