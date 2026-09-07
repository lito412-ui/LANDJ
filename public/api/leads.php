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
verificarModuloVisible($pdo, 'leads');
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

// ─── Exportar / importar CSV ────────────────────────────────────────────────
function exportarLeads(PDO $pdo, int $grupoId): void {
    $s = $pdo->prepare("SELECT nombre, email, telefono, empresa, origen, estado, notas, created_at FROM leads WHERE id_grupo = ? ORDER BY nombre");
    $s->execute([$grupoId]);
    $filas = [];
    foreach ($s->fetchAll() as $l) {
        $filas[] = [$l['nombre'], $l['email'], $l['telefono'], $l['empresa'], $l['origen'], $l['estado'], $l['notas'], $l['created_at']];
    }
    csvDescargar('leads_' . date('Y-m-d') . '.csv',
        ['nombre', 'email', 'telefono', 'empresa', 'origen', 'estado', 'notas', 'created_at'], $filas);
}

function importarLeads(PDO $pdo, int $userId, int $grupoId): void {
    try {
        $filas = csvLeerSubida('archivo');
    } catch (RuntimeException $e) {
        err($e->getMessage());
        return;
    }
    if (!$filas) { err('El archivo CSV está vacío o no tiene un formato válido'); return; }

    $creados = 0; $actualizados = 0; $errores = [];
    $sBuscarEmail = $pdo->prepare("SELECT id_lead FROM leads WHERE email = ? AND id_grupo = ? LIMIT 1");
    $sIns = $pdo->prepare(
        "INSERT INTO leads (nombre, email, telefono, empresa, origen, estado, notas, creado_por, id_grupo)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $sUpd = $pdo->prepare(
        "UPDATE leads SET nombre=?, telefono=?, empresa=?, origen=?, estado=?, notas=? WHERE id_lead=? AND id_grupo=?"
    );

    foreach ($filas as $idx => $fila) {
        $numFila = $idx + 2;
        $v = validarLead($fila);
        if ($v['errors']) { $errores[] = "Fila $numFila: " . implode('; ', $v['errors']); continue; }

        try {
            $existenteId = null;
            if ($v['email'] !== null) {
                $sBuscarEmail->execute([$v['email'], $grupoId]);
                $existenteId = $sBuscarEmail->fetchColumn() ?: null;
            }
            if ($existenteId) {
                $sUpd->execute([$v['nombre'], $v['telefono'], $v['empresa'], $v['origen'], $v['estado'], $v['notas'], $existenteId, $grupoId]);
                registrarAuditoria($pdo, 'leads', (int) $existenteId, 'editar', null, $v);
                $actualizados++;
            } else {
                $sIns->execute([$v['nombre'], $v['email'], $v['telefono'], $v['empresa'], $v['origen'], $v['estado'], $v['notas'], $userId, $grupoId]);
                $nuevoId = (int) $pdo->lastInsertId();
                registrarAuditoria($pdo, 'leads', $nuevoId, 'crear', null, $v);
                $creados++;
            }
        } catch (PDOException $e) {
            $errores[] = "Fila $numFila: error de base de datos";
        }
    }

    ok(['creados' => $creados, 'actualizados' => $actualizados, 'errores' => $errores, 'total' => count($filas)]);
}

try {
    switch ($method) {

        // ─── Listar / buscar / detalle ────────────────────────────────────
        case 'GET':
            if (($_GET['action'] ?? '') === 'exportar') {
                exportarLeads($pdo, $grupoId);
                break;
            }
            if ($id) {
                $s = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ? AND id_grupo = ? LIMIT 1");
                $s->execute([$id, $grupoId]);
                $row = $s->fetch();
                $row ? ok($row) : err('Lead no encontrado', 404);
                break;
            }

            $where  = ['id_grupo = ?'];
            $params = [$grupoId];

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

            $origen = clean($_GET['origen'] ?? '');
            if ($origen !== '') {
                $where[]  = 'origen LIKE ?';
                $params[] = '%' . $origen . '%';
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

            $colsPermitidas = ['nombre', 'estado', 'created_at'];
            $orden = in_array($_GET['orden'] ?? '', $colsPermitidas, true) ? $_GET['orden'] : 'created_at';
            $dir   = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

            $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
            $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
            $offset = ($pagina - 1) * $limite;

            $clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $stCount = $pdo->prepare("SELECT COUNT(*) FROM leads$clausulaWhere");
            $stCount->execute($params);
            $total   = (int) $stCount->fetchColumn();
            $paginas = (int) ceil($total / $limite);

            $sql = "SELECT * FROM leads$clausulaWhere ORDER BY $orden $dir LIMIT ? OFFSET ?";
            $s = $pdo->prepare($sql);
            $s->execute([...$params, $limite, $offset]);
            ok($s->fetchAll(), compact('total', 'pagina', 'limite', 'paginas'));
            break;

        // ─── Crear ───────────────────────────────────────────────────────
        case 'POST':
            if (($_GET['action'] ?? '') === 'importar') {
                importarLeads($pdo, $userId, $grupoId);
                break;
            }
            $v = validarLead(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $s = $pdo->prepare(
                "INSERT INTO leads (nombre, email, telefono, empresa, origen, estado, notas, creado_por, id_grupo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $s->execute([$v['nombre'], $v['email'], $v['telefono'], $v['empresa'],
                         $v['origen'], $v['estado'], $v['notas'], $userId, $grupoId]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ? AND id_grupo = ?");
            $s2->execute([$newId, $grupoId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'leads', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Convertir a contacto ────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }

            if (isset($_GET['action']) && $_GET['action'] === 'convertir') {
                $sLead = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ? AND id_grupo = ?");
                $sLead->execute([$id, $grupoId]);
                $lead = $sLead->fetch() ?: null;
                if (!$lead) { err('Lead no encontrado', 404); break; }
                if ($lead['contacto_id'] !== null) { err('Este lead ya fue convertido a contacto'); break; }

                // Verificar email duplicado en contactos
                if ($lead['email'] !== null) {
                    $chk = $pdo->prepare("SELECT id_contacto FROM contactos WHERE email = ? AND id_grupo = ?");
                    $chk->execute([$lead['email'], $grupoId]);
                    if ($chk->fetch()) { err('Ya existe un contacto con el email ' . $lead['email']); break; }
                }

                $pdo->beginTransaction();
                try {
                    // Crear contacto
                    $ins = $pdo->prepare(
                        "INSERT INTO contactos (nombre, email, telefono, empresa, notas, creado_por, id_grupo)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $ins->execute([
                        $lead['nombre'], $lead['email'], $lead['telefono'],
                        $lead['empresa'], $lead['notas'], $userId, $grupoId,
                    ]);
                    $contactoId = (int) $pdo->lastInsertId();

                    // Vincular lead → contacto y marcar convertido
                    $upd = $pdo->prepare(
                        "UPDATE leads SET estado = 'convertido', contacto_id = ? WHERE id_lead = ? AND id_grupo = ?"
                    );
                    $upd->execute([$contactoId, $id, $grupoId]);

                    $pdo->commit();

                    // Leer registros actualizados
                    $sC = $pdo->prepare("SELECT id_contacto, nombre, email, telefono, empresa, notas, creado_por, created_at FROM contactos WHERE id_contacto = ?");
                    $sC->execute([$contactoId]);
                    $contacto = $sC->fetch();

                    $sL = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ?");
                    $sL->execute([$id]);
                    $leadActualizado = $sL->fetch();

                    registrarAuditoria($pdo, 'leads', $id, 'editar', $lead, $leadActualizado ?: null);
                    registrarAuditoria($pdo, 'contactos', $contactoId, 'crear', null, $contacto ?: null);

                    ok(['contacto' => $contacto, 'lead' => $leadActualizado]);
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    err('Error al convertir el lead', 500);
                }
                break;
            }

            // ─── Actualizar (edición normal) ─────────────────────────────
            $v = validarLead(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $sAntes = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Lead no encontrado', 404); break; }

            $s = $pdo->prepare(
                "UPDATE leads
                 SET nombre=?, email=?, telefono=?, empresa=?, origen=?, estado=?, notas=?
                 WHERE id_lead=? AND id_grupo=?"
            );
            $s->execute([$v['nombre'], $v['email'], $v['telefono'], $v['empresa'],
                         $v['origen'], $v['estado'], $v['notas'], $id, $grupoId]);
            $s2 = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ? AND id_grupo = ?");
            $s2->execute([$id, $grupoId]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'leads', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM leads WHERE id_lead = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Lead no encontrado', 404); break; }

            $s = $pdo->prepare("DELETE FROM leads WHERE id_lead = ? AND id_grupo = ?");
            $s->execute([$id, $grupoId]);
            registrarAuditoria($pdo, 'leads', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
