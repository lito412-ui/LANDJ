<?php
/**
 * Generación automática de facturas recurrentes.
 *
 * Pensado para ejecutarse periodicamente (ver servicio "cron" en
 * docker-compose.yml, junto a recordatorios_facturas.php). Cada ejecución:
 *
 *   1. Busca plantillas activas cuya proxima_generacion ya haya llegado
 *      (y que no hayan superado su fecha_fin, si tienen una).
 *   2. Genera una factura real por cada una (misma logica que el boton
 *      "Generar ahora" del panel), y avanza proxima_generacion al
 *      siguiente ciclo.
 *   3. Si la plantilla tiene enviar_email activado, envía la factura
 *      recien generada al contacto con el PDF adjunto.
 *
 * Uso: php database/tareas/generar_facturas_recurrentes.php
 */

require __DIR__ . '/../../public/config/conexion.php';
require __DIR__ . '/../../public/config/auditoria.php';
require __DIR__ . '/../../public/config/facturas_recurrentes_util.php';
require __DIR__ . '/../../public/config/facturas_documentos.php';
require __DIR__ . '/../../public/config/mailer.php';

function log_linea(string $msg): void {
    echo '[' . date('Y-m-d H:i:s') . "] $msg\n";
}

try {
    log_linea('Iniciando generación de facturas recurrentes...');

    $sAdmin = $pdo->query("SELECT id_usuario FROM usuarios WHERE rol = 'administrador' ORDER BY id_usuario LIMIT 1");
    $usuarioSistema = (int) ($sAdmin->fetchColumn() ?: 1);

    $sPendientes = $pdo->query("
        SELECT id_recurrente FROM facturas_recurrentes
        WHERE activa = 1
          AND proxima_generacion <= CURDATE()
          AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())
        ORDER BY proxima_generacion ASC
    ");
    $idsPendientes = $sPendientes->fetchAll(PDO::FETCH_COLUMN);
    log_linea('Plantillas recurrentes pendientes de generar: ' . count($idsPendientes));

    $generadas = 0; $enviadas = 0;

    foreach ($idsPendientes as $recurrenteId) {
        $rec = cargarRecurrenteConLineas($pdo, (int) $recurrenteId);
        if (!$rec) continue;

        if (!$rec['lineas']) {
            log_linea("  [aviso] Plantilla \"{$rec['nombre']}\" no tiene líneas, se omite");
            continue;
        }

        // Salvaguarda: usar el creado_por original de la plantilla como emisor,
        // si el usuario que la creo ya no existe, cae al primer administrador.
        $sCreador = $pdo->prepare("SELECT creado_por FROM facturas_recurrentes WHERE id_recurrente = ?");
        $sCreador->execute([$recurrenteId]);
        $creadoPor = (int) ($sCreador->fetchColumn() ?: $usuarioSistema);

        try {
            $resultado = generarFacturaDesdeRecurrente($pdo, $rec, $creadoPor);
            $generadas++;
            log_linea("  [ok] {$resultado['numero']} generada desde \"{$rec['nombre']}\" ({$resultado['total']} €), próxima: {$resultado['proxima_generacion']}");
        } catch (Throwable $e) {
            log_linea("  [error] No se pudo generar la factura de \"{$rec['nombre']}\": " . $e->getMessage());
            continue;
        }

        if (!empty($rec['enviar_email']) && !empty($rec['contacto_email']) && filter_var($rec['contacto_email'], FILTER_VALIDATE_EMAIL)) {
            try {
                $factura = facturaDocumentoCargar($pdo, $resultado['id_factura']);
                if ($factura) {
                    $pdf = facturaDocumentoPdf($factura);
                    $ok = enviarEmail(
                        $factura['contacto_email'],
                        'Factura ' . $factura['numero'],
                        facturaDocumentoHtmlEmail($factura),
                        [[
                            'data' => $pdf,
                            'name' => facturaDocumentoNombre($factura),
                            'encoding' => 'base64',
                            'type' => 'application/pdf',
                        ]],
                        (int) $factura['id_grupo']
                    );
                    if ($ok) $enviadas++;
                    log_linea('    email: ' . ($ok ? 'enviado' : 'no enviado'));
                }
            } catch (Throwable $e) {
                log_linea('    [aviso] No se pudo enviar el email: ' . $e->getMessage());
            }
        }
    }

    log_linea("Generación de facturas recurrentes finalizada. Generadas: $generadas, emails enviados: $enviadas");
} catch (Throwable $e) {
    log_linea('ERROR: ' . $e->getMessage());
    exit(1);
}
