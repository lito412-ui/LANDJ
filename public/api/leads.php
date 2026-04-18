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

const ESTADOS_VALIDOS = ['nuevo', 'contactado', 'calificado', 'convertido', 'descartado'];

function validarLead(array $b): array {
    $errors = [];

    $nombre = clean($b['nombre'] ?? '');
    if ($nombre === '') {
        $errors[] = 'El nombre es obligatorio';
    } elseif (strlen($nombre) > 100) {
        $errors[] = 'El nombre no puede superar 100 caracteres';
    } elseif (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\'\-]+$/u', $nombre)) {
        $errors[] = 'El nombre solo puede contener letras, espacios, guiones y apóstrofes';
    }

    $email = nullOrStr($b['email'] ?? '');
    if ($email !== null) {
        if (strlen($email) > 255) $errors[] = 'El email no puede superar 255 caracteres';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El email no es válido';
    }

    $telefono = nullOrStr($b['telefono'] ?? '');
    if ($telefono !== null) {
        $digits = preg_replace('/[\s\-]/', '', $telefono);
        if (!preg_match('/^[6-9]\d{8}$/', $digits))
            $errors[] = 'Teléfono español inválido (ej: 612 345 678)';
    }

    $empresa = nullOrStr($b['empresa'] ?? '');
    if ($empresa !== null && strlen($empresa) > 150)
        $errors[] = 'La empresa no puede superar 150 caracteres';

    $origen = nullOrStr($b['origen'] ?? '');
    if ($origen !== null && strlen($origen) > 50)
        $errors[] = 'El origen no puede superar 50 caracteres';

    $estado = clean($b['estado'] ?? 'nuevo');
    if (!in_array($estado, ESTADOS_VALIDOS, true))
        $errors[] = 'Estado no válido';

    $notas = nullOrStr($b['notas'] ?? '');
    if ($notas !== null && strlen($notas) > 500)
        $errors[] = 'Las notas no pueden superar 500 caracteres';

    return [
        'errors'   => $errors,
        'nombre'   => $nombre,
        'email'    => $email,
        'telefono' => $telefono,
        'empresa'  => $empresa,
        'origen'   => $origen,
        'estado'   => $estado,
        'notas'    => $notas,
    ];
}

try {
    switch ($method) {

        // ─── Listar / buscar / detalle ────────────────────────────────────
        case 'GET':
            if ($id) {
                $s = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ? LIMIT 1");
                $s->execute([$id]);
                $row = $s->fetch();
                $row ? ok($row) : err('Lead no encontrado', 404);
                break;
            }

            $where  = [];
            $params = [];

            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like     = '%' . $buscar . '%';
                $where[]  = '(nombre LIKE ? OR email LIKE ? OR empresa LIKE ?)';
                array_push($params, $like, $like, $like);
            }

            $estado = clean($_GET['estado'] ?? '');
            if ($estado !== '' && in_array($estado, ESTADOS_VALIDOS, true)) {
                $where[]  = 'estado = ?';
                $params[] = $estado;
            }

            $sql = "SELECT * FROM leads"
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . " ORDER BY created_at DESC";
            $s = $pdo->prepare($sql);
            $s->execute($params);
            ok($s->fetchAll());
            break;

        // ─── Crear ───────────────────────────────────────────────────────
        case 'POST':
            $v = validarLead(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $s = $pdo->prepare(
                "INSERT INTO leads (nombre, email, telefono, empresa, origen, estado, notas, creado_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $s->execute([$v['nombre'], $v['email'], $v['telefono'], $v['empresa'],
                         $v['origen'], $v['estado'], $v['notas'], $userId]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ?");
            $s2->execute([$newId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'leads', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Actualizar ──────────────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $v = validarLead(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $sAntes = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ?");
            $sAntes->execute([$id]);
            $antes = $sAntes->fetch() ?: null;

            $s = $pdo->prepare(
                "UPDATE leads
                 SET nombre=?, email=?, telefono=?, empresa=?, origen=?, estado=?, notas=?
                 WHERE id_lead=?"
            );
            $s->execute([$v['nombre'], $v['email'], $v['telefono'], $v['empresa'],
                         $v['origen'], $v['estado'], $v['notas'], $id]);
            $s2 = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ?");
            $s2->execute([$id]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'leads', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ?");
            $sAntes->execute([$id]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Lead no encontrado', 404); break; }

            $s = $pdo->prepare("DELETE FROM leads WHERE id_lead = ?");
            $s->execute([$id]);
            registrarAuditoria($pdo, 'leads', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
