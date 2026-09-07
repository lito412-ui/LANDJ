<?php
/**
 * Página pública de confirmación de presupuestos.
 * NO requiere sesión: el token largo y aleatorio de la URL es la única
 * credencial. GET solo muestra el presupuesto (nunca cambia nada, para
 * evitar que un escáner de email "confirme" el presupuesto sin querer).
 * POST es la única forma de aceptar/rechazar.
 */

require __DIR__ . '/config/conexion.php';
require __DIR__ . '/config/auditoria.php';

function paginaMensaje(string $titulo, string $mensaje, string $icono = 'info', int $httpCode = 200): void {
    http_response_code($httpCode);
    $colores = [
        'info'   => ['#eff6ff', '#1d4ed8', 'fa-circle-info'],
        'exito'  => ['#f0fdf4', '#15803d', 'fa-circle-check'],
        'error'  => ['#fef2f2', '#b91c1c', 'fa-circle-xmark'],
        'aviso'  => ['#fffbeb', '#92400e', 'fa-triangle-exclamation'],
    ];
    [$bg, $color, $icon] = $colores[$icono] ?? $colores['info'];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?> — L&amp;J CRM</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
           background:#f4f5f7; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; padding:20px; }
    .card { background:#fff; border-radius:16px; padding:40px 32px; max-width:420px; width:100%;
            text-align:center; box-shadow:0 10px 30px rgba(0,0,0,.08); }
    .icono { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center;
             margin:0 auto 20px; background:<?= $bg ?>; color:<?= $color ?>; font-size:28px; }
    h1 { font-size:20px; margin:0 0 12px; color:#111827; }
    p { color:#6b7280; line-height:1.6; margin:0; font-size:14px; }
</style>
</head>
<body>
    <div class="card">
        <div class="icono"><i class="fas <?= $icon ?>"></i></div>
        <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= $mensaje ?></p>
    </div>
</body>
</html>
    <?php
    exit;
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    paginaMensaje('Enlace no válido', 'El enlace de confirmación no es correcto. Revisa que lo hayas copiado completo.', 'error', 400);
}

$s = $pdo->prepare("
    SELECT p.*, c.nombre AS contacto_nombre, c.apellidos AS contacto_apellidos, c.email AS contacto_email
    FROM presupuestos p
    INNER JOIN contactos c ON c.id_contacto = p.contacto_id
    WHERE p.token_confirmacion = ?
    LIMIT 1
");
$s->execute([$token]);
$presupuesto = $s->fetch();

if (!$presupuesto) {
    paginaMensaje('Enlace no válido', 'No hemos encontrado ningún presupuesto asociado a este enlace.', 'error', 404);
}

$numero = htmlspecialchars($presupuesto['numero'], ENT_QUOTES, 'UTF-8');

// Ya convertido en factura: no se puede cambiar de decision
if ($presupuesto['factura_id']) {
    paginaMensaje(
        'Presupuesto ya facturado',
        "El presupuesto <strong>{$numero}</strong> ya fue aceptado y convertido en factura. Si necesitas el documento, contacta con nosotros.",
        'info'
    );
}

// Ya confirmado antes (aceptar o rechazar es de un solo uso)
if ($presupuesto['token_confirmado_at']) {
    $decision = $presupuesto['estado'] === 'aceptado' ? 'aceptaste' : 'rechazaste';
    paginaMensaje(
        'Ya has respondido',
        "Ya {$decision} el presupuesto <strong>{$numero}</strong> el "
            . date('d/m/Y \a \l\a\s H:i', strtotime($presupuesto['token_confirmado_at'])) . '.',
        'info'
    );
}

// Presupuesto caducado
if ($presupuesto['fecha_validez'] && $presupuesto['fecha_validez'] < date('Y-m-d')) {
    paginaMensaje(
        'Presupuesto caducado',
        "El presupuesto <strong>{$numero}</strong> caducó el " . date('d/m/Y', strtotime($presupuesto['fecha_validez']))
            . '. Ponte en contacto con nosotros para solicitar uno actualizado.',
        'aviso'
    );
}

// ─── POST: procesar la decision del cliente ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    if (!in_array($accion, ['aceptar', 'rechazar'], true)) {
        paginaMensaje('Acción no válida', 'No hemos entendido tu respuesta. Vuelve a intentarlo desde el email.', 'error', 400);
    }

    $nuevoEstado = $accion === 'aceptar' ? 'aceptado' : 'rechazado';

    $pdo->prepare("
        UPDATE presupuestos
           SET estado = ?, token_confirmado_at = NOW()
         WHERE id_presupuesto = ? AND token_confirmacion = ?
    ")->execute([$nuevoEstado, $presupuesto['id_presupuesto'], $token]);

    registrarAuditoria(
        $pdo,
        'presupuestos',
        (int) $presupuesto['id_presupuesto'],
        'editar',
        ['estado' => $presupuesto['estado']],
        ['estado' => $nuevoEstado, 'confirmado_por_cliente' => true]
    );

    if ($nuevoEstado === 'aceptado') {
        paginaMensaje(
            '¡Presupuesto aceptado!',
            "Gracias, hemos registrado tu aceptación del presupuesto <strong>{$numero}</strong>. "
                . 'En breve recibirás la factura correspondiente.',
            'exito'
        );
    }

    paginaMensaje(
        'Presupuesto rechazado',
        "Hemos registrado tu respuesta sobre el presupuesto <strong>{$numero}</strong>. "
            . 'Si quieres comentarnos algo o solicitar cambios, responde a este email.',
        'aviso'
    );
}

// ─── GET: mostrar el presupuesto con los dos botones ────────────────────────
$l = $pdo->prepare("SELECT * FROM presupuesto_lineas WHERE presupuesto_id = ? ORDER BY orden, id_linea");
$l->execute([$presupuesto['id_presupuesto']]);
$lineas = $l->fetchAll();

$contacto = trim(($presupuesto['contacto_nombre'] ?? '') . ' ' . ($presupuesto['contacto_apellidos'] ?? ''));
$total = number_format((float) $presupuesto['total'], 2, ',', '.') . ' EUR';
$validez = $presupuesto['fecha_validez'] ? date('d/m/Y', strtotime($presupuesto['fecha_validez'])) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Presupuesto <?= $numero ?> — L&amp;J CRM</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    * { box-sizing: border-box; }
    body { margin:0; min-height:100vh; background:#f4f5f7; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;
           padding:20px; display:flex; align-items:center; justify-content:center; }
    .card { background:#fff; border-radius:16px; max-width:480px; width:100%; box-shadow:0 10px 30px rgba(0,0,0,.08);
            overflow:hidden; }
    .card-header { padding:28px 28px 20px; border-bottom:1px solid #f1f5f9; }
    .card-header span { color:#6b7280; font-size:13px; }
    .card-header h1 { margin:6px 0 0; font-size:22px; color:#111827; }
    .card-body { padding:24px 28px; }
    .fila { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f8fafc; font-size:14px; }
    .fila:last-child { border-bottom:none; }
    .fila span:first-child { color:#6b7280; }
    .fila span:last-child { color:#111827; font-weight:600; text-align:right; max-width:60%; }
    .lineas { margin:16px 0; }
    .linea-item { display:flex; justify-content:space-between; padding:8px 0; font-size:13px; color:#374151; }
    .total-box { background:#f9fafb; border-radius:10px; padding:16px; margin:16px 0; text-align:center; }
    .total-box span { display:block; color:#6b7280; font-size:12px; }
    .total-box strong { font-size:28px; color:#111827; }
    .validez { text-align:center; color:#9a3412; font-size:12px; margin-top:4px; }
    .acciones { display:flex; gap:12px; padding:0 28px 28px; }
    .btn { flex:1; padding:14px; border-radius:10px; border:none; font-size:15px; font-weight:600;
           cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; }
    .btn-aceptar { background:#16a34a; color:#fff; }
    .btn-aceptar:hover { background:#15803d; }
    .btn-rechazar { background:#f1f5f9; color:#475569; }
    .btn-rechazar:hover { background:#e2e8f0; }
</style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <span>Presupuesto de L&amp;J CRM</span>
            <h1><?= $numero ?></h1>
        </div>
        <div class="card-body">
            <div class="fila"><span>Para</span><span><?= htmlspecialchars($contacto, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="fila"><span>Fecha</span><span><?= date('d/m/Y', strtotime($presupuesto['fecha_emision'])) ?></span></div>
            <?php if ($validez): ?>
            <div class="fila"><span>Válido hasta</span><span><?= $validez ?></span></div>
            <?php endif; ?>

            <div class="lineas">
                <?php foreach ($lineas as $linea): ?>
                <div class="linea-item">
                    <span><?= htmlspecialchars($linea['concepto'], ENT_QUOTES, 'UTF-8') ?> (x<?= rtrim(rtrim(number_format($linea['cantidad'], 2, ',', '.'), '0'), ',') ?>)</span>
                    <span><?= number_format((float) $linea['total_linea'], 2, ',', '.') ?> EUR</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="total-box">
                <span>Total</span>
                <strong><?= $total ?></strong>
            </div>
        </div>
        <form method="POST" class="acciones">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" name="accion" value="rechazar" class="btn btn-rechazar">
                <i class="fas fa-xmark"></i> Rechazar
            </button>
            <button type="submit" name="accion" value="aceptar" class="btn btn-aceptar">
                <i class="fas fa-check"></i> Aceptar presupuesto
            </button>
        </form>
    </div>
</body>
</html>
