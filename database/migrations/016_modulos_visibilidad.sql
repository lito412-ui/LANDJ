-- Migracion 016: visibilidad de modulos por rol
--
-- Permite a un administrador ocultar secciones enteras del panel para los
-- usuarios con rol "usuario", sin afectar a otros administradores (que
-- siempre ven todo). Es un control de VISIBILIDAD en la interfaz, no un
-- sistema de permisos por usuario individual: aplica igual a todos los
-- usuarios no-administradores de la instalacion.

CREATE TABLE IF NOT EXISTS `modulos_visibilidad` (
  `modulo`           VARCHAR(50) NOT NULL PRIMARY KEY,
  `etiqueta`         VARCHAR(100) NOT NULL,
  `categoria`        VARCHAR(50) NOT NULL,
  `visible`          TINYINT(1) NOT NULL DEFAULT 1,
  `actualizado_por`  INT NULL DEFAULT NULL,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_modvis_usuario`
    FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catalogo de modulos "ocultables" (se excluyen deliberadamente dashboard,
-- configuracion, perfil y las secciones ya restringidas a admin: users,
-- logs, databases, backups, modulos).
INSERT IGNORE INTO modulos_visibilidad (modulo, etiqueta, categoria, visible) VALUES
  ('statistics',   'Estadísticas',               'Panel Principal',             1),
  ('contactos',    'Contactos',                  'CRM',                         1),
  ('leads',        'Leads',                      'CRM',                         1),
  ('oportunidades','Oportunidades',              'CRM',                         1),
  ('presupuestos', 'Presupuestos',                'CRM',                         1),
  ('productos',    'Productos',                  'CRM',                         1),
  ('proveedores',  'Proveedores',                'CRM',                         1),
  ('facturas',     'Facturas',                   'CRM',                         1),
  ('file-manager', 'Administrador de Archivos',  'Archivos & Bases de Datos',   1),
  ('ftp',          'Cuentas FTP',                'Archivos & Bases de Datos',   1),
  ('ssl',          'Certificados SSL/TLS',       'Seguridad & SSL',             1),
  ('security',     'Seguridad',                  'Seguridad & SSL',             1),
  ('firewall',     'Firewall',                   'Seguridad & SSL',             1),
  ('email',        'Cuentas de Correo',          'Correo & Dominios',           1),
  ('domains',      'Dominios',                   'Correo & Dominios',           1);
