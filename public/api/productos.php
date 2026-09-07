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
verificarModuloVisible($pdo, 'productos');
require __DIR__ . '/../config/csv_util.php';

$userId = (int) $_SESSION['user_id'];
$grupoId = obtenerIdGrupoActual();
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
    $proveedorId = !empty($b['proveedor_id']) ? (int) $b['proveedor_id'] : null;

    if ($precio < 0) $errors[] = 'El precio no puede ser negativo';
    if ($iva < 0 || $iva > 100) $errors[] = 'El IVA debe estar entre 0 y 100';
    if ($stock < 0) $errors[] = 'El stock no puede ser negativo';

    return compact('errors', 'codigo', 'nombre', 'descripcion', 'precio', 'iva', 'stock', 'activo', 'proveedorId');
}

function cargarProducto(PDO $pdo, int $id, int $grupoId): ?array {
    $s = $pdo->prepare("
        SELECT p.*, pr.nombre AS proveedor_nombre
        FROM productos p
        LEFT JOIN proveedores pr ON pr.id_proveedor = p.proveedor_id
        WHERE p.id_producto = ? AND p.id_grupo = ? LIMIT 1
    ");
    $s->execute([$id, $grupoId]);
    return $s->fetch() ?: null;
}

// ─── Exportar / importar CSV ────────────────────────────────────────────────
function exportarProductos(PDO $pdo, int $grupoId): void {
    $s = $pdo->prepare("
        SELECT p.codigo, p.nombre, p.descripcion, p.precio, p.iva_porcentaje, p.stock, p.activo, p.created_at,
               pr.nombre AS proveedor_nombre
        FROM productos p
        LEFT JOIN proveedores pr ON pr.id_proveedor = p.proveedor_id
        WHERE p.id_grupo = ? ORDER BY p.nombre
    ");
    $s->execute([$grupoId]);
    $filas = [];
    foreach ($s->fetchAll() as $p) {
        $filas[] = [$p['codigo'], $p['nombre'], $p['descripcion'], $p['precio'], $p['iva_porcentaje'],
            $p['stock'], $p['activo'] ? '1' : '0', $p['proveedor_nombre'], $p['created_at']];
    }
    csvDescargar('productos_' . date('Y-m-d') . '.csv',
        ['codigo', 'nombre', 'descripcion', 'precio', 'iva_porcentaje', 'stock', 'activo', 'proveedor_nombre', 'created_at'], $filas);
}

function importarProductos(PDO $pdo, int $userId): void {
    try {
        $filas = csvLeerSubida('archivo');
    } catch (RuntimeException $e) {
        err($e->getMessage());
        return;
    }
    if (!$filas) { err('El archivo CSV está vacío o no tiene un formato válido'); return; }

    $creados = 0; $actualizados = 0; $errores = [];
    $sBuscarCodigo = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = ? LIMIT 1");
    $sBuscarProveedor = $pdo->prepare("SELECT id_proveedor FROM proveedores WHERE nombre = ? LIMIT 1");
    $sIns = $pdo->prepare("
        INSERT INTO productos (codigo, nombre, descripcion, precio, iva_porcentaje, stock, activo, proveedor_id, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $sUpd = $pdo->prepare("
        UPDATE productos SET nombre=?, descripcion=?, precio=?, iva_porcentaje=?, stock=?, activo=?, proveedor_id=? WHERE id_producto=?
    ");

    foreach ($filas as $idx => $fila) {
        $numFila = $idx + 2;
        // Normaliza el valor "activo" del CSV (si/no, true/false, 1/0...) antes de validar
        $fila['activo'] = csvBool($fila['activo'] ?? '', true) ? '1' : '';

        $proveedorNombre = trim($fila['proveedor_nombre'] ?? '');
        $proveedorId = null;
        if ($proveedorNombre !== '') {
            $sBuscarProveedor->execute([$proveedorNombre]);
            $proveedorId = $sBuscarProveedor->fetchColumn() ?: null;
        }
        $fila['proveedor_id'] = $proveedorId;

        $v = validarProducto($fila);
        if ($v['errors']) { $errores[] = "Fila $numFila: " . implode('; ', $v['errors']); continue; }

        try {
            $existenteId = null;
            if ($v['codigo'] !== null) {
                $sBuscarCodigo->execute([$v['codigo']]);
                $existenteId = $sBuscarCodigo->fetchColumn() ?: null;
            }
            if ($existenteId) {
                $sUpd->execute([$v['nombre'], $v['descripcion'], $v['precio'], $v['iva'], $v['stock'], $v['activo'], $v['proveedorId'], $existenteId]);
                registrarAuditoria($pdo, 'productos', (int) $existenteId, 'editar', null, $v);
                $actualizados++;
            } else {
                $sIns->execute([$v['codigo'], $v['nombre'], $v['descripcion'], $v['precio'], $v['iva'], $v['stock'], $v['activo'], $v['proveedorId'], $userId]);
                $nuevoId = (int) $pdo->lastInsertId();
                registrarAuditoria($pdo, 'productos', $nuevoId, 'crear', null, $v);
                $creados++;
            }
        } catch (PDOException $e) {
            $errores[] = "Fila $numFila: " . ($e->getCode() === '23000' ? 'código duplicado' : 'error de base de datos');
        }
    }

    ok(['creados' => $creados, 'actualizados' => $actualizados, 'errores' => $errores, 'total' => count($filas)]);
}

try {
    switch ($method) {
        case 'GET':
            if (($_GET['action'] ?? '') === 'exportar') {
                exportarProductos($pdo, $grupoId);
                break;
            }
            if ($id) {
                $producto = cargarProducto($pdo, $id, $grupoId);
                $producto ? ok($producto) : err('Producto no encontrado', 404);
                break;
            }

            $where = ['p.id_grupo = ?'];
            $params = [$grupoId];

            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $where[] = '(p.codigo LIKE ? OR p.nombre LIKE ? OR p.descripcion LIKE ?)';
                array_push($params, $like, $like, $like);
            }

            $activo = $_GET['activo'] ?? '';
            if ($activo !== '' && in_array((string) $activo, ['0', '1'], true)) {
                $where[] = 'p.activo = ?';
                $params[] = (int) $activo;
            }

            $proveedorFiltro = (int) ($_GET['proveedor_id'] ?? 0);
            if ($proveedorFiltro > 0) {
                $where[] = 'p.proveedor_id = ?';
                $params[] = $proveedorFiltro;
            }

            $cols = ['codigo', 'nombre', 'precio', 'stock', 'activo', 'created_at'];
            $orden = in_array($_GET['orden'] ?? '', $cols, true) ? $_GET['orden'] : 'created_at';
            $dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
            $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
            $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
            $offset = ($pagina - 1) * $limite;
            $clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $stCount = $pdo->prepare("SELECT COUNT(*) FROM productos p$clausulaWhere");
            $stCount->execute($params);
            $total = (int) $stCount->fetchColumn();
            $paginas = (int) ceil($total / $limite);

            $s = $pdo->prepare("
                SELECT p.*, pr.nombre AS proveedor_nombre
                FROM productos p
                LEFT JOIN proveedores pr ON pr.id_proveedor = p.proveedor_id
                $clausulaWhere
                ORDER BY p.$orden $dir, p.id_producto DESC
                LIMIT ? OFFSET ?
            ");
            $s->execute([...$params, $limite, $offset]);
            ok($s->fetchAll(), compact('total', 'pagina', 'limite', 'paginas'));
            break;

        case 'POST':
            if (($_GET['action'] ?? '') === 'importar') {
                importarProductos($pdo, $userId);
                break;
            }
            $v = validarProducto(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $s = $pdo->prepare("
                INSERT INTO productos (codigo, nombre, descripcion, precio, iva_porcentaje, stock, activo, proveedor_id, creado_por, id_grupo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($v['proveedorId'] && !recursoPerteneceAlGrupo($pdo, 'proveedores', 'id_proveedor', $v['proveedorId'], $grupoId)) { err('Proveedor no encontrado', 404); break; }
            $s->execute([$v['codigo'], $v['nombre'], $v['descripcion'], $v['precio'], $v['iva'], $v['stock'], $v['activo'], $v['proveedorId'], $userId, $grupoId]);
            $newId = (int) $pdo->lastInsertId();
            $nuevo = cargarProducto($pdo, $newId, $grupoId);
            registrarAuditoria($pdo, 'productos', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $antes = cargarProducto($pdo, $id, $grupoId);
            if (!$antes) { err('Producto no encontrado', 404); break; }

            $v = validarProducto(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $s = $pdo->prepare("
                UPDATE productos
                   SET codigo=?, nombre=?, descripcion=?, precio=?, iva_porcentaje=?, stock=?, activo=?, proveedor_id=?
                 WHERE id_producto=? AND id_grupo=?
            ");
            if ($v['proveedorId'] && !recursoPerteneceAlGrupo($pdo, 'proveedores', 'id_proveedor', $v['proveedorId'], $grupoId)) { err('Proveedor no encontrado', 404); break; }
            $s->execute([$v['codigo'], $v['nombre'], $v['descripcion'], $v['precio'], $v['iva'], $v['stock'], $v['activo'], $v['proveedorId'], $id, $grupoId]);
            $despues = cargarProducto($pdo, $id, $grupoId);
            registrarAuditoria($pdo, 'productos', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $antes = cargarProducto($pdo, $id, $grupoId);
            if (!$antes) { err('Producto no encontrado', 404); break; }
            $pdo->prepare("DELETE FROM productos WHERE id_producto = ? AND id_grupo = ?")->execute([$id, $grupoId]);
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
