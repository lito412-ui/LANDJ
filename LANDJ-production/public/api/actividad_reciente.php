<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require __DIR__ . '/../config/conexion.php';

$userId  = (int) $_SESSION['user_id'];
$esAdmin = ($_SESSION['rol'] ?? '') === 'administrador';
$limite  = min((int) ($_GET['limite'] ?? 10), 25);

$sql    = "SELECT a.accion, a.tabla, a.created_at, u.nombre AS usuario
           FROM auditoria a
           LEFT JOIN usuarios u ON u.id_usuario = a.usuario_id";
$params = [];

if (!$esAdmin) {
    $sql   .= " WHERE a.usuario_id = ?";
    $params[] = $userId;
}

$sql .= " ORDER BY a.created_at DESC LIMIT ?";
$params[] = $limite;

try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    $TABLA_LABEL = [
        'contactos'      => 'Contacto',
        'leads'          => 'Lead',
        'oportunidades'  => 'Oportunidad',
        'actividades'    => 'Actividad',
        'usuarios'       => 'Usuario',
        'dominios'       => 'Dominio',
        'cuentas_correo' => 'Cuenta de correo',
    ];

    $ACCION_LABEL = ['crear' => 'creado', 'editar' => 'actualizado', 'eliminar' => 'eliminado'];
    $ACCION_TIPO  = ['crear' => 'success', 'editar' => 'info', 'eliminar' => 'danger'];

    $data = array_map(function ($r) use ($TABLA_LABEL, $ACCION_LABEL, $ACCION_TIPO) {
        $tabla  = $TABLA_LABEL[$r['tabla']]  ?? ucfirst($r['tabla']);
        $accion = $ACCION_LABEL[$r['accion']] ?? $r['accion'];
        $tipo   = $ACCION_TIPO[$r['accion']]  ?? 'info';

        return [
            'tipo'       => $tipo,
            'texto'      => "$tabla $accion",
            'usuario'    => $r['usuario'] ?? '—',
            'tiempo'     => tiempoRelativo($r['created_at']),
            'created_at' => $r['created_at'],
        ];
    }, $rows);

    echo json_encode(['ok' => true, 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de base de datos']);
}

function tiempoRelativo(string $fecha): string {
    $diff = time() - strtotime($fecha);
    if ($diff < 60)     return 'Ahora mismo';
    if ($diff < 3600)   return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400)  return 'Hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'Hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y', strtotime($fecha));
}
