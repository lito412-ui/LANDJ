<?php
/**
 * Migración 002: columna contacto_id en leads (FK → contactos)
 * Se usa .php porque la lógica condicional no es compatible con PDO::exec() multi-sentencia.
 */

// Añadir columna si no existe
$existe = $pdo->query("
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'leads'
      AND COLUMN_NAME  = 'contacto_id'
")->fetchColumn();

if (!$existe) {
    $pdo->exec("ALTER TABLE `leads` ADD COLUMN `contacto_id` INT DEFAULT NULL");
    echo "    Columna contacto_id añadida a leads.\n";
} else {
    echo "    Columna contacto_id ya existe, omitida.\n";
}

// Añadir FK si no existe
$fkExiste = $pdo->query("
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA    = DATABASE()
      AND TABLE_NAME      = 'leads'
      AND CONSTRAINT_NAME = 'fk_leads_contacto'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
")->fetchColumn();

if (!$fkExiste) {
    $pdo->exec("
        ALTER TABLE `leads`
        ADD CONSTRAINT `fk_leads_contacto`
        FOREIGN KEY (`contacto_id`) REFERENCES `contactos`(`id_contacto`) ON DELETE SET NULL
    ");
    echo "    FK fk_leads_contacto añadida.\n";
} else {
    echo "    FK fk_leads_contacto ya existe, omitida.\n";
}
