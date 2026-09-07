-- ============================================================
-- Migración 021: Multitenancy (Grupos / Empresas) y Auth
-- ============================================================

-- 1. Tabla de Grupos (Organizaciones / Tenants)
CREATE TABLE IF NOT EXISTS `grupos` (
  `id_grupo`   INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre`     VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grupo 1 por defecto para la instalación existente
INSERT IGNORE INTO `grupos` (`id_grupo`, `nombre`) VALUES (1, 'Organización Principal');

-- 2. Modificaciones en tabla `usuarios`
-- Añadir id_grupo, campos para verificación de email y recuperación de contraseña
ALTER TABLE `usuarios`
  ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `rol`,
  ADD COLUMN IF NOT EXISTS `email_verificado` TINYINT(1) NOT NULL DEFAULT 1 AFTER `id_grupo`,
  ADD COLUMN IF NOT EXISTS `codigo_verificacion_email` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `codigo_verificacion_expira` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `codigo_recuperacion` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `codigo_recuperacion_expira` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `recuperacion_intentos` INT NOT NULL DEFAULT 0;

-- Clave foránea en usuarios hacia grupos
-- (Si falla por clave duplicada no rompe el script)
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = 'fk_usuarios_grupo' AND TABLE_SCHEMA = DATABASE());
SET @sql = IF(@fk_exists = 0, 'ALTER TABLE `usuarios` ADD CONSTRAINT `fk_usuarios_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos`(`id_grupo`) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Añadir id_grupo a tablas del CRM
ALTER TABLE `contactos`            ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_contacto`;
ALTER TABLE `leads`                ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_lead`;
ALTER TABLE `oportunidades`        ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_oportunidad`;
ALTER TABLE `actividades`          ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_actividad`;
ALTER TABLE `presupuestos`        ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_presupuesto`;
ALTER TABLE `facturas`             ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_factura`;
ALTER TABLE `facturas_recurrentes` ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_recurrente`;
ALTER TABLE `productos`            ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_producto`;
ALTER TABLE `proveedores`          ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_proveedor`;
ALTER TABLE `avisos`               ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id_aviso`;

-- 4. Adaptar system_config para ser multi-grupo (cada empresa su propio SMTP)
ALTER TABLE `system_config` ADD COLUMN IF NOT EXISTS `id_grupo` INT NOT NULL DEFAULT 1 AFTER `id`;

-- Eliminar índice único antiguo sobre solo 'clave' y añadir único compuesto (id_grupo, clave)
SET @drop_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_config' AND INDEX_NAME = 'clave');
SET @sql_drop = IF(@drop_index > 0, 'ALTER TABLE `system_config` DROP INDEX `clave`', 'SELECT 1');
PREPARE stmt_drop FROM @sql_drop;
EXECUTE stmt_drop;
DEALLOCATE PREPARE stmt_drop;

SET @create_index = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'system_config' AND INDEX_NAME = 'uq_grupo_clave');
SET @sql_idx = IF(@create_index = 0, 'ALTER TABLE `system_config` ADD UNIQUE KEY `uq_grupo_clave` (`id_grupo`, `clave`)', 'SELECT 1');
PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;
