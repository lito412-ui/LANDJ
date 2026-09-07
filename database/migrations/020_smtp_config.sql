-- Migración 020: Tabla de configuración del sistema
-- Almacena ajustes del sistema (SMTP, etc.) configurables desde el panel de administrador

CREATE TABLE IF NOT EXISTS `system_config` (
  `id`         INT          AUTO_INCREMENT PRIMARY KEY,
  `clave`      VARCHAR(100) NOT NULL UNIQUE,
  `valor`      TEXT         DEFAULT NULL,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Claves iniciales para SMTP
INSERT IGNORE INTO `system_config` (`clave`, `valor`) VALUES
  ('smtp_host', NULL),
  ('smtp_port', NULL),
  ('smtp_user', NULL),
  ('smtp_pass', NULL),
  ('smtp_from', NULL),
  ('smtp_encryption', NULL);
