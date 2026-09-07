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
verificarModuloVisible($pdo, 'contactos');
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

// ─── Exportar / importar CSV ────────────────────────────────────────────────
// ─── Detección de duplicados ────────────────────────────────────────────────
// El email tiene UNIQUE en BD: un duplicado por email exacto nunca se puede
// forzar (violaria la restriccion), asi que se separa de los duplicados por
// telefono (señal mas debil, esos si se pueden forzar).
function buscarPosiblesDuplicados(PDO $pdo, int $grupoId, ?string $email, ?string $telefono): array {
    $porEmail = [];
    if ($email !== null) {
        $s = $pdo->prepare("SELECT id_contacto, nombre, apellidos, email, telefono, empresa FROM contactos WHERE email = ? AND id_grupo = ? LIMIT 1");
        $s->execute([$email, $grupoId]);
        $porEmail = $s->fetchAll();
    }

    $porTelefono = [];
    if ($telefono !== null && $telefono !== '') {
        $telefonoNormalizado = preg_replace('/[\s\-]/', '', $telefono);
        if ($telefonoNormalizado !== '') {
            $s = $pdo->prepare(
                "SELECT id_contacto, nombre, apellidos, email, telefono, empresa
                 FROM contactos WHERE REPLACE(REPLACE(telefono, ' ', ''), '-', '') = ? AND id_grupo = ? LIMIT 5"
            );
            $s->execute([$telefonoNormalizado, $grupoId]);
            $porTelefono = $s->fetchAll();
        }
    }

    return ['email' => $porEmail, 'telefono' => $porTelefono];
}

function exportarContactos(PDO $pdo, int $grupoId): void {
    $s = $pdo->prepare("SELECT nombre, apellidos, email, telefono, empresa, notas, created_at FROM contactos WHERE id_grupo = ? ORDER BY nombre, apellidos");
    $s->execute([$grupoId]);
    $filas = [];
    foreach ($s->fetchAll() as $c) {
        $filas[] = [$c['nombre'], $c['apellidos'], $c['email'], $c['telefono'], $c['empresa'], $c['notas'], $c['created_at']];
    }
    csvDescargar('contactos_' . date('Y-m-d') . '.csv',
        ['nombre', 'apellidos', 'email', 'telefono', 'empresa', 'notas', 'created_at'], $filas);
}

function importarContactos(PDO $pdo, int $userId, int $grupoId): void {
    try {
        $filas = csvLeerSubida('archivo');
    } catch (RuntimeException $e) {
        err($e->getMessage());
        return;
    }
    if (!$filas) { err('El archivo CSV está vacío o no tiene un formato válido'); return; }

    $creados = 0; $actualizados = 0; $errores = [];
    $sBuscarEmail = $pdo->prepare("SELECT id_contacto FROM contactos WHERE email = ? AND id_grupo = ? LIMIT 1");
    $sBuscarTelefono = $pdo->prepare(
        "SELECT id_contacto FROM contactos WHERE REPLACE(REPLACE(telefono, ' ', ''), '-', '') = ? AND id_grupo = ? LIMIT 1"
    );
    $sIns = $pdo->prepare(
        "INSERT INTO contactos (nombre, apellidos, email, telefono, empresa, notas, creado_por, id_grupo)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $sUpd = $pdo->prepare(
        "UPDATE contactos SET nombre=?, apellidos=?, telefono=?, empresa=?, notas=? WHERE id_contacto=? AND id_grupo=?"
    );

    foreach ($filas as $idx => $fila) {
        $numFila = $idx + 2; // +1 cabecera, +1 base 1
        $v = validarYSanitizar($fila);
        if ($v['errors']) { $errores[] = "Fila $numFila: " . implode('; ', $v['errors']); continue; }

        try {
            $existenteId = null;
            if ($v['email'] !== null) {
                $sBuscarEmail->execute([$v['email'], $grupoId]);
                $existenteId = $sBuscarEmail->fetchColumn() ?: null;
            }
            // Sin coincidencia por email: probar por teléfono (mismo criterio que la deteccion manual)
            if (!$existenteId && $v['telefono'] !== null) {
                $telefonoNormalizado = preg_replace('/[\s\-]/', '', $v['telefono']);
                if ($telefonoNormalizado !== '') {
                    $sBuscarTelefono->execute([$telefonoNormalizado, $grupoId]);
                    $existenteId = $sBuscarTelefono->fetchColumn() ?: null;
                }
            }
            if ($existenteId) {
                $sUpd->execute([$v['nombre'], $v['apellidos'], $v['telefono'], $v['empresa'], $v['notas'], $existenteId, $grupoId]);
                registrarAuditoria($pdo, 'contactos', (int) $existenteId, 'editar', null, $v);
                $actualizados++;
            } else {
                $sIns->execute([$v['nombre'], $v['apellidos'], $v['email'], $v['telefono'], $v['empresa'], $v['notas'], $userId, $grupoId]);
                $nuevoId = (int) $pdo->lastInsertId();
                registrarAuditoria($pdo, 'contactos', $nuevoId, 'crear', null, $v);
                $creados++;
            }
        } catch (PDOException $e) {
            $errores[] = "Fila $numFila: " . ($e->getCode() === '23000' ? 'email duplicado' : 'error de base de datos');
        }
    }

    ok(['creados' => $creados, 'actualizados' => $actualizados, 'errores' => $errores, 'total' => count($filas)]);
}

try {
    switch ($method) {

        // ─── Listar / buscar / detalle ────────────────────────────────────
        case 'GET':
            if (($_GET['action'] ?? '') === 'exportar') {
                exportarContactos($pdo, $grupoId);
                break;
            }
            if ($id) {
                $s = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ? AND id_grupo = ? LIMIT 1");
                $s->execute([$id, $grupoId]);
                $row = $s->fetch();
                $row ? ok($row) : err('Contacto no encontrado', 404);
            } else {
                $where  = ['id_grupo = ?'];
                $params = [$grupoId];

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

                $limite = min(max((int) ($_GET['limite'] ?? 20), 1), 100);
                $pagina = max((int) ($_GET['pagina'] ?? 1), 1);
                $offset = ($pagina - 1) * $limite;

                $clausulaWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

                $stCount = $pdo->prepare("SELECT COUNT(*) FROM contactos$clausulaWhere");
                $stCount->execute($params);
                $total   = (int) $stCount->fetchColumn();
                $paginas = (int) ceil($total / $limite);

                $sql = "SELECT * FROM contactos$clausulaWhere ORDER BY $orden $dir LIMIT ? OFFSET ?";
                $s = $pdo->prepare($sql);
                $s->execute([...$params, $limite, $offset]);
                ok($s->fetchAll(), compact('total', 'pagina', 'limite', 'paginas'));
            }
            break;

        // ─── Crear ───────────────────────────────────────────────────────
        case 'POST':
            if (($_GET['action'] ?? '') === 'importar') {
                importarContactos($pdo, $userId, $grupoId);
                break;
            }
            $v = validarYSanitizar(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            $duplicados = buscarPosiblesDuplicados($pdo, $grupoId, $v['email'], $v['telefono']);
            if ($duplicados['email']) {
                http_response_code(409);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Ya existe un contacto con ese email',
                    'duplicados' => $duplicados['email'],
                    'forzable' => false,
                ]);
                break;
            }
            if ($duplicados['telefono'] && ($_GET['forzar'] ?? '') !== '1') {
                http_response_code(409);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Posible contacto duplicado (mismo teléfono)',
                    'duplicados' => $duplicados['telefono'],
                    'forzable' => true,
                ]);
                break;
            }

            $s = $pdo->prepare(
                "INSERT INTO contactos (nombre, apellidos, email, telefono, empresa, notas, creado_por, id_grupo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $s->execute([$v['nombre'], $v['apellidos'], $v['email'], $v['telefono'], $v['empresa'], $v['notas'], $userId, $grupoId]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $s2->execute([$newId, $grupoId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'contactos', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Actualizar ──────────────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $v = validarYSanitizar(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            $sAntes = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Contacto no encontrado', 404); break; }
            $s = $pdo->prepare(
                "UPDATE contactos
                 SET nombre=?, apellidos=?, email=?, telefono=?, empresa=?, notas=?
                 WHERE id_contacto=? AND id_grupo=?"
            );
            $s->execute([$v['nombre'], $v['apellidos'], $v['email'], $v['telefono'], $v['empresa'], $v['notas'], $id, $grupoId]);
            $s2 = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $s2->execute([$id, $grupoId]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'contactos', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Contacto no encontrado', 404); break; }
            $s = $pdo->prepare("DELETE FROM contactos WHERE id_contacto = ? AND id_grupo = ?");
            $s->execute([$id, $grupoId]);
            registrarAuditoria($pdo, 'contactos', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
