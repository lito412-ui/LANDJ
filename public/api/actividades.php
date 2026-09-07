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
$grupoId = obtenerIdGrupoActual();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

function ok($data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function nullOrStr(string $v): ?string { $v = trim($v); return $v !== '' ? $v : null; }

const TIPOS_VALIDOS = ['nota', 'llamada', 'reunion', 'tarea', 'email'];

function validarActividad(array $b): array {
    $errors = [];

    $tipo = trim($b['tipo'] ?? 'nota');
    if (!in_array($tipo, TIPOS_VALIDOS, true))
        $errors[] = 'Tipo de actividad no válido';

    $descripcion = trim($b['descripcion'] ?? '');
    if ($descripcion === '')
        $errors[] = 'La descripción es obligatoria';
    elseif (strlen($descripcion) > 500)
        $errors[] = 'La descripción no puede superar 500 caracteres';

    $fecha = nullOrStr($b['fecha'] ?? '');
    if ($fecha !== null && !preg_match('/^\d{4}-\d{2}-\d{2}/', $fecha))
        $errors[] = 'Formato de fecha inválido';

    // Recordatorio: formato datetime-local del input HTML (YYYY-MM-DDTHH:MM)
    $recordatorioAt = nullOrStr($b['recordatorio_at'] ?? '');
    if ($recordatorioAt !== null) {
        $recordatorioAt = str_replace('T', ' ', $recordatorioAt);
        if (strlen($recordatorioAt) === 16) $recordatorioAt .= ':00';
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $recordatorioAt))
            $errors[] = 'Formato de recordatorio inválido';
    }

    $contactoId    = isset($b['contacto_id'])    && $b['contacto_id']    !== '' ? (int)$b['contacto_id']    : null;
    $leadId        = isset($b['lead_id'])         && $b['lead_id']        !== '' ? (int)$b['lead_id']        : null;
    $oportunidadId = isset($b['oportunidad_id'])  && $b['oportunidad_id'] !== '' ? (int)$b['oportunidad_id'] : null;

    if ($contactoId === null && $leadId === null && $oportunidadId === null)
        $errors[] = 'La actividad debe estar vinculada a un contacto, lead u oportunidad';

    return [
        'errors'          => $errors,
        'tipo'            => $tipo,
        'descripcion'     => $descripcion,
        'fecha'           => $fecha,
        'recordatorio_at' => $recordatorioAt,
        'contacto_id'     => $contactoId,
        'lead_id'         => $leadId,
        'oportunidad_id'  => $oportunidadId,
    ];
}

try {
    switch ($method) {

        // ─── Listar ──────────────────────────────────────────────────────────
        case 'GET':
            if ($id) {
                $s = $pdo->prepare("SELECT * FROM actividades WHERE id_actividad = ? AND id_grupo = ? LIMIT 1");
                $s->execute([$id, $grupoId]);
                $row = $s->fetch();
                $row ? ok($row) : err('Actividad no encontrada', 404);
                break;
            }

            $where  = ['id_grupo = ?'];
            $params = [$grupoId];

            if (isset($_GET['contacto_id'])    && (int)$_GET['contacto_id'] > 0)
                { $where[] = 'contacto_id = ?';    $params[] = (int)$_GET['contacto_id']; }
            elseif (isset($_GET['lead_id'])     && (int)$_GET['lead_id'] > 0)
                { $where[] = 'lead_id = ?';        $params[] = (int)$_GET['lead_id']; }
            elseif (isset($_GET['oportunidad_id']) && (int)$_GET['oportunidad_id'] > 0)
                { $where[] = 'oportunidad_id = ?'; $params[] = (int)$_GET['oportunidad_id']; }

            if (!$where) { err('Se requiere contacto_id, lead_id u oportunidad_id'); break; }

            $limite = min((int)($_GET['limite'] ?? 50), 200);
            $sql = "SELECT * FROM actividades WHERE " . implode(' AND ', $where)
                 . " ORDER BY COALESCE(fecha, created_at) DESC LIMIT ?";
            $params[] = $limite;
            $s = $pdo->prepare($sql);
            $s->execute($params);
            ok($s->fetchAll());
            break;

        // ─── Crear ───────────────────────────────────────────────────────────
        case 'POST':
            $v = validarActividad(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            if (($v['contacto_id'] && !recursoPerteneceAlGrupo($pdo, 'contactos', 'id_contacto', $v['contacto_id'], $grupoId)) ||
                ($v['lead_id'] && !recursoPerteneceAlGrupo($pdo, 'leads', 'id_lead', $v['lead_id'], $grupoId)) ||
                ($v['oportunidad_id'] && !recursoPerteneceAlGrupo($pdo, 'oportunidades', 'id_oportunidad', $v['oportunidad_id'], $grupoId))) {
                err('El recurso relacionado no pertenece a tu grupo.', 403); break;
            }

            $s = $pdo->prepare("
                INSERT INTO actividades
                    (tipo, descripcion, fecha, recordatorio_at, contacto_id, lead_id, oportunidad_id, creado_por, id_grupo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([
                $v['tipo'], $v['descripcion'], $v['fecha'], $v['recordatorio_at'],
                $v['contacto_id'], $v['lead_id'], $v['oportunidad_id'], $userId, $grupoId
            ]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT * FROM actividades WHERE id_actividad = ? AND id_grupo = ?");
            $s2->execute([$newId, $grupoId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'actividades', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Actualizar ──────────────────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }

            $sAntes = $pdo->prepare("SELECT * FROM actividades WHERE id_actividad = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Actividad no encontrada', 404); break; }

            $v = validarActividad(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            if (($v['contacto_id'] && !recursoPerteneceAlGrupo($pdo, 'contactos', 'id_contacto', $v['contacto_id'], $grupoId)) ||
                ($v['lead_id'] && !recursoPerteneceAlGrupo($pdo, 'leads', 'id_lead', $v['lead_id'], $grupoId)) ||
                ($v['oportunidad_id'] && !recursoPerteneceAlGrupo($pdo, 'oportunidades', 'id_oportunidad', $v['oportunidad_id'], $grupoId))) {
                err('El recurso relacionado no pertenece a tu grupo.', 403); break;
            }

            $pdo->prepare("
                UPDATE actividades
                SET tipo=?, descripcion=?, fecha=?, recordatorio_at=?, recordatorio_descartado=0,
                    contacto_id=?, lead_id=?, oportunidad_id=?
                WHERE id_actividad=? AND id_grupo=?
            ")->execute([
                $v['tipo'], $v['descripcion'], $v['fecha'], $v['recordatorio_at'],
                $v['contacto_id'], $v['lead_id'], $v['oportunidad_id'], $id, $grupoId
            ]);

            $s2 = $pdo->prepare("SELECT * FROM actividades WHERE id_actividad = ? AND id_grupo = ?");
            $s2->execute([$id, $grupoId]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'actividades', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM actividades WHERE id_actividad = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Actividad no encontrada', 404); break; }

            $pdo->prepare("DELETE FROM actividades WHERE id_actividad = ? AND id_grupo = ?")->execute([$id, $grupoId]);
            registrarAuditoria($pdo, 'actividades', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
