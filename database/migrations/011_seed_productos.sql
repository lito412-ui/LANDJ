-- Migración 011: productos y servicios de ejemplo
-- Se usa INSERT IGNORE porque `codigo` tiene UNIQUE, lo que la hace idempotente.

INSERT IGNORE INTO productos (codigo, nombre, descripcion, precio, iva_porcentaje, stock, activo, creado_por)
VALUES
  ('WEB-DISENO', 'Diseño de página web', 'Diseño y maquetación de sitio web corporativo (hasta 5 páginas).',
    1200.00, 21.00, 0.00, 1, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),

  ('HOST-ANUAL', 'Hosting anual', 'Alojamiento web con dominio incluido, facturación anual.',
    150.00, 21.00, 0.00, 1, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),

  ('MANT-MENSUAL', 'Mantenimiento mensual web', 'Actualizaciones, copias de seguridad y soporte mensual.',
    90.00, 21.00, 0.00, 1, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),

  ('CONSULT-HORA', 'Hora de consultoría', 'Consultoría técnica o estratégica facturada por hora.',
    60.00, 21.00, 0.00, 1, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),

  ('LICENCIA-SOFT', 'Licencia de software anual', 'Licencia anual de uso del software de gestión.',
    300.00, 21.00, 50.00, 1, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),

  ('FORMACION-ONLINE', 'Curso de formación online', 'Curso online de formación para el equipo del cliente.',
    250.00, 10.00, 0.00, 1, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),

  ('PORTATIL-14', 'Portátil 14" i5 16GB', 'Equipo portátil 14 pulgadas, Intel i5, 16GB RAM, 512GB SSD.',
    699.00, 21.00, 12.00, 1, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),

  ('RATON-INAL', 'Ratón inalámbrico', 'Ratón inalámbrico ergonómico, batería recargable.',
    19.90, 21.00, 80.00, 1, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1)),

  ('SOPORTE-PACK5', 'Bono de 5 horas de soporte', 'Bono prepago de 5 horas de soporte técnico, no acumulable.',
    250.00, 21.00, 0.00, 0, (SELECT id_usuario FROM usuarios WHERE nombre = 'admin' LIMIT 1));
