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
    die("Error de conexión: " . $e->getMessage());
}
?>