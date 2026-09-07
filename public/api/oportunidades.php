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
verificarModuloVisible($pdo, 'oportunidades');
require __DIR__ . '/../config/csv_util.php';

$userId = (int) $_SESSION['user_id'];
$grupoId = obtenerIdGrupoActual();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

function ok($data): void { echo json_encode(['ok' => true, 'data' => $data]); }
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
}
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function clean(string $v): string { return trim($v); }
function nullOrStr(string $v): ?string { $v = clean($v); return $v !== '' ? $v : null; }

const ETAPAS_VALIDAS = ['prospecto', 'propuesta', 'negociacion', 'cerrada_ganada', 'cerrada_perdida'];

function validarOportunidad(array $b): array {
    $errors = [];

    $titulo = clean($b['titulo'] ?? '');
    if ($titulo === '')          $errors[] = 'El título es obligatorio';
    elseif (strlen($titulo) > 150) $errors[] = 'El título no puede superar 150 caracteres';

    $descripcion = nullOrStr($b['descripcion'] ?? '');
    if ($descripcion !== null && strlen($descripcion) > 500)
        $errors[] = 'La descripción no puede superar 500 caracteres';

    $valorRaw = trim($b['valor'] ?? '');
    $valor = null;
    if ($valorRaw !== '') {
        if (!is_numeric($valorRaw) || (float)$valorRaw < 0)
            $errors[] = 'El valor debe ser un número positivo';
        else
            $valor = (float)$valorRaw;
    }

    $etapa = clean($b['etapa'] ?? 'prospecto');
    if (!in_array($etapa, ETAPAS_VALIDAS, true))
        $errors[] = 'Etapa no válida';

    $fechaCierre = nullOrStr($b['fecha_cierre_esperada'] ?? '');
    if ($fechaCierre !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCierre))
        $errors[] = 'Formato de fecha inválido (YYYY-MM-DD)';

    $contactoId = isset($b['contacto_id']) && $b['contacto_id'] !== '' ? (int)$b['contacto_id'] : null;
    $leadId     = isset($b['lead_id'])     && $b['lead_id']     !== '' ? (int)$b['lead_id']     : null;

    return [
        'errors'               => $errors,
        'titulo'               => $titulo,
        'descripcion'          => $descripcion,
        'valor'                => $valor,
        'etapa'                => $etapa,
        'fecha_cierre_esperada'=> $fechaCierre,
        'contacto_id'          => $contactoId,
        'lead_id'              => $leadId,
    ];
}

// ─── Exportar / importar CSV ────────────────────────────────────────────────
function exportarOportunidades(PDO $pdo): void {
    $s = $pdo->query("
        SELECT o.titulo, o.descripcion, o.valor, o.etapa, o.fecha_cierre_esperada,
               c.email AS contacto_email, l.email AS lead_email, o.created_at
        FROM oportunidades o
        LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
        LEFT JOIN leads     l ON l.id_lead     = o.lead_id
        ORDER BY o.created_at DESC
    ");
    $filas = [];
    foreach ($s->fetchAll() as $o) {
        $filas[] = [$o['titulo'], $o['descripcion'], $o['valor'], $o['etapa'],
            $o['fecha_cierre_esperada'], $o['contacto_email'], $o['lead_email'], $o['created_at']];
    }
    csvDescargar('oportunidades_' . date('Y-m-d') . '.csv',
        ['titulo', 'descripcion', 'valor', 'etapa', 'fecha_cierre_esperada', 'contacto_email', 'lead_email', 'created_at'], $filas);
}

function importarOportunidades(PDO $pdo, int $userId): void {
    try {
        $filas = csvLeerSubida('archivo');
    } catch (RuntimeException $e) {
        err($e->getMessage());
        return;
    }
    if (!$filas) { err('El archivo CSV está vacío o no tiene un formato válido'); return; }

    $sContacto = $pdo->prepare("SELECT id_contacto FROM contactos WHERE email = ? LIMIT 1");
    $sLead     = $pdo->prepare("SELECT id_lead FROM leads WHERE email = ? LIMIT 1");
    $sIns = $pdo->prepare("
        INSERT INTO oportunidades (titulo, descripcion, valor, etapa, contacto_id, lead_id, fecha_cierre_esperada, creado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $creados = 0; $errores = [];
    foreach ($filas as $idx => $fila) {
        $numFila = $idx + 2;

        $contactoId = null;
        $email = trim($fila['contacto_email'] ?? '');
        if ($email !== '') {
            $sContacto->execute([$email]);
            $contactoId = $sContacto->fetchColumn() ?: null;
            if (!$contactoId) { $errores[] = "Fila $numFila: no existe ningún contacto con email $email"; continue; }
        }

        $leadId = null;
        $leadEmail = trim($fila['lead_email'] ?? '');
        if ($leadEmail !== '') {
            $sLead->execute([$leadEmail]);
            $leadId = $sLead->fetchColumn() ?: null;
            if (!$leadId) { $errores[] = "Fila $numFila: no existe ningún lead con email $leadEmail"; continue; }
        }

        $b = [
            'titulo'                => $fila['titulo'] ?? '',
            'descripcion'           => $fila['descripcion'] ?? '',
            'valor'                 => $fila['valor'] ?? '',
            'etapa'                 => $fila['etapa'] ?? 'prospecto',
            'fecha_cierre_esperada' => $fila['fecha_cierre_esperada'] ?? '',
            'contacto_id'           => $contactoId ?? '',
            'lead_id'               => $leadId ?? '',
        ];
        $v = validarOportunidad($b);
        if ($v['errors']) { $errores[] = "Fila $numFila: " . implode('; ', $v['errors']); continue; }

        try {
            $sIns->execute([
                $v['titulo'], $v['descripcion'], $v['valor'], $v['etapa'],
                $v['contacto_id'], $v['lead_id'], $v['fecha_cierre_esperada'], $userId,
            ]);
            $nuevoId = (int) $pdo->lastInsertId();
            registrarAuditoria($pdo, 'oportunidades', $nuevoId, 'crear', null, $v);
            $creados++;
        } catch (PDOException $e) {
            $errores[] = "Fila $numFila: error de base de datos";
        }
    }

    ok(['creados' => $creados, 'actualizados' => 0, 'errores' => $errores, 'total' => count($filas)]);
}

try {
    switch ($method) {

        // ─── Listar / detalle ─────────────────────────────────────────────────
        case 'GET':
            if (($_GET['action'] ?? '') === 'exportar') {
                exportarOportunidades($pdo);
                break;
            }
            if ($id) {
                $s = $pdo->prepare("
                    SELECT o.*,
                           c.nombre AS contacto_nombre,
                           l.nombre AS lead_nombre
                    FROM oportunidades o
                    LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                    LEFT JOIN leads     l ON l.id_lead     = o.lead_id
                    WHERE o.id_oportunidad = ?
                    LIMIT 1
                ");
                $s->execute([$id]);
                $row = $s->fetch();
                $row ? ok($row) : err('Oportunidad no encontrada', 404);
                break;
            }

            $where  = ['o.id_grupo = ?'];
            $params = [$grupoId];

            $buscar = clean($_GET['buscar'] ?? '');
            if ($buscar !== '') {
                $like    = '%' . $buscar . '%';
                $where[] = '(o.titulo LIKE ? OR o.descripcion LIKE ? OR c.nombre LIKE ? OR l.nombre LIKE ?)';
                array_push($params, $like, $like, $like, $like);
            }

            $etapa = clean($_GET['etapa'] ?? '');
            if ($etapa !== '' && in_array($etapa, ETAPAS_VALIDAS, true)) {
                $where[]  = 'o.etapa = ?';
                $params[] = $etapa;
            }

            $valorMin = trim($_GET['valor_min'] ?? '');
            if ($valorMin !== '' && is_numeric($valorMin) && (float)$valorMin >= 0) {
                $where[]  = 'o.valor >= ?';
                $params[] = (float)$valorMin;
            }

            $valorMax = trim($_GET['valor_max'] ?? '');
            if ($valorMax !== '' && is_numeric($valorMax) && (float)$valorMax >= 0) {
                $where[]  = 'o.valor <= ?';
                $params[] = (float)$valorMax;
            }

            $cierreDe = clean($_GET['cierre_desde'] ?? '');
            if ($cierreDe !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $cierreDe)) {
                $where[]  = 'o.fecha_cierre_esperada >= ?';
                $params[] = $cierreDe;
            }

            $cierreHa = clean($_GET['cierre_hasta'] ?? '');
            if ($cierreHa !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $cierreHa)) {
                $where[]  = 'o.fecha_cierre_esperada <= ?';
                $params[] = $cierreHa;
            }

            $colsPermitidas = ['titulo', 'valor', 'etapa', 'fecha_cierre_esperada', 'created_at'];
            $ordenRaw = $_GET['orden'] ?? '';
            $orden = in_array($ordenRaw, $colsPermitidas, true) ? $ordenRaw : 'created_at';
            $dir   = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

            $sql = "SELECT o.*,
                           c.nombre AS contacto_nombre,
                           l.nombre AS lead_nombre
                    FROM oportunidades o
                    LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                    LEFT JOIN leads     l ON l.id_lead     = o.lead_id"
                 . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
                 . " ORDER BY o.$orden $dir";
            $s = $pdo->prepare($sql);
            $s->execute($params);
            ok($s->fetchAll());
            break;

        // ─── Crear ───────────────────────────────────────────────────────────
        case 'POST':
            if (($_GET['action'] ?? '') === 'importar') {
                importarOportunidades($pdo, $userId);
                break;
            }
            $v = validarOportunidad(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            if (($v['contacto_id'] && !recursoPerteneceAlGrupo($pdo, 'contactos', 'id_contacto', $v['contacto_id'], $grupoId)) ||
                ($v['lead_id'] && !recursoPerteneceAlGrupo($pdo, 'leads', 'id_lead', $v['lead_id'], $grupoId))) {
                err('El contacto o lead no pertenece a tu grupo.', 403); break;
            }

            $s = $pdo->prepare("
                INSERT INTO oportunidades
                    (titulo, descripcion, valor, etapa, contacto_id, lead_id, fecha_cierre_esperada, creado_por, id_grupo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s->execute([
                $v['titulo'], $v['descripcion'], $v['valor'], $v['etapa'],
                $v['contacto_id'], $v['lead_id'], $v['fecha_cierre_esperada'], $userId, $grupoId
            ]);
            $newId = (int) $pdo->lastInsertId();
            $s2 = $pdo->prepare("SELECT o.*, c.nombre AS contacto_nombre, l.nombre AS lead_nombre
                FROM oportunidades o
                LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                LEFT JOIN leads l ON l.id_lead = o.lead_id
                WHERE o.id_oportunidad = ?");
            $s2->execute([$newId]);
            $nuevo = $s2->fetch();
            registrarAuditoria($pdo, 'oportunidades', $newId, 'crear', null, $nuevo ?: null);
            ok($nuevo);
            break;

        // ─── Actualizar / mover etapa ─────────────────────────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }

            // Mover etapa
            if (isset($_GET['action']) && $_GET['action'] === 'etapa') {
                $b = body();
                $nuevaEtapa = clean($b['etapa'] ?? '');
                if (!in_array($nuevaEtapa, ETAPAS_VALIDAS, true)) {
                    err('Etapa no válida'); break;
                }
                $sAntes = $pdo->prepare("SELECT * FROM oportunidades WHERE id_oportunidad = ? AND id_grupo = ?");
                $sAntes->execute([$id, $grupoId]);
                $antes = $sAntes->fetch() ?: null;
                if (!$antes) { err('Oportunidad no encontrada', 404); break; }

                $pdo->prepare("UPDATE oportunidades SET etapa = ? WHERE id_oportunidad = ? AND id_grupo = ?")
                    ->execute([$nuevaEtapa, $id, $grupoId]);

                $s2 = $pdo->prepare("SELECT o.*, c.nombre AS contacto_nombre, l.nombre AS lead_nombre
                    FROM oportunidades o
                    LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                    LEFT JOIN leads l ON l.id_lead = o.lead_id
                    WHERE o.id_oportunidad = ?");
                $s2->execute([$id]);
                $despues = $s2->fetch() ?: null;
                registrarAuditoria($pdo, 'oportunidades', $id, 'editar', $antes, $despues);
                ok($despues);
                break;
            }

            // Edición completa
            $v = validarOportunidad(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }
            if (($v['contacto_id'] && !recursoPerteneceAlGrupo($pdo, 'contactos', 'id_contacto', $v['contacto_id'], $grupoId)) ||
                ($v['lead_id'] && !recursoPerteneceAlGrupo($pdo, 'leads', 'id_lead', $v['lead_id'], $grupoId))) {
                err('El contacto o lead no pertenece a tu grupo.', 403); break;
            }

            $sAntes = $pdo->prepare("SELECT * FROM oportunidades WHERE id_oportunidad = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Oportunidad no encontrada', 404); break; }

            $pdo->prepare("
                UPDATE oportunidades
                SET titulo=?, descripcion=?, valor=?, etapa=?,
                    contacto_id=?, lead_id=?, fecha_cierre_esperada=?
                WHERE id_oportunidad=? AND id_grupo=?
            ")->execute([
                $v['titulo'], $v['descripcion'], $v['valor'], $v['etapa'],
                $v['contacto_id'], $v['lead_id'], $v['fecha_cierre_esperada'], $id, $grupoId
            ]);

            $s2 = $pdo->prepare("SELECT o.*, c.nombre AS contacto_nombre, l.nombre AS lead_nombre
                FROM oportunidades o
                LEFT JOIN contactos c ON c.id_contacto = o.contacto_id
                LEFT JOIN leads l ON l.id_lead = o.lead_id
                WHERE o.id_oportunidad = ?");
            $s2->execute([$id]);
            $despues = $s2->fetch() ?: null;
            registrarAuditoria($pdo, 'oportunidades', $id, 'editar', $antes, $despues);
            ok($despues);
            break;

        // ─── Eliminar ────────────────────────────────────────────────────────
        case 'DELETE':
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM oportunidades WHERE id_oportunidad = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch() ?: null;
            if (!$antes) { err('Oportunidad no encontrada', 404); break; }

            $pdo->prepare("DELETE FROM oportunidades WHERE id_oportunidad = ? AND id_grupo = ?")->execute([$id, $grupoId]);
            registrarAuditoria($pdo, 'oportunidades', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    err('Error de base de datos', 500);
}
