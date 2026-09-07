-- ============================================================
-- Migración 021: Multitenancy (Grupos / Empresas) y Auth
-- Compatible con MySQL 8.4
-- ============================================================

-- ============================================================
-- 1. Tabla de Grupos (Organizaciones / Tenants)
-- ============================================================

CREATE TABLE IF NOT EXISTS `grupos` (
  `id_grupo` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- Grupo 1 por defecto para instalaciones existentes
INSERT IGNORE INTO `grupos` (`id_grupo`, `nombre`)
VALUES (1, 'Organización Principal');


-- ============================================================
-- 2. Modificaciones en tabla usuarios
-- ============================================================

-- id_grupo
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `usuarios`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `rol`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- email_verificado
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'email_verificado'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `usuarios`
     ADD COLUMN `email_verificado` TINYINT(1) NOT NULL DEFAULT 1 AFTER `id_grupo`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- codigo_verificacion_email
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'codigo_verificacion_email'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `usuarios`
     ADD COLUMN `codigo_verificacion_email` VARCHAR(255) DEFAULT NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- codigo_verificacion_expira
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'codigo_verificacion_expira'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `usuarios`
     ADD COLUMN `codigo_verificacion_expira` DATETIME DEFAULT NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- codigo_recuperacion
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'codigo_recuperacion'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `usuarios`
     ADD COLUMN `codigo_recuperacion` VARCHAR(255) DEFAULT NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- codigo_recuperacion_expira
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'codigo_recuperacion_expira'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `usuarios`
     ADD COLUMN `codigo_recuperacion_expira` DATETIME DEFAULT NULL',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- recuperacion_intentos
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND COLUMN_NAME = 'recuperacion_intentos'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `usuarios`
     ADD COLUMN `recuperacion_intentos` INT NOT NULL DEFAULT 0',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================================
-- 3. Clave foránea usuarios -> grupos
-- ============================================================

SET @fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'usuarios'
      AND CONSTRAINT_NAME = 'fk_usuarios_grupo'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql = IF(
    @fk_exists = 0,
    'ALTER TABLE `usuarios`
     ADD CONSTRAINT `fk_usuarios_grupo`
     FOREIGN KEY (`id_grupo`)
     REFERENCES `grupos`(`id_grupo`)
     ON DELETE CASCADE',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================================
-- 4. Añadir id_grupo a tablas del CRM
-- ============================================================

-- contactos
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'contactos'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `contactos`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_contacto`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- leads
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'leads'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `leads`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_lead`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- oportunidades
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'oportunidades'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `oportunidades`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_oportunidad`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- actividades
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'actividades'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `actividades`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_actividad`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- presupuestos
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'presupuestos'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `presupuestos`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_presupuesto`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- facturas
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'facturas'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `facturas`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_factura`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- facturas_recurrentes
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'facturas_recurrentes'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `facturas_recurrentes`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_recurrente`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- productos
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'productos'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `productos`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_producto`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- proveedores
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'proveedores'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `proveedores`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_proveedor`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- avisos
SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'avisos'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `avisos`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_aviso`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================================
-- 5. Adaptar system_config para multitenancy
-- ============================================================

SET @existe = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'system_config'
      AND COLUMN_NAME = 'id_grupo'
);

SET @sql = IF(
    @existe = 0,
    'ALTER TABLE `system_config`
     ADD COLUMN `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id`',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ============================================================
-- 6. Eliminar índice único antiguo sobre "clave"
-- ============================================================

SET @drop_index = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'system_config'
      AND INDEX_NAME = 'clave'
);

SET @sql_drop = IF(
    @drop_index > 0,
    'ALTER TABLE `system_config` DROP INDEX `clave`',
    'SELECT 1'
);

PREPARE stmt_drop FROM @sql_drop;
EXECUTE stmt_drop;
DEALLOCATE PREPARE stmt_drop;


-- ============================================================
-- 7. Crear índice único compuesto (id_grupo, clave)
-- ============================================================

SET @create_index = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'system_config'
      AND INDEX_NAME = 'uq_grupo_clave'
);

SET @sql_idx = IF(
    @create_index = 0,
    'ALTER TABLE `system_config`
     ADD UNIQUE KEY `uq_grupo_clave` (`id_grupo`, `clave`)',
    'SELECT 1'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;