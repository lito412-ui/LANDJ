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
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

function ok($data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function clean(string $v): string { return trim($v); }
function nullOrStr(string $v): ?string { $v = clean($v); return $v !== '' ? $v : null; }

const ETAPAS_VALIDAS = ['prospecto', 'propuesta', 'negociacion', 'cerrada_ganada', 'cerrada_perdida'];

function validarOportunidad(array $b): array {
    $errors = [];

    $titulo = clean($b['titulo'] ?? '');
    if ($titulo === '')          $errors[] = 'El título es obligatorio';
    elseif (strlen($titulo) > 150) $errors[] = 'El título no puede superar 150 caracteres';

    $descripcion = nullOrStr($b['descripcion'] ?? '');
    if ($descripcion !== null && strlen($descripcion) > 500)
        $errors[] = 'La descripción no puede superar 500 caracteres';

    $valorRaw = trim($b['valor'] ?? '');
    $valor = null;
    if ($valorRaw !== '') {
        if (!is_numeric($valorRaw) || (float)$valorRaw < 0)
            $errors[] = 'El valor debe ser un número positivo';
        else
            $valor = (float)$valorRaw;
    }

    $etapa = clean($b['etapa'] ?? 'prospecto');
    if (!in_array($etapa, ETAPAS_VALIDAS, true))
        $errors[] = 'Etapa no válida';

    $fechaCierre = nullOrStr($b['fecha_cierre_esperada'] ?? '');
    if ($fechaCierre !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCierre))
        $errors[] = 'Formato de fecha inválido (YYYY-MM-DD)';

    $contactoId = isset($b['contacto_id']) && $b['contacto_id'] !== '' ? (int)$b['contacto_id'] : null;
    $leadId     = isset($b['lead_id'])     && $b['lead_id']     !== '' ? (int)$b['lead_id']     : null;

    return [
        'errors'               => $errors,
        'titulo'               => $titulo,
        'descripcion'          => $descripcion,
        'valor'                => $valor,
        'etapa'                => $etapa,
        'fecha_cierre_esperada'=> $fechaCierre,
        'contacto_id'          => $contactoId,
        'lead_id'              => $leadId,
    ];
}

try {
    switch ($method) {

        // ─── Listar / detalle ─────────────────────────────────────────────────
        case 'GET':
            if ($id) {
                $s = $pdo->prepare("
                    SELECT o.*,
                           c.nombre AS contacto_nombre,
                           l.nombre AS lead_nombre
                    FROM oportunidades o
                    LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                    LEFT JOIN leads     l ON l.id_lead     = o.lead_id
                    WHERE o.id_oportunidad = ?
                    LIMIT 1
                ");
                $s->execute([$id]);
                $row = $s->fetch();
                $row ? ok($row) : err('Oportunidad no encontrada', 404);
                break;
            }

            $where  = [];
            $params = [];

            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like    = '%' . $buscar . '%';
                $where[] = '(o.titulo LIKE ? OR o.descripcion LIKE ? OR c.nombre LIKE ? OR l.nombre LIKE ?)';
                array_push($params, $like, $like, $like, $like);
            }

            $etapa = clean($_GET['etapa'] ?? '');
            if ($etapa !== '' && in_array($etapa, ETAPAS_VALIDAS, true)) {
                $where[]  = 'o.etapa = ?';
                $params[] = $etapa;
            }

            $valorMin = trim($_GET['valor_min'] ?? '');
            if ($valorMin !== '' && is_numeric($valorMin) && (float)$valorMin >= 0) {
                $where[]  = 'o.valor >= ?';
                $params[] = (float)$valorMin;
            }

            $valorMax = trim($_GET['valor_max'] ?? '');
            if ($valorMax !== '' && is_numeric($valorMax) && (float)$valorMax >= 0) {
                $where[]  = 'o.valor <= ?';
                $params[] = (float)$valorMax;
            }

            $cierreDe = clean($_GET['cierre_desde'] ?? '');
            if ($cierreDe !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $cierreDe)) {
                $where[]  = 'o.fecha_cierre_esperada >= ?';
                $params[] = $cierreDe;
            }

            $cierreHa = clean($_GET['cierre_hasta'] ?? '');
            if ($cierreHa !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $cierreHa)) {
                $where[]  = 'o.fecha_cierre_esperada <= ?';
                $params[] = $cierreHa;
            }

            $colsPermitidas = ['titulo', 'valor', 'etapa', 'fecha_cierre_esperada', 'created_at'];
            $ordenRaw = $_GET['orden'] ?? '';
            $orden = in_array($ordenRaw, $colsPermitidas, true) ? $ordenRaw : 'created_at';
            $dir   = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

            $sql = "SELECT o.*,
                           c.nombre AS contacto_nombre,
                           l.nombre AS lead_nombre
                    FROM oportunidades o
                    LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                    LEFT JOIN leads     l ON l.id_lead     = o.lead_id"
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . " ORDER BY o.$orden $dir";
            $s = $pdo->prepare($sql);
            $s->execute($params);
            ok($s->fetchAll());
            break;

        // ─── Crear ───────────────────────────────────────────────────────────
        case 'POST':
            $v = validarOportunidad(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $s = $pdo->prepare("
                INSERT INTO oportunidades
                    (titulo, descripcion, valor, etapa, contacto_id, lead_id, fecha_cierre_esperada, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([
                $v['titulo'], $v['descripcion'], $v['valor'], $v['etapa'],
                $v['contacto_id'], $v['lead_id'], $v['fecha_cierre_esperada'], $userId
            ]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT o.*, c.nombre AS contacto_nombre, l.nombre AS lead_nombre
                FROM oportunidades o
                LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                LEFT JOIN leads l ON l.id_lead = o.lead_id
                WHERE o.id_oportunidad = ?");
            $s2->execute([$newId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'oportunidades', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Actualizar / mover etapa ─────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }

            // Mover etapa
            if (isset($_GET['action']) && $_GET['action'] === 'etapa') {
                $b = body();
                $nuevaEtapa = clean($b['etapa'] ?? '');
                if (!in_array($nuevaEtapa, ETAPAS_VALIDAS, true)) {
                    err('Etapa no válida'); break;
                }
                $sAntes = $pdo->prepare("SELECT * FROM oportunidades WHERE id_oportunidad = ?");
                $sAntes->execute([$id]);
                $antes = $sAntes->fetch() ?: null;
                if (!$antes) { err('Oportunidad no encontrada', 404); break; }

                $pdo->prepare("UPDATE oportunidades SET etapa = ? WHERE id_oportunidad = ?")
                    ->execute([$nuevaEtapa, $id]);

                $s2 = $pdo->prepare("SELECT o.*, c.nombre AS contacto_nombre, l.nombre AS lead_nombre
                    FROM oportunidades o
                    LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                    LEFT JOIN leads l ON l.id_lead = o.lead_id
                    WHERE o.id_oportunidad = ?");
                $s2->execute([$id]);
                $despues = $s2->fetch() ?: null;
                registrarAuditoria($pdo, 'oportunidades', $id, 'editar', $antes, $despues);
                ok($despues);
                break;
            }

            // Edición completa
            $v = validarOportunidad(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $sAntes = $pdo->prepare("SELECT * FROM oportunidades WHERE id_oportunidad = ?");
            $sAntes->execute([$id]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Oportunidad no encontrada', 404); break; }

            $pdo->prepare("
                UPDATE oportunidades
                SET titulo=?, descripcion=?, valor=?, etapa=?,
                    contacto_id=?, lead_id=?, fecha_cierre_esperada=?
                WHERE id_oportunidad=?
            ")->execute([
                $v['titulo'], $v['descripcion'], $v['valor'], $v['etapa'],
                $v['contacto_id'], $v['lead_id'], $v['fecha_cierre_esperada'], $id
            ]);

            $s2 = $pdo->prepare("SELECT o.*, c.nombre AS contacto_nombre, l.nombre AS lead_nombre
                FROM oportunidades o
                LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                LEFT JOIN leads l ON l.id_lead = o.lead_id
                WHERE o.id_oportunidad = ?");
            $s2->execute([$id]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'oportunidades', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM oportunidades WHERE id_oportunidad = ?");
            $sAntes->execute([$id]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Oportunidad no encontrada', 404); break; }

            $pdo->prepare("DELETE FROM oportunidades WHERE id_oportunidad = ?")->execute([$id]);
            registrarAuditoria($pdo, 'oportunidades', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
