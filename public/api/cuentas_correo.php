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
verificarModuloVisible($pdo, 'email');

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

$ESTADOS = ['activo', 'suspendido'];
$ORDEN_WHITELIST = ['email', 'dominio', 'cuota', 'estado', 'created_at'];
$EMAIL_RE = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';

function validarEmail(string $v): ?string {
    global $EMAIL_RE;
    if ($v === '') return 'El email es obligatorio';
    if (strlen($v) > 255) return 'El email no puede superar 255 caracteres';
    if (!preg_match($EMAIL_RE, $v)) return 'Formato de email no válido (ej: usuario@dominio.com)';
    return null;
}

function extraerDominio(string $email): string {
    return strtolower(substr($email, strpos($email, '@') + 1));
}

// ─── GET — Listar ─────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $buscar = clean($_GET['buscar'] ?? '');
    $estado = in_array($_GET['estado'] ?? '', array_merge([''], $ESTADOS)) ? ($_GET['estado'] ?? '') : '';
    $orden  = in_array($_GET['orden'] ?? '', $ORDEN_WHITELIST) ? $_GET['orden'] : 'created_at';
    $dir    = strtoupper($_GET['dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';
    $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
    $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
    $offset = ($pagina - 1) * $limite;

    $where  = ['1=1'];
    $params = [];

    if ($buscar !== '') {
        $where[]  = '(c.email LIKE ? OR c.dominio LIKE ?)';
        $params[] = "%$buscar%";
        $params[] = "%$buscar%";
    }
    if ($estado !== '') { $where[] = 'c.estado = ?'; $params[] = $estado; }

    $clausula = implode(' AND ', $where);

    $stCount = $pdo->prepare("SELECT COUNT(*) FROM cuentas_correo c WHERE $clausula");
    $stCount->execute($params);
    $total = (int) $stCount->fetchColumn();

    $st = $pdo->prepare(
        "SELECT c.*, u.nombre AS creado_por_nombre
         FROM cuentas_correo c
         LEFT JOIN usuarios u ON u.id_usuario = c.creado_por
         WHERE $clausula
         ORDER BY c.$orden $dir
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

    $email = strtolower(clean($b['email'] ?? ''));
    $e = validarEmail($email);
    if ($e) { err($e); exit; }

    $dominio = extraerDominio($email);
    $estado  = in_array($b['estado'] ?? '', $ESTADOS) ? $b['estado'] : 'activo';
    $cuota   = max((int) ($b['cuota'] ?? 500), 0);
    $notas   = nullOrStr($b['notas'] ?? '');

    if ($notas !== null && strlen($notas) > 500) { err('Las notas no pueden superar 500 caracteres'); exit; }

    try {
        $st = $pdo->prepare(
            "INSERT INTO cuentas_correo (email, dominio, cuota, estado, notas, creado_por)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $st->execute([$email, $dominio, $cuota, $estado, $notas, $userId]);
        $newId = (int) $pdo->lastInsertId();
        $nuevo = $pdo->prepare(
            "SELECT c.*, u.nombre AS creado_por_nombre
             FROM cuentas_correo c LEFT JOIN usuarios u ON u.id_usuario = c.creado_por
             WHERE c.id_cuenta = ?"
        );
        $nuevo->execute([$newId]);
        $nuevo = $nuevo->fetch();
        registrarAuditoria($pdo, 'cuentas_correo', $newId, 'crear', null, $nuevo);
        ok($nuevo);
    } catch (PDOException $ex) {
        error_log('[cuentas_correo POST] ' . $ex->getMessage());
        if ($ex->getCode() === '23000') { err('Ya existe una cuenta con ese email'); }
        else err('Error al crear la cuenta', 500);
    }
    exit;
}

// ─── PUT — Editar ─────────────────────────────────────────────────────────────
if ($method === 'PUT' && $id) {
    $antesQ = $pdo->prepare("SELECT * FROM cuentas_correo WHERE id_cuenta = ?");
    $antesQ->execute([$id]);
    $antes = $antesQ->fetch();
    if (!$antes) { err('Cuenta no encontrada', 404); exit; }

    $b = body();

    $email = strtolower(clean($b['email'] ?? ''));
    $e = validarEmail($email);
    if ($e) { err($e); exit; }

    $dominio = extraerDominio($email);
    $estado  = in_array($b['estado'] ?? '', $ESTADOS) ? $b['estado'] : $antes['estado'];
    $cuota   = max((int) ($b['cuota'] ?? $antes['cuota']), 0);
    $notas   = nullOrStr($b['notas'] ?? '');

    if ($notas !== null && strlen($notas) > 500) { err('Las notas no pueden superar 500 caracteres'); exit; }

    try {
        $pdo->prepare(
            "UPDATE cuentas_correo SET email=?, dominio=?, cuota=?, estado=?, notas=? WHERE id_cuenta=?"
        )->execute([$email, $dominio, $cuota, $estado, $notas, $id]);

        $despuesQ = $pdo->prepare(
            "SELECT c.*, u.nombre AS creado_por_nombre
             FROM cuentas_correo c LEFT JOIN usuarios u ON u.id_usuario = c.creado_por
             WHERE c.id_cuenta = ?"
        );
        $despuesQ->execute([$id]);
        $despues = $despuesQ->fetch();
        registrarAuditoria($pdo, 'cuentas_correo', $id, 'editar', $antes, $despues);
        ok($despues);
    } catch (PDOException $ex) {
        error_log('[cuentas_correo PUT] ' . $ex->getMessage());
        if ($ex->getCode() === '23000') { err('Ya existe una cuenta con ese email'); }
        else err('Error al actualizar la cuenta', 500);
    }
    exit;
}

// ─── DELETE — Eliminar ────────────────────────────────────────────────────────
if ($method === 'DELETE' && $id) {
    $rowQ = $pdo->prepare("SELECT * FROM cuentas_correo WHERE id_cuenta = ?");
    $rowQ->execute([$id]);
    $row = $rowQ->fetch();
    if (!$row) { err('Cuenta no encontrada', 404); exit; }

    $pdo->prepare("DELETE FROM cuentas_correo WHERE id_cuenta = ?")->execute([$id]);
    registrarAuditoria($pdo, 'cuentas_correo', $id, 'eliminar', $row, null);
    ok(null);
    exit;
}

err('Método no permitido', 405);
