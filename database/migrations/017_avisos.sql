-- Migracion 017: Avisos (anuncios de administradores a otros usuarios)
--
-- Distinto del sistema de "Notificaciones" ya existente (recordatorios
-- personales basados en actividades.recordatorio_at): un Aviso es un mensaje
-- que un administrador redacta y envia a otros usuarios (a todos o a una
-- seleccion), con seguimiento de lectura por destinatario.

CREATE TABLE IF NOT EXISTS `avisos` (
  `id_aviso`    INT AUTO_INCREMENT PRIMARY KEY,
  `titulo`      VARCHAR(150) NOT NULL,
  `mensaje`     TEXT NOT NULL,
  `tipo`        ENUM('info','exito','aviso','urgente') NOT NULL DEFAULT 'info',
  `creado_por`  INT NOT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_avisos_usuario`
    FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE,
  INDEX `idx_avisos_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `avisos_destinatarios` (
  `id_aviso`    INT NOT NULL,
  `usuario_id`  INT NOT NULL,
  `leido_at`    DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id_aviso`, `usuario_id`),
  CONSTRAINT `fk_avisos_dest_aviso`
    FOREIGN KEY (`id_aviso`) REFERENCES `avisos`(`id_aviso`) ON DELETE CASCADE,
  CONSTRAINT `fk_avisos_dest_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE,
  INDEX `idx_avisos_dest_usuario` (`usuario_id`, `leido_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
