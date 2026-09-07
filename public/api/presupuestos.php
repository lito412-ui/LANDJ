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
verificarModuloVisible($pdo, 'presupuestos');
require __DIR__ . '/../config/csv_util.php';

$userId = (int) $_SESSION['user_id'];
$grupoId = obtenerIdGrupoActual();
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

const PRESUPUESTO_ESTADOS = ['borrador', 'enviado', 'aceptado', 'rechazado', 'expirado', 'convertido'];

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

function validarPresupuesto(array $b): array {
    $errors = [];

    $contactoId = (int) ($b['contacto_id'] ?? 0);
    if ($contactoId <= 0) $errors[] = 'Selecciona un contacto';

    $estado = clean($b['estado'] ?? 'borrador');
    if (!in_array($estado, PRESUPUESTO_ESTADOS, true)) $errors[] = 'Estado no valido';

    $fechaEmision = validarFecha($b['fecha_emision'] ?? null, 'La fecha de emision', true, $errors);
    $fechaValidez = validarFecha($b['fecha_validez'] ?? null, 'La fecha de validez', false, $errors);
    if ($fechaEmision && $fechaValidez && $fechaValidez < $fechaEmision) {
        $errors[] = 'La fecha de validez no puede ser anterior a la emision';
    }

    $notas = nullOrStr($b['notas'] ?? '');
    if ($notas !== null && strlen($notas) > 1000) $errors[] = 'Las notas no pueden superar 1000 caracteres';

    $lineasInput = is_array($b['lineas'] ?? null) ? $b['lineas'] : [];
    if (empty($lineasInput)) $errors[] = 'Anade al menos una linea';

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

    return compact('errors', 'contactoId', 'estado', 'fechaEmision', 'fechaValidez', 'notas', 'lineas', 'base', 'iva', 'total');
}

function generarNumeroPresupuesto(PDO $pdo): string {
    $year = date('Y');
    $prefix = "PRE-$year-";
    $s = $pdo->prepare("SELECT numero FROM presupuestos WHERE numero LIKE ? ORDER BY numero DESC LIMIT 1");
    $s->execute([$prefix . '%']);
    $ultimo = $s->fetchColumn();
    $seq = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;
    return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

function cargarPresupuesto(PDO $pdo, int $id): ?array {
    $s = $pdo->prepare("
        SELECT p.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos,
               c.email AS contacto_email, c.empresa AS contacto_empresa,
               f.numero AS factura_numero
        FROM presupuestos p
        INNER JOIN contactos c ON c.id_contacto = p.contacto_id
        LEFT JOIN facturas f ON f.id_factura = p.factura_id
        WHERE p.id_presupuesto = ?
        LIMIT 1
    ");
    $s->execute([$id]);
    $presupuesto = $s->fetch();
    if (!$presupuesto) return null;

    $l = $pdo->prepare("SELECT * FROM presupuesto_lineas WHERE presupuesto_id = ? ORDER BY orden, id_linea");
    $l->execute([$id]);
    $presupuesto['lineas'] = $l->fetchAll();
    return $presupuesto;
}

function guardarLineasPresupuesto(PDO $pdo, int $presupuestoId, array $lineas): void {
    $pdo->prepare("DELETE FROM presupuesto_lineas WHERE presupuesto_id = ?")->execute([$presupuestoId]);
    $s = $pdo->prepare("
        INSERT INTO presupuesto_lineas
            (presupuesto_id, concepto, cantidad, precio_unitario, iva_porcentaje, subtotal, iva_importe, total_linea, orden)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($lineas as $linea) {
        $s->execute([
            $presupuestoId,
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

// ─── Convertir presupuesto en factura ───────────────────────────────────────
function generarNumeroFacturaDesdePresupuesto(PDO $pdo): string {
    $year = date('Y');
    $prefix = "FAC-$year-";
    $s = $pdo->prepare("SELECT numero FROM facturas WHERE numero LIKE ? ORDER BY numero DESC LIMIT 1");
    $s->execute([$prefix . '%']);
    $ultimo = $s->fetchColumn();
    $seq = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;
    return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

function convertirEnFactura(PDO $pdo, int $id, int $userId): void {
    $presupuesto = cargarPresupuesto($pdo, $id);
    if (!$presupuesto) { err('Presupuesto no encontrado', 404); return; }
    if ($presupuesto['factura_id']) { err('Este presupuesto ya se convirtió en la factura ' . $presupuesto['factura_numero']); return; }
    if (empty($presupuesto['lineas'])) { err('El presupuesto no tiene lineas'); return; }

    $pdo->beginTransaction();
    try {
        $numero = generarNumeroFacturaDesdePresupuesto($pdo);
        $sIns = $pdo->prepare("
            INSERT INTO facturas
                (numero, contacto_id, estado, fecha_emision, fecha_vencimiento, base_imponible, iva_total, total, notas, creado_por)
            VALUES (?, ?, 'emitida', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, ?, ?, ?)
        ");
        $notas = 'Generada a partir del presupuesto ' . $presupuesto['numero'] . ($presupuesto['notas'] ? ("\n" . $presupuesto['notas']) : '');
        $sIns->execute([$numero, $presupuesto['contacto_id'], $presupuesto['base_imponible'], $presupuesto['iva_total'], $presupuesto['total'], $notas, $userId]);
        $facturaId = (int) $pdo->lastInsertId();

        $sLinea = $pdo->prepare("
            INSERT INTO factura_lineas
                (factura_id, concepto, cantidad, precio_unitario, iva_porcentaje, subtotal, iva_importe, total_linea, orden)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($presupuesto['lineas'] as $l) {
            $sLinea->execute([
                $facturaId, $l['concepto'], $l['cantidad'], $l['precio_unitario'], $l['iva_porcentaje'],
                $l['subtotal'], $l['iva_importe'], $l['total_linea'], $l['orden'],
            ]);
        }

        $pdo->prepare("UPDATE presupuestos SET estado = 'convertido', factura_id = ? WHERE id_presupuesto = ?")
            ->execute([$facturaId, $id]);

        registrarAuditoria($pdo, 'facturas', $facturaId, 'crear', null, ['origen_presupuesto' => $presupuesto['numero']]);
        registrarAuditoria($pdo, 'presupuestos', $id, 'editar', ['estado' => $presupuesto['estado']], ['estado' => 'convertido', 'factura_id' => $facturaId]);

        $pdo->commit();
        ok(['factura_id' => $facturaId, 'factura_numero' => $numero]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('[presupuestos:convertir] ' . $e->getMessage());
        err('No se pudo convertir el presupuesto en factura', 500);
    }
}

// ─── Exportar / importar CSV ────────────────────────────────────────────────
function exportarPresupuestos(PDO $pdo, int $grupoId): void {
    $s = $pdo->prepare("
        SELECT p.numero, c.email AS contacto_email, p.estado, p.fecha_emision, p.fecha_validez, p.notas,
               pl.concepto, pl.cantidad, pl.precio_unitario, pl.iva_porcentaje
        FROM presupuestos p
        INNER JOIN contactos c ON c.id_contacto = p.contacto_id
        INNER JOIN presupuesto_lineas pl ON pl.presupuesto_id = p.id_presupuesto
        WHERE p.id_grupo = ? ORDER BY p.numero, pl.orden, pl.id_linea
    ");
    $s->execute([$grupoId]);
    $filas = [];
    foreach ($s->fetchAll() as $l) {
        $filas[] = [$l['numero'], $l['contacto_email'], $l['estado'], $l['fecha_emision'], $l['fecha_validez'],
            $l['notas'], $l['concepto'], $l['cantidad'], $l['precio_unitario'], $l['iva_porcentaje']];
    }
    csvDescargar('presupuestos_' . date('Y-m-d') . '.csv', [
        'numero', 'contacto_email', 'estado', 'fecha_emision', 'fecha_validez',
        'notas', 'concepto', 'cantidad', 'precio_unitario', 'iva_porcentaje',
    ], $filas);
}

function importarPresupuestos(PDO $pdo, int $userId, int $grupoId): void {
    try {
        $filas = csvLeerSubida('archivo');
    } catch (RuntimeException $e) {
        err($e->getMessage());
        return;
    }
    if (!$filas) { err('El archivo CSV está vacío o no tiene un formato válido'); return; }

    $grupos = [];
    foreach ($filas as $idx => $fila) {
        $numero = trim($fila['numero'] ?? '');
        $clave  = $numero !== '' ? $numero : '__sin_numero_' . $idx;
        $grupos[$clave]['filas'][] = ['idx' => $idx, 'datos' => $fila];
        $grupos[$clave]['cabecera'] ??= $fila;
    }

    $sContacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE email = ? AND id_grupo = ? LIMIT 1");
    $sExiste   = $pdo->prepare("SELECT id_presupuesto FROM presupuestos WHERE numero = ? AND id_grupo = ? LIMIT 1");

    $creados = 0; $errores = [];

    foreach ($grupos as $grupo) {
        $cabecera    = $grupo['cabecera'];
        $primeraFila = $grupo['filas'][0]['idx'] + 2;

        $numeroReal = trim($cabecera['numero'] ?? '');
        if ($numeroReal !== '') {
            $sExiste->execute([$numeroReal, $grupoId]);
            if ($sExiste->fetchColumn()) {
                $errores[] = "Fila $primeraFila: ya existe un presupuesto con el número $numeroReal (se omite)";
                continue;
            }
        }

        $email = trim($cabecera['contacto_email'] ?? '');
        if ($email === '') { $errores[] = "Fila $primeraFila: falta contacto_email"; continue; }
        $sContacto->execute([$email, $grupoId]);
        $contactoId = $sContacto->fetchColumn() ?: null;
        if (!$contactoId) { $errores[] = "Fila $primeraFila: no existe ningún contacto con email $email"; continue; }

        $lineasInput = array_map(fn($f) => [
            'concepto'        => $f['datos']['concepto'] ?? '',
            'cantidad'        => $f['datos']['cantidad'] ?? '',
            'precio_unitario' => $f['datos']['precio_unitario'] ?? '',
            'iva_porcentaje'  => $f['datos']['iva_porcentaje'] ?? '',
        ], $grupo['filas']);

        $b = [
            'contacto_id'   => $contactoId,
            'estado'        => $cabecera['estado'] ?? 'borrador',
            'fecha_emision' => $cabecera['fecha_emision'] ?? '',
            'fecha_validez' => $cabecera['fecha_validez'] ?? '',
            'notas'         => $cabecera['notas'] ?? '',
            'lineas'        => $lineasInput,
        ];
        $v = validarPresupuesto($b);
        if ($v['errors']) { $errores[] = "Fila $primeraFila: " . implode('; ', $v['errors']); continue; }

        try {
            $pdo->beginTransaction();
            $num = $numeroReal !== '' ? $numeroReal : generarNumeroPresupuesto($pdo);
            $s = $pdo->prepare("
                INSERT INTO presupuestos
                    (numero, contacto_id, estado, fecha_emision, fecha_validez, base_imponible, iva_total, total, notas, creado_por, id_grupo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([$num, $v['contactoId'], $v['estado'], $v['fechaEmision'], $v['fechaValidez'], $v['base'], $v['iva'], $v['total'], $v['notas'], $userId, $grupoId]);
            $nuevoId = (int) $pdo->lastInsertId();
            guardarLineasPresupuesto($pdo, $nuevoId, $v['lineas']);
            registrarAuditoria($pdo, 'presupuestos', $nuevoId, 'crear', null, $v);
            $pdo->commit();
            $creados++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $duplicado = $e instanceof PDOException && $e->getCode() === '23000';
            $errores[] = "Fila $primeraFila: error al guardar el presupuesto (" . ($duplicado ? 'número duplicado' : 'error de base de datos') . ")";
        }
    }

    ok(['creados' => $creados, 'actualizados' => 0, 'errores' => $errores, 'total' => count($grupos)]);
}

try {
    switch ($method) {
        case 'GET':
            if (($_GET['action'] ?? '') === 'exportar') {
                exportarPresupuestos($pdo, $grupoId);
                break;
            }
            if ($id) {
                if (!recursoPerteneceAlGrupo($pdo, 'presupuestos', 'id_presupuesto', $id, $grupoId)) { err('Presupuesto no encontrado', 404); break; }
                $presupuesto = cargarPresupuesto($pdo, $id);
                $presupuesto ? ok($presupuesto) : err('Presupuesto no encontrado', 404);
                break;
            }

            $where = ['p.id_grupo = ?'];
            $params = [$grupoId];

            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $where[] = '(p.numero LIKE ? OR c.nombre LIKE ? OR c.apellidos LIKE ? OR c.empresa LIKE ? OR c.email LIKE ?)';
                array_push($params, $like, $like, $like, $like, $like);
            }

            $estado = clean($_GET['estado'] ?? '');
            if ($estado !== '' && in_array($estado, PRESUPUESTO_ESTADOS, true)) {
                $where[] = 'p.estado = ?';
                $params[] = $estado;
            }

            foreach ([['desde', '>='], ['hasta', '<=']] as [$key, $op]) {
                $fecha = clean($_GET[$key] ?? '');
                if ($fecha !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    $where[] = "p.fecha_emision $op ?";
                    $params[] = $fecha;
                }
            }

            $colsPermitidas = ['numero', 'fecha_emision', 'fecha_validez', 'total', 'estado'];
            $orden = in_array($_GET['orden'] ?? '', $colsPermitidas, true) ? $_GET['orden'] : 'fecha_emision';
            $dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
            $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
            $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
            $offset = ($pagina - 1) * $limite;

            $clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
            $from = " FROM presupuestos p INNER JOIN contactos c ON c.id_contacto = p.contacto_id$clausulaWhere";

            $stCount = $pdo->prepare("SELECT COUNT(*)$from");
            $stCount->execute($params);
            $total = (int) $stCount->fetchColumn();
            $paginas = (int) ceil($total / $limite);

            $sql = "SELECT p.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos,
                           c.email AS contacto_email, c.empresa AS contacto_empresa
                    $from
                    ORDER BY p.$orden $dir, p.id_presupuesto DESC
                    LIMIT ? OFFSET ?";
            $s = $pdo->prepare($sql);
            $s->execute([...$params, $limite, $offset]);
            ok($s->fetchAll(), compact('total', 'pagina', 'limite', 'paginas'));
            break;

        case 'POST':
            if (($_GET['action'] ?? '') === 'importar') {
                importarPresupuestos($pdo, $userId, $grupoId);
                break;
            }
            if (($_GET['action'] ?? '') === 'convertir') {
                if (!$id) { err('ID requerido'); break; }
                convertirEnFactura($pdo, $id, $userId);
                break;
            }

            $v = validarPresupuesto(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $contacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $contacto->execute([$v['contactoId'], $grupoId]);
            if (!$contacto->fetch()) { err('Contacto no encontrado', 404); break; }

            $pdo->beginTransaction();
            $numero = generarNumeroPresupuesto($pdo);
            $s = $pdo->prepare("
                INSERT INTO presupuestos
                    (numero, contacto_id, estado, fecha_emision, fecha_validez, base_imponible, iva_total, total, notas, creado_por, id_grupo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([$numero, $v['contactoId'], $v['estado'], $v['fechaEmision'], $v['fechaValidez'], $v['base'], $v['iva'], $v['total'], $v['notas'], $userId, $grupoId]);
            $newId = (int) $pdo->lastInsertId();
            guardarLineasPresupuesto($pdo, $newId, $v['lineas']);
            $nuevo = cargarPresupuesto($pdo, $newId);
            registrarAuditoria($pdo, 'presupuestos', $newId, 'crear', null, $nuevo ?: null);
            $pdo->commit();
            ok($nuevo);
            break;

        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            if (!recursoPerteneceAlGrupo($pdo, 'presupuestos', 'id_presupuesto', $id, $grupoId)) { err('Presupuesto no encontrado', 404); break; }
            $antes = cargarPresupuesto($pdo, $id);
            if (!$antes) { err('Presupuesto no encontrado', 404); break; }
            if ($antes['factura_id']) { err('No se puede editar un presupuesto ya convertido en factura'); break; }

            $v = validarPresupuesto(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $contacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $contacto->execute([$v['contactoId'], $grupoId]);
            if (!$contacto->fetch()) { err('Contacto no encontrado', 404); break; }

            $pdo->beginTransaction();
            $s = $pdo->prepare("
                UPDATE presupuestos
                   SET contacto_id=?, estado=?, fecha_emision=?, fecha_validez=?,
                       base_imponible=?, iva_total=?, total=?, notas=?
                 WHERE id_presupuesto=? AND id_grupo=?
            ");
            $s->execute([$v['contactoId'], $v['estado'], $v['fechaEmision'], $v['fechaValidez'], $v['base'], $v['iva'], $v['total'], $v['notas'], $id, $grupoId]);
            guardarLineasPresupuesto($pdo, $id, $v['lineas']);
            $despues = cargarPresupuesto($pdo, $id);
            registrarAuditoria($pdo, 'presupuestos', $id, 'editar', $antes, $despues);
            $pdo->commit();
            ok($despues);
            break;

        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            if (!recursoPerteneceAlGrupo($pdo, 'presupuestos', 'id_presupuesto', $id, $grupoId)) { err('Presupuesto no encontrado', 404); break; }
            $antes = cargarPresupuesto($pdo, $id);
            if (!$antes) { err('Presupuesto no encontrado', 404); break; }
            $s = $pdo->prepare("DELETE FROM presupuestos WHERE id_presupuesto = ? AND id_grupo = ?");
            $s->execute([$id, $grupoId]);
            registrarAuditoria($pdo, 'presupuestos', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Metodo no permitido', 405);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[presupuestos] ' . $e->getMessage());
    err('Error de base de datos', 500);
}
