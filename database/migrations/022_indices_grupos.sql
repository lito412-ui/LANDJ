-- Acelera las consultas filtradas por organización. Esta migración se ejecuta
-- una sola vez y complementa la creación de columnas de la migración 021.
CREATE INDEX idx_usuarios_grupo ON usuarios (id_grupo);
CREATE INDEX idx_contactos_grupo ON contactos (id_grupo);
CREATE INDEX idx_leads_grupo ON leads (id_grupo);
CREATE INDEX idx_oportunidades_grupo ON oportunidades (id_grupo);
CREATE INDEX idx_actividades_grupo ON actividades (id_grupo);
CREATE INDEX idx_presupuestos_grupo ON presupuestos (id_grupo);
CREATE INDEX idx_facturas_grupo ON facturas (id_grupo);
CREATE INDEX idx_productos_grupo ON productos (id_grupo);
CREATE INDEX idx_proveedores_grupo ON proveedores (id_grupo);
CREATE INDEX idx_avisos_grupo ON avisos (id_grupo);
