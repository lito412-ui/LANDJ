SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario`      INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre`          VARCHAR(100) NOT NULL UNIQUE,
  `email`           VARCHAR(255) DEFAULT NULL UNIQUE,
  `contraseña_hash` VARCHAR(255) NOT NULL,
  `rol`             VARCHAR(50)  NOT NULL DEFAULT 'usuario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
