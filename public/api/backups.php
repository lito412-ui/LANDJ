<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SESSION['rol'] !== 'administrador') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso restringido a administradores']);
    exit;
}

define('BACKUP_DIR', realpath(__DIR__ . '/../../') . '/backups/');

if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

function ok($data): void {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'data' => $data]);
}
function err(string $msg, int $code = 400): void {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}

function nombreSeguro(string $nombre): string {
    $base = basename($nombre);
    if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{6}\.sql$/', $base)) return '';
    return $base;
}

function formatBytes(int $bytes): string {
    if ($bytes < 1024)       return $bytes . ' B';
    if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

function listarBackups(): array {
    $archivos = glob(BACKUP_DIR . 'backup_*.sql') ?: [];
    usort($archivos, fn($a, $b) => filemtime($b) - filemtime($a));
    return array_map(fn($f) => [
        'nombre' => basename($f),
        'bytes'  => filesize($f),
        'tamano' => formatBytes(filesize($f)),
        'fecha'  => date('Y-m-d H:i:s', filemtime($f)),
    ], $archivos);
}

function generarDump(PDO $pdo): string {
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $out  = "-- LANDJ CRM Backup\n";
    $out .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $out .= "-- Base de datos: $dbName\n\n";
    $out .= "SET NAMES utf8mb4;\n";
    $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $tabla) {
        $create = $pdo->query("SHOW CREATE TABLE `$tabla`")->fetch(PDO::FETCH_NUM);
        $out .= "-- ─── $tabla ────────────────────────────────────────────\n";
        $out .= "DROP TABLE IF EXISTS `$tabla`;\n";
        $out .= $create[1] . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $cols = '`' . implode('`, `', array_keys($rows[0])) . '`';
            foreach (array_chunk($rows, 100) as $chunk) {
                $vals = [];
                foreach ($chunk as $row) {
                    $rowVals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), array_values($row));
                    $vals[] = '(' . implode(', ', $rowVals) . ')';
                }
                $out .= "INSERT INTO `$tabla` ($cols) VALUES\n  " . implode(",\n  ", $vals) . ";\n";
            }
            $out .= "\n";
        }
    }

    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $out;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// ─── Descargar ────────────────────────────────────────────────────────────────
if ($method === 'GET' && $action === 'descargar') {
    $nombre = nombreSeguro($_GET['archivo'] ?? '');
    if (!$nombre) { err('Archivo no válido'); exit; }
    $ruta = BACKUP_DIR . $nombre;
    if (!file_exists($ruta)) { err('Archivo no encontrado', 404); exit; }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Content-Length: ' . filesize($ruta));
    readfile($ruta);
    exit;
}

// ─── Listar ───────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    ok(listarBackups());
    exit;
}

// ─── Crear ────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    csrfValidar();
    require __DIR__ . '/../config/conexion.php';
    try {
        $dump   = generarDump($pdo);
        $nombre = 'backup_' . date('Y-m-d_His') . '.sql';
        $ruta   = BACKUP_DIR . $nombre;
        file_put_contents($ruta, $dump);
        $bytes  = filesize($ruta);
        ok(['nombre' => $nombre, 'bytes' => $bytes, 'tamano' => formatBytes($bytes), 'fecha' => date('Y-m-d H:i:s')]);
    } catch (Throwable $e) {
        err('Error al generar el backup', 500);
    }
    exit;
}

// ─── Eliminar ─────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    csrfValidar();
    $body   = json_decode(file_get_contents('php://input'), true);
    $nombre = nombreSeguro($body['archivo'] ?? '');
    if (!$nombre) { err('Archivo no válido'); exit; }
    $ruta = BACKUP_DIR . $nombre;
    if (!file_exists($ruta)) { err('Archivo no encontrado', 404); exit; }
    unlink($ruta);
    ok(null);
    exit;
}

err('Método no permitido', 405);
