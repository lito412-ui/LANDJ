<?php

/**
 * Lógica compartida para generar una factura real a partir de una plantilla
 * de factura recurrente. La usan tanto public/api/facturas_recurrentes.php
 * (botón "Generar ahora") como database/tareas/generar_facturas_recurrentes.php
 * (cron), para no duplicar el cálculo de totales ni la numeración.
 */

function generarNumeroFacturaRecurrente(PDO $pdo): string {
    $year = date('Y');
    $prefix = "FAC-$year-";
    $s = $pdo->prepare("SELECT numero FROM facturas WHERE numero LIKE ? ORDER BY numero DESC LIMIT 1");
    $s->execute([$prefix . '%']);
    $ultimo = $s->fetchColumn();
    $seq = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;
    return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

/** Fecha del siguiente ciclo (mes/trimestre/año) a partir de una fecha base, ajustando el día al mes destino. */
function calcularProximaFechaRecurrente(string $desde, string $periodicidad, int $diaGeneracion): string {
    $incrementos = ['mensual' => 1, 'trimestral' => 3, 'anual' => 12];
    $meses = $incrementos[$periodicidad] ?? 1;
    $dt = new DateTime($desde);
    $dt->modify("+{$meses} month");
    $ultimoDiaMes = (int) $dt->format('t');
    $dia = min($diaGeneracion, $ultimoDiaMes);
    return $dt->format('Y-m') . '-' . str_pad((string) $dia, 2, '0', STR_PAD_LEFT);
}

function cargarRecurrenteConLineas(PDO $pdo, int $id): ?array {
    $s = $pdo->prepare("
        SELECT r.*, c.nombre AS contacto_nombre, c.email AS contacto_email
        FROM facturas_recurrentes r
        INNER JOIN contactos c ON c.id_contacto = r.contacto_id
        WHERE r.id_recurrente = ?
        LIMIT 1
    ");
    $s->execute([$id]);
    $rec = $s->fetch();
    if (!$rec) return null;

    $l = $pdo->prepare("SELECT * FROM facturas_recurrentes_lineas WHERE recurrente_id = ? ORDER BY orden, id_linea");
    $l->execute([$id]);
    $rec['lineas'] = $l->fetchAll();

    return $rec;
}

/**
 * Genera una factura real a partir de una plantilla recurrente (con sus
 * líneas ya cargadas en $rec['lineas']) y avanza proxima_generacion.
 * Requiere registrarAuditoria() ya cargada por el llamador.
 */
function generarFacturaDesdeRecurrente(PDO $pdo, array $rec, int $userIdGenerador): array {
    $base = 0.0; $ivaTotal = 0.0;
    $lineasCalculadas = [];
    foreach ($rec['lineas'] as $l) {
        $cantidad = (float) $l['cantidad'];
        $precio = (float) $l['precio_unitario'];
        $ivaPct = (float) $l['iva_porcentaje'];
        $subtotal = round($cantidad * $precio, 2);
        $ivaImporte = round($subtotal * ($ivaPct / 100), 2);
        $totalLinea = round($subtotal + $ivaImporte, 2);
        $lineasCalculadas[] = [
            'concepto' => $l['concepto'], 'cantidad' => $cantidad, 'precio' => $precio, 'ivaPct' => $ivaPct,
            'subtotal' => $subtotal, 'ivaImporte' => $ivaImporte, 'totalLinea' => $totalLinea, 'orden' => $l['orden'],
        ];
        $base += $subtotal;
        $ivaTotal += $ivaImporte;
    }
    $base = round($base, 2);
    $ivaTotal = round($ivaTotal, 2);
    $total = round($base + $ivaTotal, 2);

    $numero = generarNumeroFacturaRecurrente($pdo);
    $fechaEmision = date('Y-m-d');
    $fechaVencimiento = date('Y-m-d', strtotime("+{$rec['dias_vencimiento']} days"));
    $notas = 'Generada automáticamente desde la plantilla recurrente "' . $rec['nombre'] . '"';

    $pdo->beginTransaction();
    try {
        $sIns = $pdo->prepare("
            INSERT INTO facturas
                (numero, contacto_id, estado, fecha_emision, fecha_vencimiento, base_imponible, iva_total, total, notas, creado_por, recurrente_id)
            VALUES (?, ?, 'emitida', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $sIns->execute([$numero, $rec['contacto_id'], $fechaEmision, $fechaVencimiento, $base, $ivaTotal, $total, $notas, $userIdGenerador, $rec['id_recurrente']]);
        $facturaId = (int) $pdo->lastInsertId();

        $sLinea = $pdo->prepare("
            INSERT INTO factura_lineas (factura_id, concepto, cantidad, precio_unitario, iva_porcentaje, subtotal, iva_importe, total_linea, orden)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($lineasCalculadas as $l) {
            $sLinea->execute([$facturaId, $l['concepto'], $l['cantidad'], $l['precio'], $l['ivaPct'], $l['subtotal'], $l['ivaImporte'], $l['totalLinea'], $l['orden']]);
        }

        $proxima = calcularProximaFechaRecurrente($rec['proxima_generacion'], $rec['periodicidad'], (int) $rec['dia_generacion']);
        $pdo->prepare("UPDATE facturas_recurrentes SET ultima_generacion = ?, proxima_generacion = ? WHERE id_recurrente = ?")
            ->execute([$fechaEmision, $proxima, $rec['id_recurrente']]);

        if (function_exists('registrarAuditoria')) {
            registrarAuditoria($pdo, 'facturas', $facturaId, 'crear', null, ['origen_recurrente' => $rec['nombre'], 'numero' => $numero]);
        }

        $pdo->commit();
        return ['id_factura' => $facturaId, 'numero' => $numero, 'total' => $total, 'proxima_generacion' => $proxima];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
