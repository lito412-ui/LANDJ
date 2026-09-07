<section id="proveedores" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Proveedores</h2>
            <p class="section-subtitle">Gestiona las empresas y personas a las que compras productos o servicios</p>
        </div>
        <button class="btn-primary" id="proveedores-nuevo-btn">
            <i class="fas fa-plus"></i> Nuevo Proveedor
        </button>
    </div>

    <!-- Panel crear / editar -->
    <div class="form-panel" id="proveedores-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="proveedores-form-titulo">Nuevo Proveedor</h3>
            </div>
            <form id="proveedores-form" class="form-grid" novalidate>
                <div class="form-group">
                    <label class="form-label">Nombre <span class="form-required">*</span></label>
                    <input type="text" id="pv-nombre" class="form-input" placeholder="Nombre o razón social" maxlength="150" autocomplete="off">
                    <span class="form-error" id="err-pv-nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">NIF/CIF</label>
                    <input type="text" id="pv-nif" class="form-input" placeholder="B12345678" maxlength="9" autocomplete="off">
                    <span class="form-error" id="err-pv-nif"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" id="pv-email" class="form-input" placeholder="contacto@proveedor.com" maxlength="150" autocomplete="off">
                    <span class="form-error" id="err-pv-email"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" id="pv-telefono" class="form-input" placeholder="900 000 000" maxlength="30" autocomplete="off">
                    <span class="form-error" id="err-pv-telefono"></span>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Dirección</label>
                    <input type="text" id="pv-direccion" class="form-input" placeholder="Calle, número, ciudad..." maxlength="255" autocomplete="off">
                    <span class="form-error" id="err-pv-direccion"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Persona de contacto</label>
                    <input type="text" id="pv-contacto-referencia" class="form-input" placeholder="Nombre de tu contacto en la empresa" maxlength="150" autocomplete="off">
                    <span class="form-error" id="err-pv-contacto-referencia"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="pv-activo" class="form-input">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Notas</label>
                    <textarea id="pv-notas" class="form-textarea" rows="3" placeholder="Condiciones de pago, plazos de entrega..." maxlength="1000"></textarea>
                    <span class="form-error" id="err-pv-notas"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" id="proveedores-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="proveedores-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel detalle -->
    <div class="detalle-overlay" id="proveedor-detalle-overlay" style="display:none">
        <div class="detalle-panel" id="proveedor-detalle-panel">
            <div class="detalle-header">
                <div class="detalle-avatar" id="pv-det-avatar"><i class="fas fa-truck"></i></div>
                <div class="detalle-header-info">
                    <h3 id="pv-det-nombre"></h3>
                    <span id="pv-det-nif" class="detalle-empresa"></span>
                </div>
                <div class="detalle-header-actions">
                    <button class="btn-icon" id="pv-det-editar-btn" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon danger" id="pv-det-eliminar-btn" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="btn-icon" id="pv-det-cerrar-btn" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="detalle-body">
                <div class="detalle-campos">
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="detalle-valor" id="pv-det-email"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-phone"></i> Teléfono</span>
                        <span class="detalle-valor" id="pv-det-telefono"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-map-marker-alt"></i> Dirección</span>
                        <span class="detalle-valor" id="pv-det-direccion"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-user"></i> Contacto</span>
                        <span class="detalle-valor" id="pv-det-contacto-referencia"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-tag"></i> Estado</span>
                        <span class="detalle-valor" id="pv-det-estado"></span>
                    </div>
                    <div class="detalle-campo">
                        <span class="detalle-label"><i class="fas fa-calendar-alt"></i> Registrado</span>
                        <span class="detalle-valor" id="pv-det-fecha"></span>
                    </div>
                </div>

                <div class="detalle-notas" id="pv-det-notas-bloque">
                    <span class="detalle-label"><i class="fas fa-sticky-note"></i> Notas</span>
                    <p id="pv-det-notas"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Buscador + filtros -->
    <div class="crm-toolbar">
        <div class="crm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="proveedores-buscar" class="crm-search-input"
                   placeholder="Buscar por nombre, NIF o email...">
        </div>
        <div class="crm-filtros">
            <button class="btn-secondary btn-sm" id="proveedores-exportar-btn" title="Exportar a CSV">
                <i class="fas fa-file-export"></i> Exportar
            </button>
            <button class="btn-secondary btn-sm" id="proveedores-importar-btn" title="Importar desde CSV">
                <i class="fas fa-file-import"></i> Importar
            </button>
            <input type="file" id="proveedores-importar-input" accept=".csv" hidden>
            <select id="pv-filtro-activo" class="crm-select">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </div>
    </div>

    <!-- Tabla -->
    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>NIF</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="proveedores-tbody">
                    <tr>
                        <td colspan="6" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="pv-paginacion"></div>
</section>
