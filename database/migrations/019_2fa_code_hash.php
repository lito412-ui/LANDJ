<?php
/** @var PDO $pdo */

// Ampliar la columna two_factor_code a VARCHAR(255) para permitir almacenar hashes seguros
try {
    $col = $pdo->query("SHOW COLUMNS FROM `usuarios` LIKE 'two_factor_code'")->fetch();
    if ($col && strpos(strtolower($col['Type']), 'varchar(6)') !== false) {
        $pdo->exec("ALTER TABLE `usuarios` MODIFY `two_factor_code` VARCHAR(255) DEFAULT NULL");
        echo "    columna two_factor_code ampliada a VARCHAR(255)\n";
    }
} catch (PDOException $e) {
    echo "    nota: " . $e->getMessage() . "\n";
}
