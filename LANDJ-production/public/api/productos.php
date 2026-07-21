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
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

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
function decimalInput($v): float { return (float) str_replace(',', '.', (string) $v); }

function validarProducto(array $b): array {
    $errors = [];

    $codigo = nullOrStr((string) ($b['codigo'] ?? ''));
    if ($codigo !== null) {
        if (strlen($codigo) > 40) $errors[] = 'El codigo no puede superar 40 caracteres';
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $codigo)) $errors[] = 'El codigo solo puede usar letras, numeros, puntos, guiones y barra baja';
    }

    $nombre = clean((string) ($b['nombre'] ?? ''));
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';
    if (strlen($nombre) > 150) $errors[] = 'El nombre no puede superar 150 caracteres';

    $descripcion = nullOrStr((string) ($b['descripcion'] ?? ''));
    if ($descripcion !== null && strlen($descripcion) > 1000) $errors[] = 'La descripcion no puede superar 1000 caracteres';

    $precio = decimalInput($b['precio'] ?? 0);
    $iva = decimalInput($b['iva_porcentaje'] ?? 21);
    $stock = decimalInput($b['stock'] ?? 0);
    $activo = !empty($b['activo']) ? 1 : 0;

    if ($precio < 0) $errors[] = 'El precio no puede ser negativo';
    if ($iva < 0 || $iva > 100) $errors[] = 'El IVA debe estar entre 0 y 100';
    if ($stock < 0) $errors[] = 'El stock no puede ser negativo';

    return compact('errors', 'codigo', 'nombre', 'descripcion', 'precio', 'iva', 'stock', 'activo');
}

function cargarProducto(PDO $pdo, int $id): ?array {
    $s = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ? LIMIT 1");
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                $producto = cargarProducto($pdo, $id);
                $producto ? ok($producto) : err('Producto no encontrado', 404);
                break;
            }

            $where = [];
            $params = [];

            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $where[] = '(codigo LIKE ? OR nombre LIKE ? OR descripcion LIKE ?)';
                array_push($params, $like, $like, $like);
            }

            $activo = $_GET['activo'] ?? '';
            if ($activo !== '' && in_array((string) $activo, ['0', '1'], true)) {
                $where[] = 'activo = ?';
                $params[] = (int) $activo;
            }

            $cols = ['codigo', 'nombre', 'precio', 'stock', 'activo', 'created_at'];
            $orden = in_array($_GET['orden'] ?? '', $cols, true) ? $_GET['orden'] : 'created_at';
            $dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
            $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
            $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
            $offset = ($pagina - 1) * $limite;
            $clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $stCount = $pdo->prepare("SELECT COUNT(*) FROM productos$clausulaWhere");
            $stCount->execute($params);
            $total = (int) $stCount->fetchColumn();
            $paginas = (int) ceil($total / $limite);

            $s = $pdo->prepare("SELECT * FROM productos$clausulaWhere ORDER BY $orden $dir, id_producto DESC LIMIT ? OFFSET ?");
            $s->execute([...$params, $limite, $offset]);
            ok($s->fetchAll(), compact('total', 'pagina', 'limite', 'paginas'));
            break;

        case 'POST':
            $v = validarProducto(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $s = $pdo->prepare("
                INSERT INTO productos (codigo, nombre, descripcion, precio, iva_porcentaje, stock, activo, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([$v['codigo'], $v['nombre'], $v['descripcion'], $v['precio'], $v['iva'], $v['stock'], $v['activo'], $userId]);
            $newId = (int) $pdo->lastInsertId();
            $nuevo = cargarProducto($pdo, $newId);
            registrarAuditoria($pdo, 'productos', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $antes = cargarProducto($pdo, $id);
            if (!$antes) { err('Producto no encontrado', 404); break; }

            $v = validarProducto(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $s = $pdo->prepare("
                UPDATE productos
                   SET codigo=?, nombre=?, descripcion=?, precio=?, iva_porcentaje=?, stock=?, activo=?
                 WHERE id_producto=?
            ");
            $s->execute([$v['codigo'], $v['nombre'], $v['descripcion'], $v['precio'], $v['iva'], $v['stock'], $v['activo'], $id]);
            $despues = cargarProducto($pdo, $id);
            registrarAuditoria($pdo, 'productos', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $antes = cargarProducto($pdo, $id);
            if (!$antes) { err('Producto no encontrado', 404); break; }
            $pdo->prepare("DELETE FROM productos WHERE id_producto = ?")->execute([$id]);
            registrarAuditoria($pdo, 'productos', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Metodo no permitido', 405);
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        err('Ya existe un producto con ese codigo');
        exit;
    }
    error_log('[productos] ' . $e->getMessage());
    err('Error de base de datos', 500);
}
