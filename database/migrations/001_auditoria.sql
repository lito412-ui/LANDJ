-- Migración 001: tabla de auditoría
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id_auditoria`  BIGINT       AUTO_INCREMENT PRIMARY KEY,
  `tabla`         VARCHAR(50)  NOT NULL,
  `registro_id`   INT          NOT NULL,
  `accion`        ENUM('crear','editar','eliminar') NOT NULL,
  `usuario_id`    INT          DEFAULT NULL,
  `datos_antes`   JSON         DEFAULT NULL,
  `datos_despues` JSON         DEFAULT NULL,
  `ip`            VARCHAR(45)  DEFAULT NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL,
  INDEX `idx_audit_entidad` (`tabla`, `registro_id`),
  INDEX `idx_audit_usuario` (`usuario_id`),
  INDEX `idx_audit_fecha`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
