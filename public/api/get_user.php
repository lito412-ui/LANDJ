<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json');

if (!isset($_SESSION['nombre'])) {
    echo json_encode(['logged' => false]);
    exit;
}

require __DIR__ . '/../config/conexion.php';

$stmt = $pdo->prepare("SELECT email, created_at FROM usuarios WHERE id_usuario = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch();

echo json_encode([
    'logged'     => true,
    'nombre'     => $_SESSION['nombre'],
    'rol'        => $_SESSION['rol'],
    'email'      => $row['email'] ?? '',
    'created_at' => $row['created_at'] ?? '',
]);
?>