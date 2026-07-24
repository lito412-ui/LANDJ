-- Migración 008: Verificación en dos pasos (2FA por email)

ALTER TABLE `usuarios`
    ADD COLUMN `two_factor_enabled`    TINYINT(1)   NOT NULL DEFAULT 0           AFTER `created_at`,
    ADD COLUMN `two_factor_code`       VARCHAR(6)   DEFAULT NULL                  AFTER `two_factor_enabled`,
    ADD COLUMN `two_factor_expires_at` DATETIME     DEFAULT NULL                  AFTER `two_factor_code`,
    ADD COLUMN `two_factor_attempts`   TINYINT      NOT NULL DEFAULT 0            AFTER `two_factor_expires_at`;
