-- Migracion 013: modulo de presupuestos (quotes) + recordatorios de cobro
--
-- Un presupuesto se envia al cliente antes de facturar. Si se acepta, se
-- convierte en una factura real (accion "convertir" en la API), copiando
-- sus lineas y quedando enlazado via `factura_id`.

CREATE TABLE IF NOT EXISTS `presupuestos` (
  `id_presupuesto` INT AUTO_INCREMENT PRIMARY KEY,
  `numero`         VARCHAR(30) NOT NULL UNIQUE,
  `contacto_id`    INT NOT NULL,
  `estado`         ENUM('borrador','enviado','aceptado','rechazado','expirado','convertido') NOT NULL DEFAULT 'borrador',
  `fecha_emision`  DATE NOT NULL,
  `fecha_validez`  DATE DEFAULT NULL,
  `base_imponible` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_total`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notas`          TEXT DEFAULT NULL,
  `factura_id`     INT DEFAULT NULL COMMENT 'Factura generada al convertir el presupuesto aceptado',
  `creado_por`     INT NOT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_presupuestos_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos`(`id_contacto`) ON DELETE RESTRICT,
  CONSTRAINT `fk_presupuestos_factura`
    FOREIGN KEY (`factura_id`) REFERENCES `facturas`(`id_factura`) ON DELETE SET NULL,
  CONSTRAINT `fk_presupuestos_usuario`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT,
  INDEX `idx_presupuestos_contacto` (`contacto_id`),
  INDEX `idx_presupuestos_estado` (`estado`),
  INDEX `idx_presupuestos_fecha` (`fecha_emision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `presupuesto_lineas` (
  `id_linea`        INT AUTO_INCREMENT PRIMARY KEY,
  `presupuesto_id`  INT NOT NULL,
  `concepto`        VARCHAR(255) NOT NULL,
  `cantidad`        DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_porcentaje`  DECIMAL(5,2) NOT NULL DEFAULT 21.00,
  `subtotal`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_importe`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_linea`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `orden`           SMALLINT NOT NULL DEFAULT 1,
  CONSTRAINT `fk_presupuesto_lineas_presupuesto`
    FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos`(`id_presupuesto`) ON DELETE CASCADE,
  INDEX `idx_presupuesto_lineas_presupuesto` (`presupuesto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Traza cuándo se envió el último recordatorio de cobro de una factura vencida,
-- para que el script de recordatorios (database/tareas/recordatorios_facturas.php)
-- no reenvíe el email en cada ejecución.
ALTER TABLE `facturas`
  ADD COLUMN `recordatorio_enviado_at` DATETIME NULL DEFAULT NULL AFTER `total`,
  ADD INDEX `idx_facturas_recordatorio` (`estado`, `fecha_vencimiento`, `recordatorio_enviado_at`);
