<section id="leads" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Leads</h2>
            <p class="section-subtitle">Gestiona tus prospectos y oportunidades de negocio</p>
        </div>
        <button class="btn-primary" id="leads-nuevo-btn">
            <i class="fas fa-plus"></i> Nuevo Lead
        </button>
    </div>

    <!-- Panel crear / editar -->
    <div class="form-panel" id="leads-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="leads-form-titulo">Nuevo Lead</h3>
            </div>
            <form id="leads-form" class="form-grid" novalidate>
                <div class="form-group">
                    <label class="form-label">Nombre <span class="form-required">*</span></label>
                    <input type="text" id="lf-nombre" class="form-input" placeholder="Nombre completo" maxlength="100" autocomplete="off">
                    <span class="form-error" id="lerr-nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" id="lf-email" class="form-input" placeholder="correo@ejemplo.com" maxlength="255" autocomplete="off">
                    <span class="form-error" id="lerr-email"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" id="lf-telefono" class="form-input" placeholder="612 345 678" maxlength="30" autocomplete="off">
                    <span class="form-error" id="lerr-telefono"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Empresa</label>
                    <input type="text" id="lf-empresa" class="form-input" placeholder="Nombre de la empresa" maxlength="150" autocomplete="off">
                    <span class="form-error" id="lerr-empresa"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Origen</label>
                    <input type="text" id="lf-origen" class="form-input" placeholder="web, referido, evento..." maxlength="50" autocomplete="off">
                    <span class="form-error" id="lerr-origen"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="lf-estado" class="form-input">
                        <option value="nuevo">Nuevo</option>
                        <option value="contactado">Contactado</option>
                        <option value="calificado">Calificado</option>
                        <option value="convertido">Convertido</option>
                        <option value="descartado">Descartado</option>
                    </select>
                    <span class="form-error" id="lerr-estado"></span>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Notas</label>
                    <textarea id="lf-notas" class="form-textarea" rows="3" placeholder="Observaciones sobre este lead..." maxlength="500"></textarea>
                    <div class="form-counter"><span id="lnotas-count">0</span>/500</div>
                    <span class="form-error" id="lerr-notas"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" id="leads-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="leads-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="crm-toolbar">
        <div class="crm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="leads-buscar" class="crm-search-input"
                   placeholder="Buscar por nombre, email o empresa...">
        </div>
        <div class="crm-filtros">
            <select id="leads-filtro-estado" class="crm-select">
                <option value="">Todos los estados</option>
                <option value="nuevo">Nuevo</option>
                <option value="contactado">Contactado</option>
                <option value="calificado">Calificado</option>
                <option value="convertido">Convertido</option>
                <option value="descartado">Descartado</option>
            </select>
        </div>
    </div>

    <!-- Tabla -->
    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Origen</th>
                        <th>Estado</th>
                        <th>Registrado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="leads-tbody">
                    <tr>
                        <td colspan="7" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel detalle -->
    <div class="detalle-overlay" id="lead-detalle-overlay" style="display:none">
        <div class="detalle-panel" id="lead-detalle-panel">
            <div class="detalle-header">
                <div class="detalle-avatar lead-avatar" id="ldet-avatar"></div>
                <div class="detalle-header-info">
                    <h3 id="ldet-nombre"></h3>
                    <span id="ldet-empresa" class="detalle-empresa"></span>
                </div>
                <div class="detalle-header-actions">
                    <button class="btn-icon" id="ldet-editar-btn" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon danger" id="ldet-eliminar-btn" title="Eliminar"><i class="fas fa-trash"></i></button>
                    <button class="btn-icon" id="ldet-cerrar-btn" title="Cerrar"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="detalle-body">
                <div id="ldet-estado-bloque"></div>
                <div class="detalle-campos">
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="detalle-valor" id="ldet-email"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-phone"></i> Teléfono</span>
                        <span class="detalle-valor" id="ldet-telefono"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-building"></i> Empresa</span>
                        <span class="detalle-valor" id="ldet-empresa-campo"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-tag"></i> Origen</span>
                        <span class="detalle-valor" id="ldet-origen"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-calendar-alt"></i> Registrado</span>
                        <span class="detalle-valor" id="ldet-fecha"></span>
                    </div>
                </div>
                <div class="detalle-notas" id="ldet-notas-bloque">
                    <span class="detalle-label"><i class="fas fa-sticky-note"></i> Notas</span>
                    <p id="ldet-notas"></p>
                </div>
            </div>
        </div>
    </div>
</section>
