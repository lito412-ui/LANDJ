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

function validarNombre(string $v, string $campo = 'nombre'): ?string {
    if (strlen($v) > 100) return "El $campo no puede superar 100 caracteres";
    if (!preg_match('/^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s\'\-]+$/u', $v))
        return "El $campo solo puede contener letras, espacios, guiones y apóstrofes";
    return null;
}

function validarTelefono(string $v): ?string {
    $digits = preg_replace('/[\s\-]/', '', $v);
    if (!preg_match('/^[6-9]\d{8}$/', $digits))
        return 'Teléfono inválido (ej: 612 345 678)';
    return null;
}

function validarYSanitizar(array $b): array {
    $errors = [];

    $nombre = clean($b['nombre'] ?? '');
    if ($nombre === '') { $errors[] = 'El nombre es obligatorio'; }
    else {
        $e = validarNombre($nombre, 'nombre');
        if ($e) $errors[] = $e;
    }

    $apellidos = nullOrStr($b['apellidos'] ?? '');
    if ($apellidos !== null) {
        $e = validarNombre($apellidos, 'apellidos');
        if ($e) $errors[] = $e;
    }

    $email = nullOrStr($b['email'] ?? '');
    if ($email !== null) {
        if (strlen($email) > 255) $errors[] = 'El email no puede superar 255 caracteres';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El email no es válido';
    }

    $telefono = nullOrStr($b['telefono'] ?? '');
    if ($telefono !== null) {
        if (strlen($telefono) > 30) $errors[] = 'El teléfono no puede superar 30 caracteres';
        else {
            $e = validarTelefono($telefono);
            if ($e) $errors[] = $e;
        }
    }

    $empresa = nullOrStr($b['empresa'] ?? '');
    if ($empresa !== null && strlen($empresa) > 150)
        $errors[] = 'La empresa no puede superar 150 caracteres';

    $notas = nullOrStr($b['notas'] ?? '');
    if ($notas !== null && strlen($notas) > 500)
        $errors[] = 'Las notas no pueden superar 500 caracteres';

    return [
        'errors'    => $errors,
        'nombre'    => $nombre,
        'apellidos' => $apellidos,
        'email'     => $email,
        'telefono'  => $telefono,
        'empresa'   => $empresa,
        'notas'     => $notas,
    ];
}

try {
    switch ($method) {

        // ─── Listar / buscar / detalle ────────────────────────────────────
        case 'GET':
            if ($id) {
                $s = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ? LIMIT 1");
                $s->execute([$id]);
                $row = $s->fetch();
                $row ? ok($row) : err('Contacto no encontrado', 404);
            } else {
                $where  = [];
                $params = [];

                $buscar = clean($_GET['buscar'] ?? '');
                if ($buscar !== '') {
                    $like    = '%' . $buscar . '%';
                    $where[] = '(nombre LIKE ? OR apellidos LIKE ? OR email LIKE ? OR empresa LIKE ?)';
                    array_push($params, $like, $like, $like, $like);
                }

                $empresa = clean($_GET['empresa'] ?? '');
                if ($empresa !== '') {
                    $where[]  = 'empresa LIKE ?';
                    $params[] = '%' . $empresa . '%';
                }

                $desde = clean($_GET['desde'] ?? '');
                if ($desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
                    $where[]  = 'DATE(created_at) >= ?';
                    $params[] = $desde;
                }

                $hasta = clean($_GET['hasta'] ?? '');
                if ($hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
                    $where[]  = 'DATE(created_at) <= ?';
                    $params[] = $hasta;
                }

                $colsPermitidas = ['nombre', 'empresa', 'created_at'];
                $orden = in_array($_GET['orden'] ?? '', $colsPermitidas, true) ? $_GET['orden'] : 'created_at';
                $dir   = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

                $sql = "SELECT * FROM contactos"
                     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                     . " ORDER BY $orden $dir";
                $s = $pdo->prepare($sql);
                $s->execute($params);
                ok($s->fetchAll());
            }
            break;

        // ─── Crear ───────────────────────────────────────────────────────
        case 'POST':
            $v = validarYSanitizar(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            $s = $pdo->prepare(
                "INSERT INTO contactos (nombre, apellidos, email, telefono, empresa, notas, creado_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $s->execute([$v['nombre'], $v['apellidos'], $v['email'], $v['telefono'], $v['empresa'], $v['notas'], $userId]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ?");
            $s2->execute([$newId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'contactos', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Actualizar ──────────────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $v = validarYSanitizar(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            $sAntes = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ?");
            $sAntes->execute([$id]);
            $antes = $sAntes->fetch() ?: null;
            $s = $pdo->prepare(
                "UPDATE contactos
                 SET nombre=?, apellidos=?, email=?, telefono=?, empresa=?, notas=?
                 WHERE id_contacto=?"
            );
            $s->execute([$v['nombre'], $v['apellidos'], $v['email'], $v['telefono'], $v['empresa'], $v['notas'], $id]);
            $s2 = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ?");
            $s2->execute([$id]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'contactos', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ?");
            $sAntes->execute([$id]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Contacto no encontrado', 404); break; }
            $s = $pdo->prepare("DELETE FROM contactos WHERE id_contacto = ?");
            $s->execute([$id]);
            registrarAuditoria($pdo, 'contactos', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
