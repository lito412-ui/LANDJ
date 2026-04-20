-- Migración 004: datos de ejemplo para CRM (contactos, leads, oportunidades, actividades)

-- ─── Contactos ────────────────────────────────────────────────────────────────
-- email tiene UNIQUE, INSERT IGNORE es suficiente
INSERT IGNORE INTO contactos (nombre, apellidos, email, telefono, empresa, creado_por)
VALUES
  ('María',  'García López',  'maria.garcia@ejemplo.com',   '612345678', 'Soluciones Tech SL',    (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),
  ('Carlos', 'Martínez Ruiz', 'carlos.martinez@empresa.es', '623456789', 'Grupo Comercial Norte', (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),
  ('Ana',    'Fernández Gil',  'ana.fernandez@pyme.es',      '634567890', 'Diseños Creativos',     (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1));

-- ─── Leads ────────────────────────────────────────────────────────────────────
-- leads.email no tiene UNIQUE, se usa WHERE NOT EXISTS para idempotencia
INSERT INTO leads (nombre, email, empresa, origen, estado, creado_por)
SELECT 'Roberto Sanz', 'roberto.sanz@nuevo.es', 'Importaciones Sanz', 'web', 'nuevo',
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM leads WHERE email = 'roberto.sanz@nuevo.es');

INSERT INTO leads (nombre, email, empresa, origen, estado, creado_por)
SELECT 'Laura Vidal', 'laura.vidal@contacto.es', 'Eventos Vidal', 'referido', 'contactado',
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM leads WHERE email = 'laura.vidal@contacto.es');

INSERT INTO leads (nombre, email, empresa, origen, estado, creado_por)
SELECT 'Pedro Romero', 'pedro.romero@leads.es', 'Romero & Asociados', 'web', 'calificado',
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM leads WHERE email = 'pedro.romero@leads.es');

-- ─── Oportunidades ────────────────────────────────────────────────────────────
-- oportunidades.titulo no tiene UNIQUE, se usa WHERE NOT EXISTS para idempotencia
INSERT INTO oportunidades (titulo, valor, etapa, contacto_id, asignado_a, creado_por, fecha_cierre_esperada)
SELECT 'Proyecto web corporativo', 4500.00, 'propuesta',
  (SELECT id_contacto FROM contactos WHERE email = 'maria.garcia@ejemplo.com' LIMIT 1),
  (SELECT id_usuario  FROM usuarios  WHERE nombre = 'admin' LIMIT 1),
  (SELECT id_usuario  FROM usuarios  WHERE nombre = 'admin' LIMIT 1),
  DATE_ADD(CURDATE(), INTERVAL 30 DAY)
WHERE NOT EXISTS (SELECT 1 FROM oportunidades WHERE titulo = 'Proyecto web corporativo');

INSERT INTO oportunidades (titulo, valor, etapa, contacto_id, asignado_a, creado_por, fecha_cierre_esperada)
SELECT 'Mantenimiento anual software', 1200.00, 'negociacion',
  (SELECT id_contacto FROM contactos WHERE email = 'carlos.martinez@empresa.es' LIMIT 1),
  (SELECT id_usuario  FROM usuarios  WHERE nombre = 'admin' LIMIT 1),
  (SELECT id_usuario  FROM usuarios  WHERE nombre = 'admin' LIMIT 1),
  DATE_ADD(CURDATE(), INTERVAL 15 DAY)
WHERE NOT EXISTS (SELECT 1 FROM oportunidades WHERE titulo = 'Mantenimiento anual software');

INSERT INTO oportunidades (titulo, valor, etapa, contacto_id, asignado_a, creado_por, fecha_cierre_esperada)
SELECT 'Consultoría estratégica', 2800.00, 'prospecto',
  (SELECT id_contacto FROM contactos WHERE email = 'ana.fernandez@pyme.es' LIMIT 1),
  (SELECT id_usuario  FROM usuarios  WHERE nombre = 'admin' LIMIT 1),
  (SELECT id_usuario  FROM usuarios  WHERE nombre = 'admin' LIMIT 1),
  DATE_ADD(CURDATE(), INTERVAL 60 DAY)
WHERE NOT EXISTS (SELECT 1 FROM oportunidades WHERE titulo = 'Consultoría estratégica');

-- ─── Actividades ──────────────────────────────────────────────────────────────
INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por)
SELECT 'llamada', 'Llamada de presentación inicial.', NOW(),
  (SELECT id_contacto FROM contactos WHERE email = 'maria.garcia@ejemplo.com' LIMIT 1),
  NULL, NULL,
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
WHERE NOT EXISTS (
  SELECT 1 FROM actividades WHERE descripcion = 'Llamada de presentación inicial.'
    AND creado_por = (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
);

INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por)
SELECT 'nota', 'Interesado en el servicio premium.', NOW(),
  NULL,
  (SELECT id_lead FROM leads WHERE email = 'laura.vidal@contacto.es' LIMIT 1),
  NULL,
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
WHERE NOT EXISTS (
  SELECT 1 FROM actividades WHERE descripcion = 'Interesado en el servicio premium.'
    AND creado_por = (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
);

INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por)
SELECT 'reunion', 'Reunión de seguimiento del presupuesto.', NOW(),
  (SELECT id_contacto FROM contactos WHERE email = 'carlos.martinez@empresa.es' LIMIT 1),
  NULL,
  (SELECT id_oportunidad FROM oportunidades WHERE titulo = 'Proyecto web corporativo' LIMIT 1),
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
WHERE NOT EXISTS (
  SELECT 1 FROM actividades WHERE descripcion = 'Reunión de seguimiento del presupuesto.'
    AND creado_por = (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
);

INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por)
SELECT 'tarea', 'Enviar propuesta detallada por email.', NOW(),
  NULL, NULL,
  (SELECT id_oportunidad FROM oportunidades WHERE titulo = 'Mantenimiento anual software' LIMIT 1),
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
WHERE NOT EXISTS (
  SELECT 1 FROM actividades WHERE descripcion = 'Enviar propuesta detallada por email.'
    AND creado_por = (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)
);
