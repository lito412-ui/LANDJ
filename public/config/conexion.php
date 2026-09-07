<?php
$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'db';
$port = getenv('DB_PORT') !== false ? getenv('DB_PORT') : '3306';
$db   = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'landj_db';
$user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log('[conexion] Error de conexión a la base de datos: ' . $e->getMessage());
    http_response_code(500);

    $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) ||
             (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Error de conexión con la base de datos']);
    } else {
        echo 'Error interno del servidor. Por favor, inténtelo más tarde.';
    }
    exit;
}

require_once __DIR__ . '/multitenant.php';
asegurarEsquemaMultitenant($pdo);