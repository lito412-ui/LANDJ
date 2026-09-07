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
verificarModuloVisible($pdo, 'proveedores');
require __DIR__ . '/../config/csv_util.php';

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

function validarNif(string $v): ?string {
    // Acepta NIF/NIE/CIF español en formato flexible: 8-9 caracteres alfanumericos
    if (!preg_match('/^[A-Za-z0-9]{8,9}$/', $v)) {
        return 'El NIF/CIF debe tener entre 8 y 9 caracteres alfanuméricos';
    }
    return null;
}

function validarProveedor(array $b): array {
    $errors = [];

    $nombre = clean($b['nombre'] ?? '');
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';
    elseif (strlen($nombre) > 150) $errors[] = 'El nombre no puede superar 150 caracteres';

    $nif = nullOrStr($b['nif'] ?? '');
    if ($nif !== null) {
        $nif = strtoupper($nif);
        $e = validarNif($nif);
        if ($e) $errors[] = $e;
    }

    $email = nullOrStr($b['email'] ?? '');
    if ($email !== null) {
        if (strlen($email) > 150) $errors[] = 'El email no puede superar 150 caracteres';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'El email no es válido';
    }

    $telefono = nullOrStr($b['telefono'] ?? '');
    if ($telefono !== null && strlen($telefono) > 30) $errors[] = 'El teléfono no puede superar 30 caracteres';

    $direccion = nullOrStr($b['direccion'] ?? '');
    if ($direccion !== null && strlen($direccion) > 255) $errors[] = 'La dirección no puede superar 255 caracteres';

    $contactoReferencia = nullOrStr($b['contacto_referencia'] ?? '');
    if ($contactoReferencia !== null && strlen($contactoReferencia) > 150) $errors[] = 'El contacto de referencia no puede superar 150 caracteres';

    $notas = nullOrStr($b['notas'] ?? '');
    if ($notas !== null && strlen($notas) > 1000) $errors[] = 'Las notas no pueden superar 1000 caracteres';

    $activo = array_key_exists('activo', $b) ? (!empty($b['activo']) ? 1 : 0) : 1;

    return [
        'errors' => $errors,
        'nombre' => $nombre,
        'nif' => $nif,
        'email' => $email,
        'telefono' => $telefono,
        'direccion' => $direccion,
        'contactoReferencia' => $contactoReferencia,
        'notas' => $notas,
        'activo' => $activo,
    ];
}

// ─── Exportar / importar CSV ────────────────────────────────────────────────
function exportarProveedores(PDO $pdo): void {
    $s = $pdo->query("SELECT nombre, nif, email, telefono, direccion, contacto_referencia, notas, activo, created_at FROM proveedores ORDER BY nombre");
    $filas = [];
    foreach ($s->fetchAll() as $p) {
        $filas[] = [$p['nombre'], $p['nif'], $p['email'], $p['telefono'], $p['direccion'],
            $p['contacto_referencia'], $p['notas'], $p['activo'] ? '1' : '0', $p['created_at']];
    }
    csvDescargar('proveedores_' . date('Y-m-d') . '.csv', [
        'nombre', 'nif', 'email', 'telefono', 'direccion', 'contacto_referencia', 'notas', 'activo', 'created_at',
    ], $filas);
}

function importarProveedores(PDO $pdo, int $userId): void {
    try {
        $filas = csvLeerSubida('archivo');
    } catch (RuntimeException $e) {
        err($e->getMessage());
        return;
    }
    if (!$filas) { err('El archivo CSV está vacío o no tiene un formato válido'); return; }

    $creados = 0; $actualizados = 0; $errores = [];
    $sBuscarNif = $pdo->prepare("SELECT id_proveedor FROM proveedores WHERE nif = ? LIMIT 1");
    $sIns = $pdo->prepare("
        INSERT INTO proveedores (nombre, nif, email, telefono, direccion, contacto_referencia, notas, activo, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $sUpd = $pdo->prepare("
        UPDATE proveedores SET nombre=?, email=?, telefono=?, direccion=?, contacto_referencia=?, notas=?, activo=? WHERE id_proveedor=?
    ");

    foreach ($filas as $idx => $fila) {
        $numFila = $idx + 2;
        $fila['activo'] = csvBool($fila['activo'] ?? '', true) ? '1' : '';
        $v = validarProveedor($fila);
        if ($v['errors']) { $errores[] = "Fila $numFila: " . implode('; ', $v['errors']); continue; }

        try {
            $existenteId = null;
            if ($v['nif'] !== null) {
                $sBuscarNif->execute([$v['nif']]);
                $existenteId = $sBuscarNif->fetchColumn() ?: null;
            }
            if ($existenteId) {
                $sUpd->execute([$v['nombre'], $v['email'], $v['telefono'], $v['direccion'], $v['contactoReferencia'], $v['notas'], $v['activo'], $existenteId]);
                registrarAuditoria($pdo, 'proveedores', (int) $existenteId, 'editar', null, $v);
                $actualizados++;
            } else {
                $sIns->execute([$v['nombre'], $v['nif'], $v['email'], $v['telefono'], $v['direccion'], $v['contactoReferencia'], $v['notas'], $v['activo'], $userId]);
                $nuevoId = (int) $pdo->lastInsertId();
                registrarAuditoria($pdo, 'proveedores', $nuevoId, 'crear', null, $v);
                $creados++;
            }
        } catch (PDOException $e) {
            $errores[] = "Fila $numFila: " . ($e->getCode() === '23000' ? 'NIF duplicado' : 'error de base de datos');
        }
    }

    ok(['creados' => $creados, 'actualizados' => $actualizados, 'errores' => $errores, 'total' => count($filas)]);
}

try {
    switch ($method) {

        // ─── Listar / buscar / detalle ────────────────────────────────────
        case 'GET':
            if (($_GET['action'] ?? '') === 'exportar') {
                exportarProveedores($pdo);
                break;
            }
            if ($id) {
                $s = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ? LIMIT 1");
                $s->execute([$id]);
                $row = $s->fetch();
                $row ? ok($row) : err('Proveedor no encontrado', 404);
            } else {
                $where  = [];
                $params = [];

                $buscar = clean($_GET['buscar'] ?? '');
                if ($buscar !== '') {
                    $like    = '%' . $buscar . '%';
                    $where[] = '(nombre LIKE ? OR nif LIKE ? OR email LIKE ? OR contacto_referencia LIKE ?)';
                    array_push($params, $like, $like, $like, $like);
                }

                $activo = clean($_GET['activo'] ?? '');
                if ($activo === '0' || $activo === '1') {
                    $where[]  = 'activo = ?';
                    $params[] = $activo;
                }

                $colsPermitidas = ['nombre', 'nif', 'activo', 'created_at'];
                $orden = in_array($_GET['orden'] ?? '', $colsPermitidas, true) ? $_GET['orden'] : 'nombre';
                $dir   = ($_GET['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

                $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
                $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
                $offset = ($pagina - 1) * $limite;

                $clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

                $stCount = $pdo->prepare("SELECT COUNT(*) FROM proveedores$clausulaWhere");
                $stCount->execute($params);
                $total   = (int) $stCount->fetchColumn();
                $paginas = (int) ceil($total / $limite);

                $sql = "SELECT * FROM proveedores$clausulaWhere ORDER BY $orden $dir LIMIT ? OFFSET ?";
                $s = $pdo->prepare($sql);
                $s->execute([...$params, $limite, $offset]);
                ok($s->fetchAll(), compact('total', 'pagina', 'limite', 'paginas'));
            }
            break;

        // ─── Crear ───────────────────────────────────────────────────────
        case 'POST':
            if (($_GET['action'] ?? '') === 'importar') {
                importarProveedores($pdo, $userId);
                break;
            }
            $v = validarProveedor(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            $s = $pdo->prepare("
                INSERT INTO proveedores (nombre, nif, email, telefono, direccion, contacto_referencia, notas, activo, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([$v['nombre'], $v['nif'], $v['email'], $v['telefono'], $v['direccion'], $v['contactoReferencia'], $v['notas'], $v['activo'], $userId]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
            $s2->execute([$newId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'proveedores', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Actualizar ──────────────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $v = validarProveedor(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            $sAntes = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
            $sAntes->execute([$id]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Proveedor no encontrado', 404); break; }
            $s = $pdo->prepare("
                UPDATE proveedores
                   SET nombre=?, nif=?, email=?, telefono=?, direccion=?, contacto_referencia=?, notas=?, activo=?
                 WHERE id_proveedor=?
            ");
            $s->execute([$v['nombre'], $v['nif'], $v['email'], $v['telefono'], $v['direccion'], $v['contactoReferencia'], $v['notas'], $v['activo'], $id]);
            $s2 = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
            $s2->execute([$id]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'proveedores', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
            $sAntes->execute([$id]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Proveedor no encontrado', 404); break; }
            $s = $pdo->prepare("DELETE FROM proveedores WHERE id_proveedor = ?");
            $s->execute([$id]);
            registrarAuditoria($pdo, 'proveedores', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
