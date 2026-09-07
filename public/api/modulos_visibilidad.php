<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/seguridad.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

csrfValidar();

require __DIR__ . '/../config/conexion.php';
require __DIR__ . '/../config/auditoria.php';

$userId = (int) $_SESSION['user_id'];
$rol    = $_SESSION['rol'] ?? 'usuario';
$method = $_SERVER['REQUEST_METHOD'];

function ok($data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}

try {
    switch ($method) {

        // ─── Cualquier usuario autenticado puede leer el mapa de visibilidad ──
        // (lo necesita el propio panel para ocultar secciones en su sidebar)
        case 'GET':
            $s = $pdo->query("SELECT modulo, etiqueta, categoria, visible FROM modulos_visibilidad ORDER BY categoria, etiqueta");
            ok($s->fetchAll());
            break;

        // ─── Solo administradores pueden cambiar la visibilidad ───────────────
        case 'PUT':
            if ($rol !== 'administrador') { err('Acceso restringido a administradores', 403); break; }

            $modulo = trim($_GET['modulo'] ?? '');
            if ($modulo === '') { err('Falta el parámetro "modulo"'); break; }

            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            if (!array_key_exists('visible', $body)) { err('Falta el campo "visible"'); break; }
            $visible = !empty($body['visible']) ? 1 : 0;

            $sAntes = $pdo->prepare("SELECT * FROM modulos_visibilidad WHERE modulo = ?");
            $sAntes->execute([$modulo]);
            $antes = $sAntes->fetch();
            if (!$antes) { err('Módulo no reconocido', 404); break; }

            $pdo->prepare("UPDATE modulos_visibilidad SET visible = ?, actualizado_por = ? WHERE modulo = ?")
                ->execute([$visible, $userId, $modulo]);

            registrarAuditoria(
                $pdo, 'modulos_visibilidad', 0, 'editar',
                ['modulo' => $modulo, 'visible' => (int) $antes['visible']],
                ['modulo' => $modulo, 'visible' => $visible]
            );

            ok(['modulo' => $modulo, 'visible' => (bool) $visible]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
