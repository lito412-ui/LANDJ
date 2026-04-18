SET NAMES utf8mb4;

-- ─── Usuarios ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario`      INT          AUTO_INCREMENT PRIMARY KEY,
  `nombre`          VARCHAR(100) NOT NULL UNIQUE,
  `email`           VARCHAR(255) DEFAULT NULL UNIQUE,
  `contraseña_hash` VARCHAR(255) NOT NULL,
  `rol`             VARCHAR(50)  NOT NULL DEFAULT 'usuario',
  `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Contactos ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contactos` (
  `id_contacto`  INT           AUTO_INCREMENT PRIMARY KEY,
  `nombre`       VARCHAR(100)  NOT NULL,
  `apellidos`    VARCHAR(100)  DEFAULT NULL,
  `email`        VARCHAR(255)  DEFAULT NULL UNIQUE,
  `telefono`     VARCHAR(30)   DEFAULT NULL,
  `empresa`      VARCHAR(150)  DEFAULT NULL,
  `notas`        TEXT          DEFAULT NULL,
  `creado_por`   INT           NOT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_contactos_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Leads ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `leads` (
  `id_lead`      INT           AUTO_INCREMENT PRIMARY KEY,
  `nombre`       VARCHAR(100)  NOT NULL,
  `email`        VARCHAR(255)  DEFAULT NULL,
  `telefono`     VARCHAR(30)   DEFAULT NULL,
  `empresa`      VARCHAR(150)  DEFAULT NULL,
  `origen`       VARCHAR(50)   DEFAULT NULL,
  `estado`       ENUM('nuevo','contactado','calificado','convertido','descartado') NOT NULL DEFAULT 'nuevo',
  `notas`        TEXT          DEFAULT NULL,
  `contacto_id`  INT           DEFAULT NULL,
  `creado_por`   INT           NOT NULL,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_leads_usuario`   FOREIGN KEY (`creado_por`)   REFERENCES `usuarios`(`id_usuario`)  ON DELETE RESTRICT,
  CONSTRAINT `fk_leads_contacto`  FOREIGN KEY (`contacto_id`)  REFERENCES `contactos`(`id_contacto`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Oportunidades ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `oportunidades` (
  `id_oportunidad`       INT            AUTO_INCREMENT PRIMARY KEY,
  `titulo`               VARCHAR(150)   NOT NULL,
  `descripcion`          TEXT           DEFAULT NULL,
  `valor`                DECIMAL(12,2)  DEFAULT NULL,
  `etapa`                ENUM('prospecto','propuesta','negociacion','cerrada_ganada','cerrada_perdida') NOT NULL DEFAULT 'prospecto',
  `contacto_id`          INT            DEFAULT NULL,
  `lead_id`              INT            DEFAULT NULL,
  `asignado_a`           INT            DEFAULT NULL,
  `creado_por`           INT            NOT NULL,
  `fecha_cierre_esperada` DATE          DEFAULT NULL,
  `created_at`           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_opor_contacto`   FOREIGN KEY (`contacto_id`) REFERENCES `contactos`(`id_contacto`)  ON DELETE SET NULL,
  CONSTRAINT `fk_opor_lead`       FOREIGN KEY (`lead_id`)     REFERENCES `leads`(`id_lead`)           ON DELETE SET NULL,
  CONSTRAINT `fk_opor_asignado`   FOREIGN KEY (`asignado_a`)  REFERENCES `usuarios`(`id_usuario`)     ON DELETE SET NULL,
  CONSTRAINT `fk_opor_creador`    FOREIGN KEY (`creado_por`)  REFERENCES `usuarios`(`id_usuario`)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Actividades ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `actividades` (
  `id_actividad`    INT       AUTO_INCREMENT PRIMARY KEY,
  `tipo`            ENUM('nota','llamada','reunion','tarea','email') NOT NULL DEFAULT 'nota',
  `descripcion`     TEXT      NOT NULL,
  `fecha`           DATETIME  DEFAULT NULL,
  `completada`      TINYINT(1) NOT NULL DEFAULT 0,
  `contacto_id`     INT       DEFAULT NULL,
  `lead_id`         INT       DEFAULT NULL,
  `oportunidad_id`  INT       DEFAULT NULL,
  `creado_por`      INT       NOT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_act_contacto`    FOREIGN KEY (`contacto_id`)    REFERENCES `contactos`(`id_contacto`)       ON DELETE SET NULL,
  CONSTRAINT `fk_act_lead`        FOREIGN KEY (`lead_id`)        REFERENCES `leads`(`id_lead`)               ON DELETE SET NULL,
  CONSTRAINT `fk_act_oportunidad` FOREIGN KEY (`oportunidad_id`) REFERENCES `oportunidades`(`id_oportunidad`) ON DELETE SET NULL,
  CONSTRAINT `fk_act_creador`     FOREIGN KEY (`creado_por`)     REFERENCES `usuarios`(`id_usuario`)         ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
