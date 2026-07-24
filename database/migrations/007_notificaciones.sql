-- Migración 007: notificaciones y recordatorios de actividades

-- Tabla de preferencias de notificaciones por usuario
CREATE TABLE IF NOT EXISTS `preferencias_notificaciones` (
  `usuario_id`         INT          NOT NULL PRIMARY KEY,
  `activas`            TINYINT(1)   NOT NULL DEFAULT 1,
  `sonido`             TINYINT(1)   NOT NULL DEFAULT 1,
  `browser_push`       TINYINT(1)   NOT NULL DEFAULT 0,
  `antelacion_minutos` SMALLINT     NOT NULL DEFAULT 30,
  `frecuencia_segundos` SMALLINT    NOT NULL DEFAULT 60,
  `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pref_notif_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Columna recordatorio_at: momento en el que se debe disparar el recordatorio
ALTER TABLE `actividades`
  ADD COLUMN `recordatorio_at` DATETIME NULL DEFAULT NULL AFTER `fecha`,
  ADD COLUMN `recordatorio_descartado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `recordatorio_at`,
  ADD INDEX `idx_act_recordatorio` (`recordatorio_at`, `recordatorio_descartado`, `completada`);

-- Sembrar preferencias por defecto para usuarios existentes
INSERT INTO `preferencias_notificaciones` (`usuario_id`)
SELECT `id_usuario` FROM `usuarios`
ON DUPLICATE KEY UPDATE `usuario_id` = `preferencias_notificaciones`.`usuario_id`;
