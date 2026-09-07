-- Migracion 014: token de confirmacion publica para presupuestos
--
-- Permite que el cliente acepte o rechace un presupuesto desde un enlace
-- del email, sin necesidad de iniciar sesion en el CRM. El token se genera
-- la primera vez que se envia el presupuesto por email (ver
-- public/api/presupuesto_email.php) y es de un solo uso por decision
-- (una vez confirmado, el enlace deja de permitir cambios).

ALTER TABLE `presupuestos`
  ADD COLUMN `token_confirmacion`   VARCHAR(64) NULL DEFAULT NULL AFTER `factura_id`,
  ADD COLUMN `token_confirmado_at`  DATETIME NULL DEFAULT NULL AFTER `token_confirmacion`,
  ADD UNIQUE INDEX `idx_presupuestos_token` (`token_confirmacion`);
