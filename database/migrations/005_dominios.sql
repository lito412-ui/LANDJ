-- Migración 005: tabla dominios
CREATE TABLE IF NOT EXISTS `dominios` (
  `id_dominio`  INT           AUTO_INCREMENT PRIMARY KEY,
  `dominio`     VARCHAR(255)  NOT NULL UNIQUE,
  `tipo`        ENUM('principal','subdominio','addon','parked') NOT NULL DEFAULT 'principal',
  `estado`      ENUM('activo','pendiente','suspendido') NOT NULL DEFAULT 'pendiente',
  `ip`          VARCHAR(45)   DEFAULT NULL,
  `ssl`         TINYINT(1)    NOT NULL DEFAULT 0,
  `notas`       TEXT          DEFAULT NULL,
  `creado_por`  INT           NOT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_dominios_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
