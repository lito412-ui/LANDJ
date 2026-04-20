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

require __DIR__ . '/../config/conexion.php';

$tabla      = trim($_GET['tabla']      ?? '');
$accion     = trim($_GET['accion']     ?? '');
$registroId = isset($_GET['registro_id']) ? (int) $_GET['registro_id'] : null;
$limite     = min((int) ($_GET['limite'] ?? 50), 200);
$offset     = max((int) ($_GET['offset'] ?? 0), 0);

$where  = [];
$params = [];

if ($tabla !== '') {
    $tablasValidas = ['contactos', 'leads', 'oportunidades', 'actividades', 'usuarios', 'dominios'];
    if (in_array($tabla, $tablasValidas, true)) {
        $where[]  = 'a.tabla = ?';
        $params[] = $tabla;
    }
}
if ($accion !== '') {
    $accionesValidas = ['crear', 'editar', 'eliminar'];
    if (in_array($accion, $accionesValidas, true)) {
        $where[]  = 'a.accion = ?';
        $params[] = $accion;
    }
}
if ($registroId !== null) {
    $where[]  = 'a.registro_id = ?';
    $params[] = $registroId;
}

$clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$sqlCount = "SELECT COUNT(*) FROM auditoria a$clausulaWhere";
$sqlData  = "SELECT a.*, u.nombre AS usuario_nombre
             FROM auditoria a
             LEFT JOIN usuarios u ON u.id_usuario = a.usuario_id
             $clausulaWhere
             ORDER BY a.created_at DESC
             LIMIT ? OFFSET ?";

try {
    $stCount = $pdo->prepare($sqlCount);
    $stCount->execute($params);
    $total = (int) $stCount->fetchColumn();

    $paramsData = [...$params, $limite, $offset];
    $s = $pdo->prepare($sqlData);
    $s->execute($paramsData);
    $rows = $s->fetchAll();

    foreach ($rows as &$row) {
        $row['datos_antes']   = $row['datos_antes']   ? json_decode($row['datos_antes'],   true) : null;
        $row['datos_despues'] = $row['datos_despues'] ? json_decode($row['datos_despues'], true) : null;
    }

    echo json_encode(['ok' => true, 'data' => $rows, 'total' => $total]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de base de datos']);
}
