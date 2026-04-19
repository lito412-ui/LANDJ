-- Migración 003: datos de ejemplo para CRM (contactos, leads, oportunidades, actividades)

-- ─── Contactos ────────────────────────────────────────────────────────────────
INSERT IGNORE INTO contactos (nombre, apellidos, email, telefono, empresa, creado_por)
VALUES
  ('María',  'García López',  'maria.garcia@ejemplo.com',   '612345678', 'Soluciones Tech SL',    (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')),
  ('Carlos', 'Martínez Ruiz', 'carlos.martinez@empresa.es', '623456789', 'Grupo Comercial Norte', (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')),
  ('Ana',    'Fernández Gil',  'ana.fernandez@pyme.es',      '634567890', 'Diseños Creativos',     (SELECT id_usuario FROM usuarios WHERE nombre = 'admin'));

-- ─── Leads ────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO leads (nombre, email, empresa, origen, estado, creado_por)
VALUES
  ('Roberto Sanz', 'roberto.sanz@nuevo.es',   'Importaciones Sanz', 'web',      'nuevo',       (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')),
  ('Laura Vidal',  'laura.vidal@contacto.es', 'Eventos Vidal',      'referido', 'contactado',  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')),
  ('Pedro Romero', 'pedro.romero@leads.es',   'Romero & Asociados', 'web',      'calificado',  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin'));

-- ─── Oportunidades ────────────────────────────────────────────────────────────
INSERT IGNORE INTO oportunidades (titulo, valor, etapa, contacto_id, asignado_a, creado_por, fecha_cierre_esperada)
VALUES
  (
    'Proyecto web corporativo', 4500.00, 'propuesta',
    (SELECT id_contacto FROM contactos WHERE email = 'maria.garcia@ejemplo.com'),
    (SELECT id_usuario FROM usuarios WHERE nombre = 'admin'),
    (SELECT id_usuario FROM usuarios WHERE nombre = 'admin'),
    DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  ),
  (
    'Mantenimiento anual software', 1200.00, 'negociacion',
    (SELECT id_contacto FROM contactos WHERE email = 'carlos.martinez@empresa.es'),
    (SELECT id_usuario FROM usuarios WHERE nombre = 'admin'),
    (SELECT id_usuario FROM usuarios WHERE nombre = 'admin'),
    DATE_ADD(CURDATE(), INTERVAL 15 DAY)
  ),
  (
    'Consultoría estratégica', 2800.00, 'prospecto',
    (SELECT id_contacto FROM contactos WHERE email = 'ana.fernandez@pyme.es'),
    (SELECT id_usuario FROM usuarios WHERE nombre = 'admin'),
    (SELECT id_usuario FROM usuarios WHERE nombre = 'admin'),
    DATE_ADD(CURDATE(), INTERVAL 60 DAY)
  );

-- ─── Actividades ──────────────────────────────────────────────────────────────
INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por)
SELECT 'llamada', 'Llamada de presentación inicial.', NOW(),
  (SELECT id_contacto FROM contactos WHERE email = 'maria.garcia@ejemplo.com'),
  NULL, NULL,
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')
WHERE NOT EXISTS (
  SELECT 1 FROM actividades WHERE descripcion = 'Llamada de presentación inicial.'
    AND creado_por = (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')
);

INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por)
SELECT 'nota', 'Interesado en el servicio premium.', NOW(),
  NULL,
  (SELECT id_lead FROM leads WHERE email = 'laura.vidal@contacto.es'),
  NULL,
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')
WHERE NOT EXISTS (
  SELECT 1 FROM actividades WHERE descripcion = 'Interesado en el servicio premium.'
    AND creado_por = (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')
);

INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por)
SELECT 'reunion', 'Reunión de seguimiento del presupuesto.', NOW(),
  (SELECT id_contacto FROM contactos WHERE email = 'carlos.martinez@empresa.es'),
  NULL,
  (SELECT id_oportunidad FROM oportunidades WHERE titulo = 'Proyecto web corporativo'),
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')
WHERE NOT EXISTS (
  SELECT 1 FROM actividades WHERE descripcion = 'Reunión de seguimiento del presupuesto.'
    AND creado_por = (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')
);

INSERT INTO actividades (tipo, descripcion, fecha, contacto_id, lead_id, oportunidad_id, creado_por)
SELECT 'tarea', 'Enviar propuesta detallada por email.', NOW(),
  NULL, NULL,
  (SELECT id_oportunidad FROM oportunidades WHERE titulo = 'Mantenimiento anual software'),
  (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')
WHERE NOT EXISTS (
  SELECT 1 FROM actividades WHERE descripcion = 'Enviar propuesta detallada por email.'
    AND creado_por = (SELECT id_usuario FROM usuarios WHERE nombre = 'admin')
);
