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

function preferenciasUsuario(PDO $pdo, int $userId): array {
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

        // ─── GET: pendientes del usuario ────────────────────────────────────
        case 'GET':
            $pref = preferenciasUsuario($pdo, $userId);

            if (!(int)$pref['activas']) {
                ok(['preferencias' => $pref, 'pendientes' => [], 'total' => 0]);
                break;
            }

            // Notificación pendiente = actividad cuyo recordatorio ya ha llegado,
            // no está completada y no ha sido descartada.
            // Devolvemos también el nombre de la entidad vinculada (contacto/lead/oportunidad).
            $sql = "
                SELECT
                    a.id_actividad,
                    a.tipo,
                    a.descripcion,
                    a.fecha,
                    a.recordatorio_at,
                    a.completada,
                    a.contacto_id,
                    a.lead_id,
                    a.oportunidad_id,
                    a.created_at,
                    c.nombre  AS contacto_nombre,
                    c.apellidos AS contacto_apellidos,
                    l.nombre  AS lead_nombre,
                    o.titulo  AS oportunidad_titulo,
                    TIMESTAMPDIFF(MINUTE, NOW(), a.recordatorio_at) AS minutos_restantes
                FROM actividades a
                LEFT JOIN contactos     c ON c.id_contacto    = a.contacto_id
                LEFT JOIN leads         l ON l.id_lead        = a.lead_id
                LEFT JOIN oportunidades o ON o.id_oportunidad = a.oportunidad_id
                WHERE a.creado_por = ?
                  AND a.recordatorio_at IS NOT NULL
                  AND a.recordatorio_at <= NOW()
                  AND a.completada = 0
                  AND a.recordatorio_descartado = 0
                ORDER BY a.recordatorio_at ASC
                LIMIT 50
            ";
            $s = $pdo->prepare($sql);
            $s->execute([$userId]);
            $rows = $s->fetchAll();

            ok([
                'preferencias' => $pref,
                'pendientes'   => $rows,
                'total'        => count($rows),
            ]);
            break;

        // ─── POST: acción sobre una notificación ────────────────────────────
        // body: { id_actividad: X, accion: 'descartar' | 'completar' | 'posponer', minutos?: 15 }
        case 'POST':
            csrfValidar();
            $b = body();

            $idAct  = isset($b['id_actividad']) ? (int)$b['id_actividad'] : 0;
            $accion = trim($b['accion'] ?? '');

            if ($idAct <= 0)                                      { err('id_actividad requerido'); break; }
            if (!in_array($accion, ['descartar','completar','posponer'], true)) {
                err('Acción no válida'); break;
            }

            // Comprobar propiedad
            $sChk = $pdo->prepare("SELECT * FROM actividades WHERE id_actividad = ? AND creado_por = ?");
            $sChk->execute([$idAct, $userId]);
            $antes = $sChk->fetch();
            if (!$antes) { err('Actividad no encontrada', 404); break; }

            if ($accion === 'descartar') {
                $pdo->prepare("UPDATE actividades SET recordatorio_descartado = 1 WHERE id_actividad = ?")
                    ->execute([$idAct]);
            } elseif ($accion === 'completar') {
                $pdo->prepare("UPDATE actividades SET completada = 1, recordatorio_descartado = 1 WHERE id_actividad = ?")
                    ->execute([$idAct]);
            } else { // posponer
                $minutos = isset($b['minutos']) ? (int)$b['minutos'] : 15;
                if ($minutos < 1 || $minutos > 43200) { err('Minutos fuera de rango (1..43200)'); break; }
                $pdo->prepare("UPDATE actividades SET recordatorio_at = DATE_ADD(NOW(), INTERVAL ? MINUTE), recordatorio_descartado = 0 WHERE id_actividad = ?")
                    ->execute([$minutos, $idAct]);
            }

            $sAfter = $pdo->prepare("SELECT * FROM actividades WHERE id_actividad = ?");
            $sAfter->execute([$idAct]);
            $despues = $sAfter->fetch() ?: null;
            registrarAuditoria($pdo, 'actividades', $idAct, 'editar', $antes, $despues);

            ok(['accion' => $accion, 'actividad' => $despues]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
