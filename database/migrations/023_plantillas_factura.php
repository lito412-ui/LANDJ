<?php
/**
 * Plantillas visuales por grupo. La instantánea en la factura permite que los
 * documentos ya emitidos conserven su aspecto aunque la plantilla se edite.
 */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS plantillas_factura (
        id_plantilla INT AUTO_INCREMENT PRIMARY KEY,
        id_grupo INT NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        logo_url VARCHAR(1000) NULL,
        color_primario CHAR(7) NOT NULL DEFAULT '#1d4ed8',
        color_secundario CHAR(7) NOT NULL DEFAULT '#eff6ff',
        fuente VARCHAR(30) NOT NULL DEFAULT 'Helvetica',
        texto_pie VARCHAR(500) NULL,
        creado_por INT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_plantilla_factura_grupo_nombre (id_grupo, nombre),
        KEY idx_plantillas_factura_grupo (id_grupo),
        CONSTRAINT fk_plantillas_factura_grupo FOREIGN KEY (id_grupo)
            REFERENCES grupos(id_grupo) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$columnas = $pdo->query("SHOW COLUMNS FROM facturas")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('plantilla_id', $columnas, true)) {
    $pdo->exec("ALTER TABLE facturas ADD COLUMN plantilla_id INT NULL AFTER id_grupo");
    $pdo->exec("ALTER TABLE facturas ADD KEY idx_facturas_plantilla (plantilla_id)");
}
if (!in_array('plantilla_snapshot', $columnas, true)) {
    $pdo->exec("ALTER TABLE facturas ADD COLUMN plantilla_snapshot JSON NULL AFTER plantilla_id");
}

echo "  Plantillas de factura preparadas.\n";
