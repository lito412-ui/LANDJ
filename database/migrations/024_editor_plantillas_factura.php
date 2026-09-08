<?php
$cols = $pdo->query('SHOW COLUMNS FROM plantillas_factura')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('marca_agua', $cols, true)) $pdo->exec("ALTER TABLE plantillas_factura ADD marca_agua VARCHAR(120) NULL AFTER texto_pie");
if (!in_array('marca_agua_opacidad', $cols, true)) $pdo->exec("ALTER TABLE plantillas_factura ADD marca_agua_opacidad TINYINT UNSIGNED NOT NULL DEFAULT 10 AFTER marca_agua");
if (!in_array('orden_bloques', $cols, true)) $pdo->exec("ALTER TABLE plantillas_factura ADD orden_bloques JSON NULL AFTER marca_agua_opacidad");
echo "  Editor visual de plantillas preparado.\n";
