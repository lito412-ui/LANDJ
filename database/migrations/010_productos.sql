-- Migracion 010: modulo de productos

CREATE TABLE IF NOT EXISTS `productos` (
  `id_producto`     INT AUTO_INCREMENT PRIMARY KEY,
  `codigo`          VARCHAR(40) DEFAULT NULL UNIQUE,
  `nombre`          VARCHAR(150) NOT NULL,
  `descripcion`     TEXT DEFAULT NULL,
  `precio`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_porcentaje`  DECIMAL(5,2) NOT NULL DEFAULT 21.00,
  `stock`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `activo`          TINYINT(1) NOT NULL DEFAULT 1,
  `creado_por`      INT NOT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_productos_usuario`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT,
  INDEX `idx_productos_nombre` (`nombre`),
  INDEX `idx_productos_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
