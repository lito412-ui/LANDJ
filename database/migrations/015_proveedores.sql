-- Migracion 015: modulo de proveedores + enlace opcional desde productos
--
-- Un proveedor es la empresa/persona a la que compras los productos o
-- servicios que despues vendes. Se enlaza opcionalmente desde `productos`
-- (un producto puede no tener proveedor asignado, por ejemplo un servicio
-- propio sin compra externa).

CREATE TABLE IF NOT EXISTS `proveedores` (
  `id_proveedor`         INT AUTO_INCREMENT PRIMARY KEY,
  `nombre`                VARCHAR(150) NOT NULL,
  `nif`                   VARCHAR(20) DEFAULT NULL,
  `email`                 VARCHAR(150) DEFAULT NULL,
  `telefono`              VARCHAR(30) DEFAULT NULL,
  `direccion`             VARCHAR(255) DEFAULT NULL,
  `contacto_referencia`   VARCHAR(150) DEFAULT NULL COMMENT 'Persona de contacto dentro del proveedor',
  `notas`                 TEXT DEFAULT NULL,
  `activo`                TINYINT(1) NOT NULL DEFAULT 1,
  `creado_por`            INT NOT NULL,
  `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_proveedores_usuario`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT,
  UNIQUE INDEX `idx_proveedores_nif` (`nif`),
  INDEX `idx_proveedores_nombre` (`nombre`),
  INDEX `idx_proveedores_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `productos`
  ADD COLUMN `proveedor_id` INT NULL DEFAULT NULL AFTER `stock`,
  ADD CONSTRAINT `fk_productos_proveedor`
    FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id_proveedor`) ON DELETE SET NULL,
  ADD INDEX `idx_productos_proveedor` (`proveedor_id`);
