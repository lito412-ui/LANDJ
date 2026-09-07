<?php
/**
 * Migración 012: factura de ejemplo construida a partir del catálogo de
 * productos/servicios, para demostrar el selector de producto en las líneas
 * de factura. Se usa .php porque los importes de las líneas se calculan
 * dinámicamente a partir de los precios e IVA reales de `productos`.
 *
 * @var PDO $pdo
 */

$adminId = $pdo->query("SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1")->fetchColumn();
if (!$adminId) {
    echo "    [aviso] No se encontró el usuario admin, se omite la factura de ejemplo\n";
    return;
}

$contactoId = $pdo->query("SELECT id_contacto FROM contactos WHERE email = 'maria.garcia@ejemplo.com' LIMIT 1")->fetchColumn();
if (!$contactoId) {
    echo "    [aviso] No se encontró el contacto de ejemplo, se omite la factura de ejemplo\n";
    return;
}

$numero = 'FAC-DEMO-0001';
$existe = $pdo->prepare("SELECT id_factura FROM facturas WHERE numero = ?");
$existe->execute([$numero]);
if ($existe->fetchColumn()) {
    echo "    [ya existe] $numero\n";
    return;
}

$sProductos = $pdo->prepare("
    SELECT nombre, precio, iva_porcentaje FROM productos
    WHERE codigo IN ('WEB-DISENO', 'HOST-ANUAL', 'MANT-MENSUAL')
    ORDER BY FIELD(codigo, 'WEB-DISENO', 'HOST-ANUAL', 'MANT-MENSUAL')
");
$sProductos->execute();
$productos = $sProductos->fetchAll(PDO::FETCH_ASSOC);

if (!$productos) {
    echo "    [aviso] No hay productos de ejemplo disponibles, se omite la factura de ejemplo\n";
    return;
}

$lineas = [];
$base = 0.0;
$ivaTotal = 0.0;
$orden = 1;

foreach ($productos as $p) {
    $cantidad = 1.00;
    $precio = (float) $p['precio'];
    $ivaPct = (float) $p['iva_porcentaje'];

    $subtotal = round($cantidad * $precio, 2);
    $ivaImporte = round($subtotal * ($ivaPct / 100), 2);
    $totalLinea = round($subtotal + $ivaImporte, 2);

    $lineas[] = [
        'concepto'        => $p['nombre'],
        'cantidad'        => $cantidad,
        'precio_unitario' => $precio,
        'iva_porcentaje'  => $ivaPct,
        'subtotal'        => $subtotal,
        'iva_importe'     => $ivaImporte,
        'total_linea'     => $totalLinea,
        'orden'           => $orden++,
    ];

    $base += $subtotal;
    $ivaTotal += $ivaImporte;
}

$base = round($base, 2);
$ivaTotal = round($ivaTotal, 2);
$total = round($base + $ivaTotal, 2);

$pdo->beginTransaction();
try {
    $ins = $pdo->prepare("
        INSERT INTO facturas
            (numero, contacto_id, estado, fecha_emision, fecha_vencimiento, base_imponible, iva_total, total, notas, creado_por)
        VALUES (?, ?, 'emitida', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, ?, ?, ?)
    ");
    $ins->execute([
        $numero, $contactoId, $base, $ivaTotal, $total,
        'Factura de ejemplo generada al migrar la base de datos, para probar el selector de productos/servicios.',
        $adminId,
    ]);
    $facturaId = (int) $pdo->lastInsertId();

    $insLinea = $pdo->prepare("
        INSERT INTO factura_lineas
            (factura_id, concepto, cantidad, precio_unitario, iva_porcentaje, subtotal, iva_importe, total_linea, orden)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($lineas as $l) {
        $insLinea->execute([
            $facturaId, $l['concepto'], $l['cantidad'], $l['precio_unitario'], $l['iva_porcentaje'],
            $l['subtotal'], $l['iva_importe'], $l['total_linea'], $l['orden'],
        ]);
    }

    $pdo->commit();
    echo "    [creada] Factura de ejemplo $numero (total $total EUR)\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    throw $e;
}
