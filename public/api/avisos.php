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
$grupoId = obtenerIdGrupoActual();
$rol    = $_SESSION['rol'] ?? 'usuario';
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

const AVISO_TIPOS = ['info', 'exito', 'aviso', 'urgente'];

function validarAviso(array $b): array {
    $errors = [];

    $titulo = clean($b['titulo'] ?? '');
    if ($titulo === '') $errors[] = 'El título es obligatorio';
    elseif (strlen($titulo) > 150) $errors[] = 'El título no puede superar 150 caracteres';

    $mensaje = clean($b['mensaje'] ?? '');
    if ($mensaje === '') $errors[] = 'El mensaje es obligatorio';
    elseif (strlen($mensaje) > 2000) $errors[] = 'El mensaje no puede superar 2000 caracteres';

    $tipo = clean($b['tipo'] ?? 'info');
    if (!in_array($tipo, AVISO_TIPOS, true)) $errors[] = 'Tipo de aviso no válido';

    $destinatarios = $b['destinatarios'] ?? 'todos';
    $usuarioIds = null;
    if ($destinatarios !== 'todos') {
        if (!is_array($destinatarios) || empty($destinatarios)) {
            $errors[] = 'Selecciona al menos un destinatario';
        } else {
            $usuarioIds = array_values(array_unique(array_map('intval', $destinatarios)));
        }
    }

    return compact('errors', 'titulo', 'mensaje', 'tipo', 'usuarioIds');
}

// ─── Bandeja del usuario actual ─────────────────────────────────────────────
function cargarBandeja(PDO $pdo, int $userId, int $grupoId): void {
    $soloNoLeidos = ($_GET['no_leidos'] ?? '') === '1';
    $clausula = $soloNoLeidos ? 'AND ad.leido_at IS NULL' : '';

    $s = $pdo->prepare("
        SELECT a.id_aviso, a.titulo, a.mensaje, a.tipo, a.created_at, ad.leido_at,
               u.nombre AS remitente_nombre
        FROM avisos_destinatarios ad
        INNER JOIN avisos a ON a.id_aviso = ad.id_aviso
        INNER JOIN usuarios u ON u.id_usuario = a.creado_por
        WHERE ad.usuario_id = ? AND a.id_grupo = ? $clausula
        ORDER BY a.created_at DESC
        LIMIT 200
    ");
    $s->execute([$userId, $grupoId]);
    $filas = $s->fetchAll();

    $sTotal = $pdo->prepare("SELECT COUNT(*) FROM avisos_destinatarios WHERE usuario_id = ? AND leido_at IS NULL");
    $sTotal->execute([$userId]);
    $noLeidos = (int) $sTotal->fetchColumn();

    ok($filas, ['no_leidos' => $noLeidos]);
}

// ─── Historial de enviados (cualquier admin ve todos) ───────────────────────
function cargarEnviados(PDO $pdo, int $grupoId): void {
    $s = $pdo->prepare("
        SELECT a.id_aviso, a.titulo, a.mensaje, a.tipo, a.created_at,
               u.nombre AS remitente_nombre,
               COUNT(ad.usuario_id) AS total_destinatarios,
               SUM(CASE WHEN ad.leido_at IS NOT NULL THEN 1 ELSE 0 END) AS total_leidos
        FROM avisos a
        INNER JOIN usuarios u ON u.id_usuario = a.creado_por
        LEFT JOIN avisos_destinatarios ad ON ad.id_aviso = a.id_aviso
        WHERE a.id_grupo = ?
        GROUP BY a.id_aviso
        ORDER BY a.created_at DESC
        LIMIT 200
    ");
    $s->execute([$grupoId]);
    ok($s->fetchAll());
}

try {
    switch ($method) {

        case 'GET':
            if (($_GET['vista'] ?? '') === 'enviados') {
                if ($rol !== 'administrador') { err('Acceso restringido a administradores', 403); break; }
                cargarEnviados($pdo, $grupoId);
                break;
            }
            if (($_GET['vista'] ?? '') === 'usuarios') {
                if ($rol !== 'administrador') { err('Acceso restringido a administradores', 403); break; }
                $s = $pdo->prepare("SELECT id_usuario, nombre, rol FROM usuarios WHERE id_usuario != ? AND id_grupo = ? ORDER BY nombre");
                $s->execute([$userId, $grupoId]);
                ok($s->fetchAll());
                break;
            }
            if (($_GET['solo_conteo'] ?? '') === '1') {
                $s = $pdo->prepare("SELECT COUNT(*) FROM avisos_destinatarios WHERE usuario_id = ? AND leido_at IS NULL");
                $s->execute([$userId]);
                ok(['no_leidos' => (int) $s->fetchColumn()]);
                break;
            }
            cargarBandeja($pdo, $userId, $grupoId);
            break;

        // ─── Crear y enviar un aviso (admin) ────────────────────────────────
        case 'POST':
            if (($_GET['action'] ?? '') === 'marcar_todas_leidas') {
                $pdo->prepare("UPDATE avisos_destinatarios SET leido_at = NOW() WHERE usuario_id = ? AND leido_at IS NULL")
                    ->execute([$userId]);
                ok(['marcadas' => true]);
                break;
            }

            if ($rol !== 'administrador') { err('Solo los administradores pueden enviar avisos', 403); break; }

            $v = validarAviso(body());
            if ($v['errors']) { err(implode('; ', $v['errors'])); break; }

            // Resolver destinatarios: "todos" = todo el mundo excepto quien lo envia
            if ($v['usuarioIds'] === null) {
                $sDest = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario != ? AND id_grupo = ?");
                $sDest->execute([$userId, $grupoId]);
                $destinatarios = $sDest->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $placeholders = implode(',', array_fill(0, count($v['usuarioIds']), '?'));
                $sDest = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario IN ($placeholders) AND id_usuario != ? AND id_grupo = ?");
                $sDest->execute([...$v['usuarioIds'], $userId, $grupoId]);
                $destinatarios = $sDest->fetchAll(PDO::FETCH_COLUMN);
            }

            if (!$destinatarios) { err('No hay destinatarios válidos para este aviso'); break; }

            $pdo->beginTransaction();
            try {
                $sIns = $pdo->prepare("INSERT INTO avisos (titulo, mensaje, tipo, creado_por, id_grupo) VALUES (?, ?, ?, ?, ?)");
                $sIns->execute([$v['titulo'], $v['mensaje'], $v['tipo'], $userId, $grupoId]);
                $avisoId = (int) $pdo->lastInsertId();

                $sDestIns = $pdo->prepare("INSERT INTO avisos_destinatarios (id_aviso, usuario_id) VALUES (?, ?)");
                foreach ($destinatarios as $destId) {
                    $sDestIns->execute([$avisoId, $destId]);
                }

                registrarAuditoria($pdo, 'avisos', $avisoId, 'crear', null, [
                    'titulo' => $v['titulo'], 'tipo' => $v['tipo'], 'destinatarios' => count($destinatarios),
                ]);

                $pdo->commit();
                ok(['id_aviso' => $avisoId, 'destinatarios' => count($destinatarios)]);
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('[avisos] ' . $e->getMessage());
                err('No se pudo enviar el aviso', 500);
            }
            break;

        // ─── Marcar como leído (cualquier destinatario) ─────────────────────
        case 'PUT':
            if (!$id) { err('ID requerido'); break; }
            $s = $pdo->prepare("
                UPDATE avisos_destinatarios SET leido_at = NOW()
                WHERE id_aviso = ? AND usuario_id = ?
            ");
            $s->execute([$id, $userId]);
            if ($s->rowCount() === 0) { err('Aviso no encontrado', 404); break; }
            ok(['id_aviso' => $id, 'leido' => true]);
            break;

        // ─── Eliminar un aviso (admin) ───────────────────────────────────────
        case 'DELETE':
            if ($rol !== 'administrador') { err('Solo los administradores pueden eliminar avisos', 403); break; }
            if (!$id) { err('ID requerido'); break; }
            $sAntes = $pdo->prepare("SELECT * FROM avisos WHERE id_aviso = ? AND id_grupo = ?");
            $sAntes->execute([$id, $grupoId]);
            $antes = $sAntes->fetch();
            if (!$antes) { err('Aviso no encontrado', 404); break; }
            $pdo->prepare("DELETE FROM avisos WHERE id_aviso = ? AND id_grupo = ?")->execute([$id, $grupoId]);
            registrarAuditoria($pdo, 'avisos', $id, 'eliminar', $antes, null);
            ok(['deleted' => $id]);
            break;

        default:
            err('Método no permitido', 405);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    err('Error de base de datos', 500);
}
