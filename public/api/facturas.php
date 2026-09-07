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
verificarModuloVisible($pdo, 'facturas');
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

const FACTURA_ESTADOS = ['borrador', 'emitida', 'pagada', 'vencida', 'cancelada'];

function validarFecha(?string $fecha, string $campo, bool $obligatoria, array &$errors): ?string {
    $fecha = $fecha !== null ? clean($fecha) : '';
    if ($fecha === '') {
        if ($obligatoria) $errors[] = "$campo es obligatorio";
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $errors[] = "$campo debe tener formato YYYY-MM-DD";
        return null;
    }
    return $fecha;
}

function validarFactura(array $b): array {
    $errors = [];

    $contactoId = (int) ($b['contacto_id'] ?? 0);
    if ($contactoId <= 0) $errors[] = 'Selecciona un contacto';

    $estado = clean($b['estado'] ?? 'borrador');
    if (!in_array($estado, FACTURA_ESTADOS, true)) $errors[] = 'Estado no valido';

    $fechaEmision = validarFecha($b['fecha_emision'] ?? null, 'La fecha de emision', true, $errors);
    $fechaVencimiento = validarFecha($b['fecha_vencimiento'] ?? null, 'La fecha de vencimiento', false, $errors);
    if ($fechaEmision && $fechaVencimiento && $fechaVencimiento < $fechaEmision) {
        $errors[] = 'La fecha de vencimiento no puede ser anterior a la emision';
    }

    $notas = nullOrStr($b['notas'] ?? '');
    if ($notas !== null && strlen($notas) > 1000) $errors[] = 'Las notas no pueden superar 1000 caracteres';

    $lineasInput = is_array($b['lineas'] ?? null) ? $b['lineas'] : [];
    if (empty($lineasInput)) $errors[] = 'Anade al menos una linea de factura';

    $lineas = [];
    foreach ($lineasInput as $idx => $linea) {
        $concepto = clean((string) ($linea['concepto'] ?? ''));
        if ($concepto === '') $errors[] = 'El concepto de la linea ' . ($idx + 1) . ' es obligatorio';
        if (strlen($concepto) > 255) $errors[] = 'El concepto de la linea ' . ($idx + 1) . ' no puede superar 255 caracteres';

        $cantidad = (float) str_replace(',', '.', (string) ($linea['cantidad'] ?? '0'));
        $precio = (float) str_replace(',', '.', (string) ($linea['precio_unitario'] ?? '0'));
        $ivaPct = (float) str_replace(',', '.', (string) ($linea['iva_porcentaje'] ?? '21'));

        if ($cantidad <= 0) $errors[] = 'La cantidad de la linea ' . ($idx + 1) . ' debe ser mayor que 0';
        if ($precio < 0) $errors[] = 'El precio de la linea ' . ($idx + 1) . ' no puede ser negativo';
        if ($ivaPct < 0 || $ivaPct > 100) $errors[] = 'El IVA de la linea ' . ($idx + 1) . ' debe estar entre 0 y 100';

        $subtotal = round($cantidad * $precio, 2);
        $ivaImporte = round($subtotal * ($ivaPct / 100), 2);
        $totalLinea = round($subtotal + $ivaImporte, 2);

        $lineas[] = [
            'concepto' => $concepto,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'iva_porcentaje' => $ivaPct,
            'subtotal' => $subtotal,
            'iva_importe' => $ivaImporte,
            'total_linea' => $totalLinea,
            'orden' => $idx + 1,
        ];
    }

    $base = round(array_sum(array_column($lineas, 'subtotal')), 2);
    $iva = round(array_sum(array_column($lineas, 'iva_importe')), 2);
    $total = round(array_sum(array_column($lineas, 'total_linea')), 2);

    return compact('errors', 'contactoId', 'estado', 'fechaEmision', 'fechaVencimiento', 'notas', 'lineas', 'base', 'iva', 'total');
}

function generarNumeroFactura(PDO $pdo): string {
    $year = date('Y');
    $prefix = "FAC-$year-";
    $s = $pdo->prepare("SELECT numero FROM facturas WHERE numero LIKE ? ORDER BY numero DESC LIMIT 1");
    $s->execute([$prefix . '%']);
    $ultimo = $s->fetchColumn();
    $seq = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;
    return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

function cargarFactura(PDO $pdo, int $id): ?array {
    $s = $pdo->prepare("
        SELECT f.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos,
               c.email AS contacto_email, c.empresa AS contacto_empresa
        FROM facturas f
        INNER JOIN contactos c ON c.id_contacto = f.contacto_id
        WHERE f.id_factura = ?
        LIMIT 1
    ");
    $s->execute([$id]);
    $factura = $s->fetch();
    if (!$factura) return null;

    $l = $pdo->prepare("SELECT * FROM factura_lineas WHERE factura_id = ? ORDER BY orden, id_linea");
    $l->execute([$id]);
    $factura['lineas'] = $l->fetchAll();
    return $factura;
}

function guardarLineas(PDO $pdo, int $facturaId, array $lineas): void {
    $pdo->prepare("DELETE FROM factura_lineas WHERE factura_id = ?")->execute([$facturaId]);
    $s = $pdo->prepare("
        INSERT INTO factura_lineas
            (factura_id, concepto, cantidad, precio_unitario, iva_porcentaje, subtotal, iva_importe, total_linea, orden)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($lineas as $linea) {
        $s->execute([
            $facturaId,
            $linea['concepto'],
            $linea['cantidad'],
            $linea['precio_unitario'],
            $linea['iva_porcentaje'],
            $linea['subtotal'],
            $linea['iva_importe'],
            $linea['total_linea'],
            $linea['orden'],
        ]);
    }
}

// ─── Exportar / importar CSV ────────────────────────────────────────────────
// Formato: una fila por cada línea de factura. Varias filas con el mismo
// "numero" forman una única factura al importar.
function exportarFacturas(PDO $pdo): void {
    $s = $pdo->query("
        SELECT f.numero, c.email AS contacto_email, f.estado, f.fecha_emision, f.fecha_vencimiento, f.notas,
               fl.concepto, fl.cantidad, fl.precio_unitario, fl.iva_porcentaje
        FROM facturas f
        INNER JOIN contactos c ON c.id_contacto = f.contacto_id
        INNER JOIN factura_lineas fl ON fl.factura_id = f.id_factura
        ORDER BY f.numero, fl.orden, fl.id_linea
    ");
    $filas = [];
    foreach ($s->fetchAll() as $l) {
        $filas[] = [$l['numero'], $l['contacto_email'], $l['estado'], $l['fecha_emision'], $l['fecha_vencimiento'],
            $l['notas'], $l['concepto'], $l['cantidad'], $l['precio_unitario'], $l['iva_porcentaje']];
    }
    csvDescargar('facturas_' . date('Y-m-d') . '.csv', [
        'numero', 'contacto_email', 'estado', 'fecha_emision', 'fecha_vencimiento',
        'notas', 'concepto', 'cantidad', 'precio_unitario', 'iva_porcentaje',
    ], $filas);
}

function importarFacturas(PDO $pdo, int $userId): void {
    try {
        $filas = csvLeerSubida('archivo');
    } catch (RuntimeException $e) {
        err($e->getMessage());
        return;
    }
    if (!$filas) { err('El archivo CSV está vacío o no tiene un formato válido'); return; }

    // Agrupar filas por número de factura; sin número, cada fila es su propia factura
    $grupos = [];
    foreach ($filas as $idx => $fila) {
        $numero = trim($fila['numero'] ?? '');
        $clave  = $numero !== '' ? $numero : '__sin_numero_' . $idx;
        $grupos[$clave]['filas'][] = ['idx' => $idx, 'datos' => $fila];
        $grupos[$clave]['cabecera'] ??= $fila;
    }

    $sContacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE email = ? LIMIT 1");
    $sExiste   = $pdo->prepare("SELECT id_factura FROM facturas WHERE numero = ? LIMIT 1");

    $creados = 0; $errores = [];

    foreach ($grupos as $grupo) {
        $cabecera    = $grupo['cabecera'];
        $primeraFila = $grupo['filas'][0]['idx'] + 2;

        $numeroReal = trim($cabecera['numero'] ?? '');
        if ($numeroReal !== '') {
            $sExiste->execute([$numeroReal]);
            if ($sExiste->fetchColumn()) {
                $errores[] = "Fila $primeraFila: ya existe una factura con el número $numeroReal (se omite)";
                continue;
            }
        }

        $email = trim($cabecera['contacto_email'] ?? '');
        if ($email === '') { $errores[] = "Fila $primeraFila: falta contacto_email"; continue; }
        $sContacto->execute([$email]);
        $contactoId = $sContacto->fetchColumn() ?: null;
        if (!$contactoId) { $errores[] = "Fila $primeraFila: no existe ningún contacto con email $email"; continue; }

        $lineasInput = array_map(fn($f) => [
            'concepto'        => $f['datos']['concepto'] ?? '',
            'cantidad'        => $f['datos']['cantidad'] ?? '',
            'precio_unitario' => $f['datos']['precio_unitario'] ?? '',
            'iva_porcentaje'  => $f['datos']['iva_porcentaje'] ?? '',
        ], $grupo['filas']);

        $b = [
            'contacto_id'       => $contactoId,
            'estado'            => $cabecera['estado'] ?? 'borrador',
            'fecha_emision'     => $cabecera['fecha_emision'] ?? '',
            'fecha_vencimiento' => $cabecera['fecha_vencimiento'] ?? '',
            'notas'             => $cabecera['notas'] ?? '',
            'lineas'            => $lineasInput,
        ];
        $v = validarFactura($b);
        if ($v['errors']) { $errores[] = "Fila $primeraFila: " . implode('; ', $v['errors']); continue; }

        try {
            $pdo->beginTransaction();
            $num = $numeroReal !== '' ? $numeroReal : generarNumeroFactura($pdo);
            $s = $pdo->prepare("
                INSERT INTO facturas
                    (numero, contacto_id, estado, fecha_emision, fecha_vencimiento, base_imponible, iva_total, total, notas, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([$num, $v['contactoId'], $v['estado'], $v['fechaEmision'], $v['fechaVencimiento'], $v['base'], $v['iva'], $v['total'], $v['notas'], $userId]);
            $nuevoId = (int) $pdo->lastInsertId();
            guardarLineas($pdo, $nuevoId, $v['lineas']);
            registrarAuditoria($pdo, 'facturas', $nuevoId, 'crear', null, $v);
            $pdo->commit();
            $creados++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $duplicado = $e instanceof PDOException && $e->getCode() === '23000';
            $errores[] = "Fila $primeraFila: error al guardar la factura (" . ($duplicado ? 'número duplicado' : 'error de base de datos') . ")";
        }
    }

    ok(['creados' => $creados, 'actualizados' => 0, 'errores' => $errores, 'total' => count($grupos)]);
}

try {
    switch ($method) {
        case 'GET':
            if (($_GET['action'] ?? '') === 'exportar') {
                exportarFacturas($pdo);
                break;
            }
            if ($id) {
                $factura = cargarFactura($pdo, $id);
                $factura ? ok($factura) : err('Factura no encontrada', 404);
                break;
            }

            $where = [];
            $params = [];

            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $where[] = '(f.numero LIKE ? OR c.nombre LIKE ? OR c.apellidos LIKE ? OR c.empresa LIKE ? OR c.email LIKE ?)';
                array_push($params, $like, $like, $like, $like, $like);
            }

            $estado = clean($_GET['estado'] ?? '');
            if ($estado !== '' && in_array($estado, FACTURA_ESTADOS, true)) {
                $where[] = 'f.estado = ?';
                $params[] = $estado;
            }

            foreach ([['desde', '>='], ['hasta', '<=']] as [$key, $op]) {
                $fecha = clean($_GET[$key] ?? '');
                if ($fecha !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    $where[] = "f.fecha_emision $op ?";
                    $params[] = $fecha;
                }
            }

            $colsPermitidas = ['numero', 'fecha_emision', 'fecha_vencimiento', 'total', 'estado'];
            $orden = in_array($_GET['orden'] ?? '', $colsPermitidas, true) ? $_GET['orden'] : 'fecha_emision';
            $dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
            $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
            $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
            $offset = ($pagina - 1) * $limite;

            $clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
            $from = " FROM facturas f INNER JOIN contactos c ON c.id_contacto = f.contacto_id$clausulaWhere";

            $stCount = $pdo->prepare("SELECT COUNT(*)$from");
            $stCount->execute($params);
            $total = (int) $stCount->fetchColumn();
            $paginas = (int) ceil($total / $limite);

            $sql = "SELECT f.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos,
                           c.email AS contacto_email, c.empresa AS contacto_empresa
                    $from
                    ORDER BY f.$orden $dir, f.id_factura DESC
                    LIMIT ? OFFSET ?";
            $s = $pdo->prepare($sql);
            $s->execute([...$params, $limite, $offset]);
            ok($s->fetchAll(), compact('total', 'pagina', 'limite', 'paginas'));
            break;

        case 'POST':
            if (($_GET['action'] ?? '') === 'importar') {
                importarFacturas($pdo, $userId);
                break;
            }
            $v = validarFactura(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $contacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE id_contacto = ?");
            $contacto->execute([$v['contactoId']]);
            if (!$contacto->fetch()) { err('Contacto no encontrado', 404); break; }

            $pdo->beginTransaction();
            $numero = generarNumeroFactura($pdo);
            $s = $pdo->prepare("
                INSERT INTO facturas
                    (numero, contacto_id, estado, fecha_emision, fecha_vencimiento, base_imponible, iva_total, total, notas, creado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([$numero, $v['contactoId'], $v['estado'], $v['fechaEmision'], $v['fechaVencimiento'], $v['base'], $v['iva'], $v['total'], $v['notas'], $userId]);
            $newId = (int) $pdo->lastInsertId();
            guardarLineas($pdo, $newId, $v['lineas']);
            $nuevo = cargarFactura($pdo, $newId);
            registrarAuditoria($pdo, 'facturas', $newId, 'crear', null, $nuevo ?: null);
            $pdo->commit();
            ok($nuevo);
            break;

        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $antes = cargarFactura($pdo, $id);
            if (!$antes) { err('Factura no encontrada', 404); break; }

            $v = validarFactura(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $contacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE id_contacto = ?");
            $contacto->execute([$v['contactoId']]);
            if (!$contacto->fetch()) { err('Contacto no encontrado', 404); break; }

            $pdo->beginTransaction();
            $s = $pdo->prepare("
                UPDATE facturas
                   SET contacto_id=?, estado=?, fecha_emision=?, fecha_vencimiento=?,
                       base_imponible=?, iva_total=?, total=?, notas=?
                 WHERE id_factura=?
            ");
            $s->execute([$v['contactoId'], $v['estado'], $v['fechaEmision'], $v['fechaVencimiento'], $v['base'], $v['iva'], $v['total'], $v['notas'], $id]);
            guardarLineas($pdo, $id, $v['lineas']);
            $despues = cargarFactura($pdo, $id);
            registrarAuditoria($pdo, 'facturas', $id, 'editar', $antes, $despues);
            $pdo->commit();
            ok($despues);
            break;

        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $antes = cargarFactura($pdo, $id);
            if (!$antes) { err('Factura no encontrada', 404); break; }
            $s = $pdo->prepare("DELETE FROM facturas WHERE id_factura = ?");
            $s->execute([$id]);
            registrarAuditoria($pdo, 'facturas', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Metodo no permitido', 405);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[facturas] ' . $e->getMessage());
    err('Error de base de datos', 500);
}
