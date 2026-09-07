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
verificarModuloVisible($pdo, 'recurrentes');

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

const PERIODICIDADES = ['mensual', 'trimestral', 'anual'];

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

function validarRecurrente(array $b): array {
    $errors = [];

    $contactoId = (int) ($b['contacto_id'] ?? 0);
    if ($contactoId <= 0) $errors[] = 'Selecciona un contacto';

    $nombre = clean($b['nombre'] ?? '');
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';
    elseif (strlen($nombre) > 150) $errors[] = 'El nombre no puede superar 150 caracteres';

    $periodicidad = clean($b['periodicidad'] ?? 'mensual');
    if (!in_array($periodicidad, PERIODICIDADES, true)) $errors[] = 'Periodicidad no válida';

    $diaGeneracion = (int) ($b['dia_generacion'] ?? 1);
    if ($diaGeneracion < 1 || $diaGeneracion > 28) $errors[] = 'El día de generación debe estar entre 1 y 28';

    $fechaInicio = validarFecha($b['fecha_inicio'] ?? null, 'La fecha de inicio', true, $errors);
    $fechaFin = validarFecha($b['fecha_fin'] ?? null, 'La fecha de fin', false, $errors);
    if ($fechaInicio && $fechaFin && $fechaFin < $fechaInicio) {
        $errors[] = 'La fecha de fin no puede ser anterior a la de inicio';
    }

    $diasVencimiento = (int) ($b['dias_vencimiento'] ?? 30);
    if ($diasVencimiento < 0 || $diasVencimiento > 365) $errors[] = 'Los días de vencimiento deben estar entre 0 y 365';

    $notas = nullOrStr($b['notas'] ?? '');
    if ($notas !== null && strlen($notas) > 1000) $errors[] = 'Las notas no pueden superar 1000 caracteres';

    $activa = array_key_exists('activa', $b) ? (!empty($b['activa']) ? 1 : 0) : 1;
    $enviarEmail = !empty($b['enviar_email']) ? 1 : 0;

    $lineasInput = is_array($b['lineas'] ?? null) ? $b['lineas'] : [];
    if (empty($lineasInput)) $errors[] = 'Añade al menos una línea';

    $lineas = [];
    foreach ($lineasInput as $idx => $linea) {
        $concepto = clean((string) ($linea['concepto'] ?? ''));
        if ($concepto === '') $errors[] = 'El concepto de la línea ' . ($idx + 1) . ' es obligatorio';
        if (strlen($concepto) > 255) $errors[] = 'El concepto de la línea ' . ($idx + 1) . ' no puede superar 255 caracteres';

        $cantidad = (float) str_replace(',', '.', (string) ($linea['cantidad'] ?? '0'));
        $precio = (float) str_replace(',', '.', (string) ($linea['precio_unitario'] ?? '0'));
        $ivaPct = (float) str_replace(',', '.', (string) ($linea['iva_porcentaje'] ?? '21'));

        if ($cantidad <= 0) $errors[] = 'La cantidad de la línea ' . ($idx + 1) . ' debe ser mayor que 0';
        if ($precio < 0) $errors[] = 'El precio de la línea ' . ($idx + 1) . ' no puede ser negativo';
        if ($ivaPct < 0 || $ivaPct > 100) $errors[] = 'El IVA de la línea ' . ($idx + 1) . ' debe estar entre 0 y 100';

        $lineas[] = [
            'concepto' => $concepto, 'cantidad' => $cantidad,
            'precio_unitario' => $precio, 'iva_porcentaje' => $ivaPct, 'orden' => $idx + 1,
        ];
    }

    return compact(
        'errors', 'contactoId', 'nombre', 'periodicidad', 'diaGeneracion',
        'fechaInicio', 'fechaFin', 'diasVencimiento', 'notas', 'activa', 'enviarEmail', 'lineas'
    );
}

function cargarRecurrente(PDO $pdo, int $id, int $grupoId): ?array {
    $s = $pdo->prepare("
        SELECT r.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos,
               c.email AS contacto_email, c.empresa AS contacto_empresa
        FROM facturas_recurrentes r
        INNER JOIN contactos c ON c.id_contacto = r.contacto_id
        WHERE r.id_recurrente = ? AND r.id_grupo = ?
        LIMIT 1
    ");
    $s->execute([$id, $grupoId]);
    $rec = $s->fetch();
    if (!$rec) return null;

    $l = $pdo->prepare("SELECT * FROM facturas_recurrentes_lineas WHERE recurrente_id = ? ORDER BY orden, id_linea");
    $l->execute([$id]);
    $rec['lineas'] = $l->fetchAll();

    $h = $pdo->prepare("
        SELECT id_factura, numero, estado, fecha_emision, total
        FROM facturas WHERE recurrente_id = ? ORDER BY fecha_emision DESC LIMIT 12
    ");
    $h->execute([$id]);
    $rec['historial'] = $h->fetchAll();

    return $rec;
}

function guardarLineasRecurrente(PDO $pdo, int $recurrenteId, array $lineas): void {
    $pdo->prepare("DELETE FROM facturas_recurrentes_lineas WHERE recurrente_id = ?")->execute([$recurrenteId]);
    $s = $pdo->prepare("
        INSERT INTO facturas_recurrentes_lineas (recurrente_id, concepto, cantidad, precio_unitario, iva_porcentaje, orden)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($lineas as $l) {
        $s->execute([$recurrenteId, $l['concepto'], $l['cantidad'], $l['precio_unitario'], $l['iva_porcentaje'], $l['orden']]);
    }
}

require __DIR__ . '/../config/facturas_recurrentes_util.php';

try {
    switch ($method) {
        case 'GET':
            if ($id) {
                $rec = cargarRecurrente($pdo, $id, $grupoId);
                $rec ? ok($rec) : err('Plantilla no encontrada', 404);
                break;
            }

            $where = ['r.id_grupo = ?']; $params = [$grupoId];
            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $where[] = '(r.nombre LIKE ? OR c.nombre LIKE ? OR c.apellidos LIKE ? OR c.empresa LIKE ?)';
                array_push($params, $like, $like, $like, $like);
            }
            $activa = $_GET['activa'] ?? '';
            if ($activa === '0' || $activa === '1') {
                $where[] = 'r.activa = ?';
                $params[] = (int) $activa;
            }

            $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
            $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
            $offset = ($pagina - 1) * $limite;
            $clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $stCount = $pdo->prepare("SELECT COUNT(*) FROM facturas_recurrentes r INNER JOIN contactos c ON c.id_contacto = r.contacto_id$clausulaWhere");
            $stCount->execute($params);
            $total = (int) $stCount->fetchColumn();

            $sql = "SELECT r.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos, c.empresa AS contacto_empresa,
                           (SELECT SUM(l.cantidad * l.precio_unitario * (1 + l.iva_porcentaje / 100))
                              FROM facturas_recurrentes_lineas l WHERE l.recurrente_id = r.id_recurrente) AS importe_estimado
                    FROM facturas_recurrentes r
                    INNER JOIN contactos c ON c.id_contacto = r.contacto_id
                    $clausulaWhere
                    ORDER BY r.activa DESC, r.proxima_generacion ASC
                    LIMIT ? OFFSET ?";
            $s = $pdo->prepare($sql);
            $s->execute([...$params, $limite, $offset]);
            ok($s->fetchAll(), ['total' => $total, 'pagina' => $pagina, 'limite' => $limite, 'paginas' => (int) ceil($total / $limite)]);
            break;

        case 'POST':
            if (($_GET['action'] ?? '') === 'generar' && $id) {
                $rec = cargarRecurrente($pdo, $id, $grupoId);
                if (!$rec) { err('Plantilla no encontrada', 404); break; }
                if (!$rec['lineas']) { err('La plantilla no tiene líneas'); break; }
                try {
                    $resultado = generarFacturaDesdeRecurrente($pdo, $rec, $userId);
                    ok($resultado);
                } catch (Throwable $e) {
                    error_log('[facturas_recurrentes:generar] ' . $e->getMessage());
                    err('No se pudo generar la factura', 500);
                }
                break;
            }

            $v = validarRecurrente(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $contacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $contacto->execute([$v['contactoId'], $grupoId]);
            if (!$contacto->fetch()) { err('Contacto no encontrado', 404); break; }

            $pdo->beginTransaction();
            $s = $pdo->prepare("
                INSERT INTO facturas_recurrentes
                    (contacto_id, nombre, periodicidad, dia_generacion, fecha_inicio, fecha_fin,
                     dias_vencimiento, notas, activa, enviar_email, proxima_generacion, creado_por, id_grupo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([
                $v['contactoId'], $v['nombre'], $v['periodicidad'], $v['diaGeneracion'],
                $v['fechaInicio'], $v['fechaFin'], $v['diasVencimiento'], $v['notas'],
                $v['activa'], $v['enviarEmail'], $v['fechaInicio'], $userId, $grupoId,
            ]);
            $newId = (int) $pdo->lastInsertId();
            guardarLineasRecurrente($pdo, $newId, $v['lineas']);
            $nuevo = cargarRecurrente($pdo, $newId, $grupoId);
            registrarAuditoria($pdo, 'facturas_recurrentes', $newId, 'crear', null, $nuevo ?: null);
            $pdo->commit();
            ok($nuevo);
            break;

        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $antes = cargarRecurrente($pdo, $id, $grupoId);
            if (!$antes) { err('Plantilla no encontrada', 404); break; }

            $v = validarRecurrente(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $contacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $contacto->execute([$v['contactoId'], $grupoId]);
            if (!$contacto->fetch()) { err('Contacto no encontrado', 404); break; }

            $pdo->beginTransaction();
            $s = $pdo->prepare("
                UPDATE facturas_recurrentes
                   SET contacto_id=?, nombre=?, periodicidad=?, dia_generacion=?, fecha_inicio=?, fecha_fin=?,
                       dias_vencimiento=?, notas=?, activa=?, enviar_email=?
                 WHERE id_recurrente=? AND id_grupo=?
            ");
            $s->execute([
                $v['contactoId'], $v['nombre'], $v['periodicidad'], $v['diaGeneracion'],
                $v['fechaInicio'], $v['fechaFin'], $v['diasVencimiento'], $v['notas'],
                $v['activa'], $v['enviarEmail'], $id, $grupoId,
            ]);
            guardarLineasRecurrente($pdo, $id, $v['lineas']);
            $despues = cargarRecurrente($pdo, $id, $grupoId);
            registrarAuditoria($pdo, 'facturas_recurrentes', $id, 'editar', $antes, $despues);
            $pdo->commit();
            ok($despues);
            break;

        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $antes = cargarRecurrente($pdo, $id, $grupoId);
            if (!$antes) { err('Plantilla no encontrada', 404); break; }
            $pdo->prepare("DELETE FROM facturas_recurrentes WHERE id_recurrente = ? AND id_grupo = ?")->execute([$id, $grupoId]);
            registrarAuditoria($pdo, 'facturas_recurrentes', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[facturas_recurrentes] ' . $e->getMessage());
    err('Error de base de datos', 500);
}
