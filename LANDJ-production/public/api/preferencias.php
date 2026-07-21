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
require __DIR__ . '/../config/auditoria.php';

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function ok($data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }

const ANTELACIONES_VALIDAS  = [5, 15, 30, 60, 120, 1440];
const FRECUENCIAS_VALIDAS   = [30, 60, 120, 300];

function obtenerOCrear(PDO $pdo, int $userId): array {
    $s = $pdo->prepare("SELECT * FROM preferencias_notificaciones WHERE usuario_id = ?");
    $s->execute([$userId]);
    $row = $s->fetch();
    if ($row) return $row;

    $pdo->prepare("INSERT INTO preferencias_notificaciones (usuario_id) VALUES (?)")
        ->execute([$userId]);
    $s->execute([$userId]);
    return $s->fetch();
}

try {
    switch ($method) {

        case 'GET':
            ok(obtenerOCrear($pdo, $userId));
            break;

        case 'PUT':
            csrfValidar();
            $b = body();

            $antes = obtenerOCrear($pdo, $userId);

            $activas      = isset($b['activas'])      ? (int)(!!$b['activas'])      : (int)$antes['activas'];
            $sonido       = isset($b['sonido'])       ? (int)(!!$b['sonido'])       : (int)$antes['sonido'];
            $browserPush  = isset($b['browser_push']) ? (int)(!!$b['browser_push']) : (int)$antes['browser_push'];

            $antelacion   = isset($b['antelacion_minutos']) ? (int)$b['antelacion_minutos'] : (int)$antes['antelacion_minutos'];
            if (!in_array($antelacion, ANTELACIONES_VALIDAS, true)) {
                err('Antelación no válida'); break;
            }

            $frecuencia   = isset($b['frecuencia_segundos']) ? (int)$b['frecuencia_segundos'] : (int)$antes['frecuencia_segundos'];
            if (!in_array($frecuencia, FRECUENCIAS_VALIDAS, true)) {
                err('Frecuencia no válida'); break;
            }

            $pdo->prepare("
                UPDATE preferencias_notificaciones
                SET activas=?, sonido=?, browser_push=?, antelacion_minutos=?, frecuencia_segundos=?
                WHERE usuario_id=?
            ")->execute([$activas, $sonido, $browserPush, $antelacion, $frecuencia, $userId]);

            $s = $pdo->prepare("SELECT * FROM preferencias_notificaciones WHERE usuario_id = ?");
            $s->execute([$userId]);
            $despues = $s->fetch();
            registrarAuditoria($pdo, 'preferencias_notificaciones', $userId, 'editar', $antes, $despues);
            ok($despues);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
