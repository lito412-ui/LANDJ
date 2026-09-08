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

$grupoId = obtenerIdGrupoActual();
$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function respuestaPlantilla(bool $ok, $data = null, ?string $error = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode($ok ? ['ok' => true, 'data' => $data] : ['ok' => false, 'error' => $error]);
}

function datosPlantilla(array $input): array {
    $nombre = trim((string) ($input['nombre'] ?? ''));
    $logo = trim((string) ($input['logo_url'] ?? ''));
    $primario = strtoupper(trim((string) ($input['color_primario'] ?? '#1D4ED8')));
    $secundario = strtoupper(trim((string) ($input['color_secundario'] ?? '#EFF6FF')));
    $fuente = trim((string) ($input['fuente'] ?? 'Helvetica'));
    $pie = trim((string) ($input['texto_pie'] ?? ''));

    $errores = [];
    if ($nombre === '' || mb_strlen($nombre) > 100) $errores[] = 'Indica un nombre de hasta 100 caracteres';
    if ($logo !== '' && (!filter_var($logo, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $logo))) {
        $errores[] = 'El logo debe ser una URL HTTPS válida';
    }
    if (!preg_match('/^#[0-9A-F]{6}$/', $primario) || !preg_match('/^#[0-9A-F]{6}$/', $secundario)) {
        $errores[] = 'Los colores deben tener formato hexadecimal';
    }
    if (!in_array($fuente, ['Helvetica', 'Times-Roman', 'Courier'], true)) $errores[] = 'Fuente no válida';
    if (mb_strlen($pie) > 500) $errores[] = 'El pie no puede superar 500 caracteres';

    return compact('nombre', 'logo', 'primario', 'secundario', 'fuente', 'pie', 'errores');
}

try {
    if ($method === 'GET') {
        $s = $pdo->prepare('SELECT * FROM plantillas_factura WHERE id_grupo = ? ORDER BY nombre');
        $s->execute([$grupoId]);
        respuestaPlantilla(true, $s->fetchAll());
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if ($method === 'POST' || $method === 'PUT') {
        $d = datosPlantilla($input);
        if ($d['errores']) { respuestaPlantilla(false, null, implode('; ', $d['errores']), 422); exit; }

        if ($method === 'POST') {
            $s = $pdo->prepare('INSERT INTO plantillas_factura (id_grupo, nombre, logo_url, color_primario, color_secundario, fuente, texto_pie, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $s->execute([$grupoId, $d['nombre'], $d['logo'] ?: null, $d['primario'], $d['secundario'], $d['fuente'], $d['pie'] ?: null, $userId]);
            $nuevoId = (int) $pdo->lastInsertId();
            registrarAuditoria($pdo, 'plantillas_factura', $nuevoId, 'crear', null, $d);
            respuestaPlantilla(true, ['id_plantilla' => $nuevoId]);
            exit;
        }

        if (!$id) { respuestaPlantilla(false, null, 'ID requerido', 400); exit; }
        $anterior = $pdo->prepare('SELECT * FROM plantillas_factura WHERE id_plantilla = ? AND id_grupo = ?');
        $anterior->execute([$id, $grupoId]);
        $antes = $anterior->fetch();
        if (!$antes) { respuestaPlantilla(false, null, 'Modelo no encontrado', 404); exit; }
        $s = $pdo->prepare('UPDATE plantillas_factura SET nombre=?, logo_url=?, color_primario=?, color_secundario=?, fuente=?, texto_pie=? WHERE id_plantilla=? AND id_grupo=?');
        $s->execute([$d['nombre'], $d['logo'] ?: null, $d['primario'], $d['secundario'], $d['fuente'], $d['pie'] ?: null, $id, $grupoId]);
        registrarAuditoria($pdo, 'plantillas_factura', $id, 'editar', $antes, $d);
        respuestaPlantilla(true, ['id_plantilla' => $id]);
        exit;
    }

    if ($method === 'DELETE') {
        if (!$id) { respuestaPlantilla(false, null, 'ID requerido', 400); exit; }
        $s = $pdo->prepare('SELECT * FROM plantillas_factura WHERE id_plantilla = ? AND id_grupo = ?');
        $s->execute([$id, $grupoId]);
        $antes = $s->fetch();
        if (!$antes) { respuestaPlantilla(false, null, 'Modelo no encontrado', 404); exit; }
        // Las facturas conservan su instantánea; solo se desasocia la plantilla activa.
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE facturas SET plantilla_id = NULL WHERE plantilla_id = ? AND id_grupo = ?')->execute([$id, $grupoId]);
        $pdo->prepare('DELETE FROM plantillas_factura WHERE id_plantilla = ? AND id_grupo = ?')->execute([$id, $grupoId]);
        registrarAuditoria($pdo, 'plantillas_factura', $id, 'eliminar', $antes, null);
        $pdo->commit();
        respuestaPlantilla(true, ['deleted' => $id]);
        exit;
    }

    respuestaPlantilla(false, null, 'Método no permitido', 405);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[plantillas_factura] ' . $e->getMessage());
    respuestaPlantilla(false, null, 'Error de base de datos', 500);
}
