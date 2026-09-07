<?php
/**
 * Recordatorios automaticos de cobro.
 *
 * Pensado para ejecutarse periodicamente (ver servicio "cron" en
 * docker-compose.yml). Cada ejecucion:
 *
 *   1. Marca como "vencida" cualquier factura "emitida" cuya fecha de
 *      vencimiento ya haya pasado.
 *   2. Para facturas vencidas sin recordatorio reciente (> 7 dias), envia
 *      un email de recordatorio de pago al contacto y crea una tarea
 *      interna (actividad con recordatorio) para que el comercial haga
 *      seguimiento. La tarea aparece automaticamente en la campana de
 *      notificaciones del panel, sin necesidad de UI nueva.
 *   3. Para facturas que vencen en los proximos 3 dias (todavia "emitida"),
 *      crea una tarea interna de aviso previo (una sola vez por factura).
 *
 * Uso: php database/tareas/recordatorios_facturas.php
 */

require __DIR__ . '/../../public/config/conexion.php';
require __DIR__ . '/../../public/config/mailer.php';
require __DIR__ . '/../../public/config/facturas_documentos.php';

const DIAS_ENTRE_RECORDATORIOS = 7;
const DIAS_AVISO_PREVIO = 3;

function log_linea(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . "] $msg\n";
}

function crearTareaInterna(PDO $pdo, int $creadoPor, ?int $contactoId, string $descripcion): void {
    // Evita duplicar la misma tarea si ya existe una pendiente con el mismo texto
    $sChk = $pdo->prepare("
        SELECT id_actividad FROM actividades
        WHERE creado_por = ? AND descripcion = ? AND completada = 0
        LIMIT 1
    ");
    $sChk->execute([$creadoPor, $descripcion]);
    if ($sChk->fetchColumn()) {
        return;
    }

    $pdo->prepare("
        INSERT INTO actividades (tipo, descripcion, fecha, recordatorio_at, contacto_id, creado_por)
        VALUES ('tarea', ?, NOW(), NOW(), ?, ?)
    ")->execute([$descripcion, $contactoId, $creadoPor]);
}

try {
    log_linea('Iniciando recordatorios de cobro...');

    // 1) Marcar como vencidas las facturas emitidas cuya fecha ya paso
    $sVencer = $pdo->prepare("
        UPDATE facturas SET estado = 'vencida'
        WHERE estado = 'emitida' AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE()
    ");
    $sVencer->execute();
    $marcadas = $sVencer->rowCount();
    log_linea("Facturas marcadas como vencidas: $marcadas");

    // 2) Facturas vencidas que necesitan recordatorio (email + tarea interna)
    $sPendientes = $pdo->query("
        SELECT id_factura FROM facturas
        WHERE estado = 'vencida'
          AND (recordatorio_enviado_at IS NULL
               OR recordatorio_enviado_at < DATE_SUB(NOW(), INTERVAL " . DIAS_ENTRE_RECORDATORIOS . " DAY))
        ORDER BY fecha_vencimiento ASC
    ");
    $idsPendientes = $sPendientes->fetchAll(PDO::FETCH_COLUMN);
    log_linea('Facturas vencidas pendientes de recordatorio: ' . count($idsPendientes));

    $enviados = 0;
    $procesados = 0;
    $sMarcarEnviado = $pdo->prepare("UPDATE facturas SET recordatorio_enviado_at = NOW() WHERE id_factura = ?");

    foreach ($idsPendientes as $facturaId) {
        $factura = facturaDocumentoCargar($pdo, (int) $facturaId);
        if (!$factura) continue;

        $emailOk = false;
        if (!empty($factura['contacto_email']) && filter_var($factura['contacto_email'], FILTER_VALIDATE_EMAIL)) {
            try {
                $pdf = facturaDocumentoPdf($factura);
                $emailOk = enviarEmail(
                    $factura['contacto_email'],
                    'Recordatorio de pago - Factura ' . $factura['numero'],
                    facturaDocumentoHtmlRecordatorio($factura),
                    [[
                        'data' => $pdf,
                        'name' => facturaDocumentoNombre($factura),
                        'encoding' => 'base64',
                        'type' => 'application/pdf',
                    ]]
                );
            } catch (Throwable $e) {
                log_linea('  [aviso] No se pudo enviar el email de la factura ' . $factura['numero'] . ': ' . $e->getMessage());
            }
        }

        crearTareaInterna(
            $pdo,
            (int) $factura['creado_por'],
            (int) $factura['contacto_id'],
            'Factura ' . $factura['numero'] . ' vencida (' . facturaDocumentoMoneda($factura['total']) . '). Hacer seguimiento de cobro.'
        );

        $sMarcarEnviado->execute([$facturaId]);
        $procesados++;
        if ($emailOk) $enviados++;
        log_linea('  [ok] ' . $factura['numero'] . ' - email ' . ($emailOk ? 'enviado' : 'no enviado') . ', tarea interna creada');
    }

    // 3) Aviso previo para facturas que vencen pronto (todavia no vencidas)
    $sProximas = $pdo->prepare("
        SELECT id_factura FROM facturas
        WHERE estado = 'emitida'
          AND fecha_vencimiento IS NOT NULL
          AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY fecha_vencimiento ASC
    ");
    $sProximas->execute([DIAS_AVISO_PREVIO]);
    $idsProximas = $sProximas->fetchAll(PDO::FETCH_COLUMN);
    log_linea('Facturas por vencer en los proximos ' . DIAS_AVISO_PREVIO . ' dias: ' . count($idsProximas));

    foreach ($idsProximas as $facturaId) {
        $factura = facturaDocumentoCargar($pdo, (int) $facturaId);
        if (!$factura) continue;

        crearTareaInterna(
            $pdo,
            (int) $factura['creado_por'],
            (int) $factura['contacto_id'],
            'Factura ' . $factura['numero'] . ' vence el ' . facturaDocumentoFecha($factura['fecha_vencimiento']) . '. Confirmar que el pago esta en curso.'
        );
    }

    log_linea("Recordatorios de cobro finalizados. Facturas procesadas: $procesados, emails enviados: $enviados");
} catch (Throwable $e) {
    log_linea('ERROR: ' . $e->getMessage());
    exit(1);
}
