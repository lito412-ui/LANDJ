<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'No autorizado';
    exit;
}

require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/modulos_visibilidad.php';
verificarModuloVisibleTexto($pdo, 'presupuestos');
require __DIR__ . '/../config/presupuestos_documentos.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo 'ID requerido';
    exit;
}

try {
    if (!recursoPerteneceAlGrupo($pdo, 'presupuestos', 'id_presupuesto', $id, obtenerIdGrupoActual())) {
        http_response_code(404);
        echo 'Presupuesto no encontrado';
        exit;
    }
    $presupuesto = presupuestoDocumentoCargar($pdo, $id);
    if (!$presupuesto) {
        http_response_code(404);
        echo 'Presupuesto no encontrado';
        exit;
    }

    $pdf = presupuestoDocumentoPdf($presupuesto);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . presupuestoDocumentoNombre($presupuesto) . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
} catch (Throwable $e) {
    error_log('[presupuesto_pdf] ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al generar PDF';
}
