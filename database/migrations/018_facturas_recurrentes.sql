-- Migracion 018: facturas recurrentes
--
-- Una plantilla recurrente genera automaticamente una factura real cada
-- cierto periodo (mensual/trimestral/anual), a traves del script
-- database/tareas/generar_facturas_recurrentes.php (ejecutado por el
-- servicio "cron" ya existente). Cada factura generada queda enlazada a la
-- plantilla que la origino via facturas.recurrente_id.

CREATE TABLE IF NOT EXISTS `facturas_recurrentes` (
  `id_recurrente`      INT AUTO_INCREMENT PRIMARY KEY,
  `contacto_id`        INT NOT NULL,
  `nombre`             VARCHAR(150) NOT NULL COMMENT 'Nombre interno, ej: "Mantenimiento mensual - Cliente X"',
  `periodicidad`       ENUM('mensual','trimestral','anual') NOT NULL DEFAULT 'mensual',
  `dia_generacion`     TINYINT NOT NULL DEFAULT 1 COMMENT 'Dia del mes (1-28) en que se genera a partir del segundo ciclo',
  `fecha_inicio`       DATE NOT NULL,
  `fecha_fin`          DATE NULL DEFAULT NULL COMMENT 'NULL = sin fecha de fin',
  `dias_vencimiento`   SMALLINT NOT NULL DEFAULT 30,
  `notas`              TEXT NULL DEFAULT NULL,
  `activa`             TINYINT(1) NOT NULL DEFAULT 1,
  `enviar_email`       TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Enviar automaticamente por email al generarse',
  `ultima_generacion`  DATE NULL DEFAULT NULL,
  `proxima_generacion` DATE NOT NULL,
  `creado_por`         INT NOT NULL,
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_facrec_contacto`
    FOREIGN KEY (`contacto_id`) REFERENCES `contactos`(`id_contacto`) ON DELETE RESTRICT,
  CONSTRAINT `fk_facrec_usuario`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT,
  INDEX `idx_facrec_activa_proxima` (`activa`, `proxima_generacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facturas_recurrentes_lineas` (
  `id_linea`         INT AUTO_INCREMENT PRIMARY KEY,
  `recurrente_id`    INT NOT NULL,
  `concepto`         VARCHAR(255) NOT NULL,
  `cantidad`         DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_porcentaje`   DECIMAL(5,2) NOT NULL DEFAULT 21.00,
  `orden`            SMALLINT NOT NULL DEFAULT 1,
  CONSTRAINT `fk_facrec_lineas`
    FOREIGN KEY (`recurrente_id`) REFERENCES `facturas_recurrentes`(`id_recurrente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Traza que factura se genero desde que plantilla recurrente (para mostrar
-- el historial en la propia plantilla). ON DELETE SET NULL: si se borra la
-- plantilla, las facturas ya generadas no desaparecen.
ALTER TABLE `facturas`
  ADD COLUMN `recurrente_id` INT NULL DEFAULT NULL AFTER `creado_por`,
  ADD CONSTRAINT `fk_facturas_recurrente`
    FOREIGN KEY (`recurrente_id`) REFERENCES `facturas_recurrentes`(`id_recurrente`) ON DELETE SET NULL,
  ADD INDEX `idx_facturas_recurrente` (`recurrente_id`);

-- Se añade como módulo ocultable independiente (categoría CRM), para que un
-- administrador pueda mostrar Facturas pero ocultar Recurrentes, o viceversa.
INSERT IGNORE INTO `modulos_visibilidad` (`modulo`, `etiqueta`, `categoria`, `visible`)
VALUES ('recurrentes', 'Facturas Recurrentes', 'CRM', 1);
