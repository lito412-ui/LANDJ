-- Migracion 009: modulo de facturas

CREATE TABLE IF NOT EXISTS `facturas` (
  `id_factura`     INT AUTO_INCREMENT PRIMARY KEY,
  `numero`         VARCHAR(30) NOT NULL UNIQUE,
  `contacto_id`    INT NOT NULL,
  `estado`         ENUM('borrador','emitida','pagada','vencida','cancelada') NOT NULL DEFAULT 'borrador',
  `fecha_emision`  DATE NOT NULL,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `base_imponible` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_total`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notas`          TEXT DEFAULT NULL,
  `creado_por`     INT NOT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_facturas_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos`(`id_contacto`) ON DELETE RESTRICT,
  CONSTRAINT `fk_facturas_usuario`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT,
  INDEX `idx_facturas_contacto` (`contacto_id`),
  INDEX `idx_facturas_estado` (`estado`),
  INDEX `idx_facturas_fecha` (`fecha_emision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `factura_lineas` (
  `id_linea`       INT AUTO_INCREMENT PRIMARY KEY,
  `factura_id`     INT NOT NULL,
  `concepto`       VARCHAR(255) NOT NULL,
  `cantidad`       DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_porcentaje` DECIMAL(5,2) NOT NULL DEFAULT 21.00,
  `subtotal`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_importe`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_linea`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `orden`          SMALLINT NOT NULL DEFAULT 1,
  CONSTRAINT `fk_factura_lineas_factura`
    FOREIGN KEY (`factura_id`) REFERENCES `facturas`(`id_factura`) ON DELETE CASCADE,
  INDEX `idx_factura_lineas_factura` (`factura_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
