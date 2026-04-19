<section id="logs" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Auditoría</h2>
            <p class="section-subtitle">Historial de cambios en todas las entidades del CRM</p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="crm-toolbar">
        <div class="crm-filtros">
            <select id="audit-filtro-tabla" class="crm-select">
                <option value="">Todas las entidades</option>
                <option value="contactos">Contactos</option>
                <option value="leads">Leads</option>
                <option value="oportunidades">Oportunidades</option>
                <option value="actividades">Actividades</option>
                <option value="usuarios">Usuarios</option>
            </select>
            <select id="audit-filtro-accion" class="crm-select">
                <option value="">Todas las acciones</option>
                <option value="crear">Crear</option>
                <option value="editar">Editar</option>
                <option value="eliminar">Eliminar</option>
            </select>
        </div>
    </div>

    <!-- Tabla -->
    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Entidad</th>
                        <th>ID</th>
                        <th>Acción</th>
                        <th>Cambios</th>
                    </tr>
                </thead>
                <tbody id="audit-tbody">
                    <tr>
                        <td colspan="6" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="audit-paginacion"></div>
</section>
