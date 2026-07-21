-- Migración 006: tabla cuentas_correo
CREATE TABLE IF NOT EXISTS `cuentas_correo` (
  `id_cuenta`  INT           AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(255)  NOT NULL UNIQUE,
  `dominio`    VARCHAR(255)  NOT NULL,
  `cuota`      INT           NOT NULL DEFAULT 500,
  `estado`     ENUM('activo','suspendido') NOT NULL DEFAULT 'activo',
  `notas`      TEXT          DEFAULT NULL,
  `creado_por` INT           NOT NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cuentas_correo_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
