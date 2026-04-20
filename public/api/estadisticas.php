<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

function ok($data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}

require __DIR__ . '/../config/conexion.php';

try {
    // Totales
    $totales = [
        'contactos'          => (int) $pdo->query("SELECT COUNT(*) FROM contactos")->fetchColumn(),
        'leads'              => (int) $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
        'oportunidades_activas' => (int) $pdo->query("SELECT COUNT(*) FROM oportunidades WHERE etapa NOT IN ('cerrada_ganada','cerrada_perdida')")->fetchColumn(),
        'actividades'        => (int) $pdo->query("SELECT COUNT(*) FROM actividades")->fetchColumn(),
    ];

    // Valor pipeline (oportunidades abiertas)
    $valorPipeline = (float) $pdo->query(
        "SELECT COALESCE(SUM(valor), 0) FROM oportunidades WHERE etapa NOT IN ('cerrada_ganada','cerrada_perdida')"
    )->fetchColumn();

    // Tasa de conversión de leads
    $leadStats = $pdo->query("SELECT COUNT(*) as total, SUM(estado = 'convertido') as convertidos FROM leads")->fetch();
    $tasaConversion = $leadStats['total'] > 0
        ? round(($leadStats['convertidos'] / $leadStats['total']) * 100, 1)
        : 0.0;

    // Leads por estado
    $leadsPorEstado = $pdo->query(
        "SELECT estado, COUNT(*) as total FROM leads GROUP BY estado ORDER BY FIELD(estado,'nuevo','contactado','calificado','convertido','descartado')"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Oportunidades por etapa
    $oportunidadesPorEtapa = $pdo->query(
        "SELECT etapa, COUNT(*) as total, COALESCE(SUM(valor), 0) as valor_sum
         FROM oportunidades
         GROUP BY etapa
         ORDER BY FIELD(etapa,'prospecto','propuesta','negociacion','cerrada_ganada','cerrada_perdida')"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Actividades por tipo
    $actividadesPorTipo = $pdo->query(
        "SELECT tipo, COUNT(*) as total FROM actividades GROUP BY tipo ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    ok([
        'totales'               => $totales,
        'valor_pipeline'        => $valorPipeline,
        'tasa_conversion'       => $tasaConversion,
        'leads_por_estado'      => $leadsPorEstado,
        'oportunidades_por_etapa' => $oportunidadesPorEtapa,
        'actividades_por_tipo'  => $actividadesPorTipo,
    ]);

} catch (PDOException $e) {
    err('Error al obtener estadísticas', 500);
}
