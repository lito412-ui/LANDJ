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
require __DIR__ . '/../config/modulos_visibilidad.php';
verificarModuloVisible($pdo, 'domains');

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

function ok($data, ?array $meta = null): void {
    $r = ['ok' => true, 'data' => $data];
    if ($meta !== null) $r['meta'] = $meta;
    echo json_encode($r);
}
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function clean(string $v): string { return trim($v); }
function nullOrStr(string $v): ?string { $v = clean($v); return $v !== '' ? $v : null; }

$TIPOS   = ['principal', 'subdominio', 'addon', 'parked'];
$ESTADOS = ['activo', 'pendiente', 'suspendido'];
$ORDEN_WHITELIST = ['dominio', 'tipo', 'estado', 'created_at'];

function validarDominio(string $v): ?string {
    if ($v === '') return 'El dominio es obligatorio';
    if (strlen($v) > 255) return 'El dominio no puede superar 255 caracteres';
    if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $v))
        return 'Formato de dominio no válido (ej: ejemplo.com)';
    return null;
}

// ─── GET — Listar ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $buscar = clean($_GET['buscar'] ?? '');
    $tipo   = in_array($_GET['tipo']   ?? '', array_merge([''], $TIPOS))   ? ($_GET['tipo']   ?? '') : '';
    $estado = in_array($_GET['estado'] ?? '', array_merge([''], $ESTADOS)) ? ($_GET['estado'] ?? '') : '';
    $orden  = in_array($_GET['orden']  ?? '', $ORDEN_WHITELIST) ? $_GET['orden'] : 'created_at';
    $dir    = strtoupper($_GET['dir']  ?? '') === 'ASC' ? 'ASC' : 'DESC';
    $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
    $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
    $offset = ($pagina - 1) * $limite;

    $where = ['1=1'];
    $params = [];

    if ($buscar !== '') {
        $where[]  = '(d.dominio LIKE ? OR d.ip LIKE ?)';
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
    }
    if ($tipo !== '')   { $where[] = 'd.tipo = ?';   $params[] = $tipo; }
    if ($estado !== '') { $where[] = 'd.estado = ?'; $params[] = $estado; }

    $clausula = implode(' AND ', $where);

    $stCount = $pdo->prepare("SELECT COUNT(*) FROM dominios d WHERE $clausula");
    $stCount->execute($params);
    $total = (int) $stCount->fetchColumn();

    $st = $pdo->prepare(
        "SELECT d.*, u.nombre AS creado_por_nombre
         FROM dominios d
         LEFT JOIN usuarios u ON u.id_usuario = d.creado_por
         WHERE $clausula
         ORDER BY d.$orden $dir
         LIMIT ? OFFSET ?"
    );
    $st->execute(array_merge($params, [$limite, $offset]));
    $rows = $st->fetchAll();

    $paginas = max((int) ceil($total / $limite), 1);
    ok($rows, compact('total', 'pagina', 'limite', 'paginas'));
    exit;
}

// ─── POST — Crear ─────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $b = body();

    $dominio = clean($b['dominio'] ?? '');
    $e = validarDominio($dominio);
    if ($e) { err($e); exit; }

    $tipo   = in_array($b['tipo']   ?? '', $TIPOS)   ? $b['tipo']   : 'principal';
    $estado = in_array($b['estado'] ?? '', $ESTADOS) ? $b['estado'] : 'pendiente';
    $ip     = nullOrStr($b['ip']    ?? '');
    $ssl    = !empty($b['ssl']) ? 1 : 0;
    $notas  = nullOrStr($b['notas'] ?? '');

    if ($ip !== null && !filter_var($ip, FILTER_VALIDATE_IP)) {
        err('Formato de IP no válido'); exit;
    }
    if ($notas !== null && strlen($notas) > 500) { err('Las notas no pueden superar 500 caracteres'); exit; }

    try {
        $st = $pdo->prepare(
            "INSERT INTO dominios (dominio, tipo, estado, ip, `ssl`, notas, creado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute([$dominio, $tipo, $estado, $ip, $ssl, $notas, $userId]);
        $nuevo = $pdo->query("SELECT d.*, u.nombre AS creado_por_nombre FROM dominios d LEFT JOIN usuarios u ON u.id_usuario = d.creado_por WHERE d.id_dominio = " . $pdo->lastInsertId())->fetch();
        registrarAuditoria($pdo, 'dominios', $nuevo['id_dominio'], 'crear', null, $nuevo);
        ok($nuevo);
    } catch (PDOException $e) {
        error_log('[dominios POST] ' . $e->getMessage());
        if ($e->getCode() === '23000') { err('Ya existe un dominio con ese nombre'); }
        else err('Error al crear el dominio', 500);
    }
    exit;
}

// ─── PUT — Editar ─────────────────────────────────────────────────────────────
if ($method === 'PUT' && $id) {
    $antes = $pdo->prepare("SELECT * FROM dominios WHERE id_dominio = ?");
    $antes->execute([$id]);
    $antes = $antes->fetch();
    if (!$antes) { err('Dominio no encontrado', 404); exit; }

    $b = body();

    $dominio = clean($b['dominio'] ?? '');
    $e = validarDominio($dominio);
    if ($e) { err($e); exit; }

    $tipo   = in_array($b['tipo']   ?? '', $TIPOS)   ? $b['tipo']   : $antes['tipo'];
    $estado = in_array($b['estado'] ?? '', $ESTADOS) ? $b['estado'] : $antes['estado'];
    $ip     = nullOrStr($b['ip']    ?? '');
    $ssl    = !empty($b['ssl']) ? 1 : 0;
    $notas  = nullOrStr($b['notas'] ?? '');

    if ($ip !== null && !filter_var($ip, FILTER_VALIDATE_IP)) {
        err('Formato de IP no válido'); exit;
    }
    if ($notas !== null && strlen($notas) > 500) { err('Las notas no pueden superar 500 caracteres'); exit; }

    try {
        $pdo->prepare(
            "UPDATE dominios SET dominio=?, tipo=?, estado=?, ip=?, `ssl`=?, notas=? WHERE id_dominio=?"
        )->execute([$dominio, $tipo, $estado, $ip, $ssl, $notas, $id]);
        $despues = $pdo->query("SELECT d.*, u.nombre AS creado_por_nombre FROM dominios d LEFT JOIN usuarios u ON u.id_usuario = d.creado_por WHERE d.id_dominio = $id")->fetch();
        registrarAuditoria($pdo, 'dominios', $id, 'editar', $antes, $despues);
        ok($despues);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') { err('Ya existe un dominio con ese nombre'); }
        else err('Error al actualizar el dominio', 500);
    }
    exit;
}

// ─── DELETE — Eliminar ────────────────────────────────────────────────────────
if ($method === 'DELETE' && $id) {
    $row = $pdo->prepare("SELECT * FROM dominios WHERE id_dominio = ?");
    $row->execute([$id]);
    $row = $row->fetch();
    if (!$row) { err('Dominio no encontrado', 404); exit; }

    $pdo->prepare("DELETE FROM dominios WHERE id_dominio = ?")->execute([$id]);
    registrarAuditoria($pdo, 'dominios', $id, 'eliminar', $row, null);
    ok(null);
    exit;
}

err('Método no permitido', 405);
