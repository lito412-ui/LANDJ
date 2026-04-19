<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SESSION['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso restringido a administradores']);
    exit;
}

function ok($data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}

require __DIR__ . '/../config/conexion.php';

try {
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();

    // Forzar estadísticas frescas (evita NULL en Update_time con InnoDB en MySQL 8)
    $pdo->exec("SET SESSION information_schema_stats_expiry = 0");

    $s = $pdo->query("SHOW TABLE STATUS");
    $tablas = $s->fetchAll();

    $totalFilas  = 0;
    $totalBytes  = 0;
    $resultado   = [];

    foreach ($tablas as $t) {
        $filas  = (int) ($t['Rows']         ?? 0);
        $datos  = (int) ($t['Data_length']  ?? 0);
        $indice = (int) ($t['Index_length'] ?? 0);
        $bytes  = $datos + $indice;

        $totalFilas  += $filas;
        $totalBytes  += $bytes;

        $resultado[] = [
            'nombre'       => $t['Name'],
            'motor'        => $t['Engine']    ?? '—',
            'filas'        => $filas,
            'bytes'        => $bytes,
            'colacion'     => $t['Collation'] ?? '—',
            'actualizada'  => $t['Update_time'] ?? null,
        ];
    }

    ok([
        'db'           => $dbName,
        'tablas'       => $resultado,
        'total_tablas' => count($resultado),
        'total_filas'  => $totalFilas,
        'total_bytes'  => $totalBytes,
    ]);
} catch (PDOException $e) {
    err('Error al consultar la base de datos', 500);
}
