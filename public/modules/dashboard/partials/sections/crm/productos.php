<section id="productos" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Productos</h2>
            <p class="section-subtitle">Gestiona productos y servicios para tus ventas</p>
        </div>
        <button class="btn-primary" id="productos-nuevo-btn">
            <i class="fas fa-plus"></i> Nuevo Producto
        </button>
    </div>

    <div class="form-panel" id="productos-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="productos-form-titulo">Nuevo Producto</h3>
            </div>
            <form id="productos-form" class="form-grid" novalidate>
                <div class="form-group">
                    <label class="form-label">Codigo</label>
                    <input type="text" id="prod-codigo" class="form-input" maxlength="40" placeholder="SERV-001">
                    <span class="form-error" id="err-prod-codigo"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre <span class="form-required">*</span></label>
                    <input type="text" id="prod-nombre" class="form-input" maxlength="150" placeholder="Nombre del producto o servicio">
                    <span class="form-error" id="err-prod-nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio <span class="form-required">*</span></label>
                    <input type="number" id="prod-precio" class="form-input" min="0" step="0.01" value="0.00">
                    <span class="form-error" id="err-prod-precio"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">IVA %</label>
                    <input type="number" id="prod-iva" class="form-input" min="0" max="100" step="0.01" value="21.00">
                    <span class="form-error" id="err-prod-iva"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock</label>
                    <input type="number" id="prod-stock" class="form-input" min="0" step="0.01" value="0.00">
                    <span class="form-error" id="err-prod-stock"></span>
                </div>
                <div class="form-group dom-ssl-group">
                    <label class="dom-ssl-toggle">
                        <input type="checkbox" id="prod-activo" checked>
                        <span class="dom-ssl-label">Producto activo</span>
                    </label>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Descripcion</label>
                    <textarea id="prod-descripcion" class="form-textarea" rows="3" maxlength="1000" placeholder="Detalles visibles para uso interno..."></textarea>
                    <div class="form-counter"><span id="prod-desc-count">0</span>/1000</div>
                    <span class="form-error" id="err-prod-descripcion"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" id="productos-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="productos-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="crm-toolbar">
        <div class="crm-search">
            <i class="fas fa-search"></i>
            <input type="text" id="productos-buscar" class="crm-search-input"
                   placeholder="Buscar por codigo, nombre o descripcion...">
        </div>
        <div class="crm-filtros">
            <select id="productos-filtro-activo" class="crm-select">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
            <button class="btn-filtros-toggle" id="prod-filtros-toggle" title="Filtros avanzados">
                <i class="fas fa-sliders-h"></i> Filtros
                <span class="filtros-badge" id="prod-filtros-badge" style="display:none">0</span>
            </button>
        </div>
    </div>

    <div class="crm-filtros-avanzados" id="prod-filtros-avanzados">
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Ordenar por</label>
            <div class="crm-orden-wrap">
                <select id="prod-filtro-orden" class="crm-select">
                    <option value="created_at">Fecha alta</option>
                    <option value="nombre">Nombre</option>
                    <option value="codigo">Codigo</option>
                    <option value="precio">Precio</option>
                    <option value="stock">Stock</option>
                    <option value="activo">Estado</option>
                </select>
                <button class="btn-dir" id="prod-filtro-dir" data-dir="desc" title="Direccion">
                    <i class="fas fa-sort-amount-down"></i>
                </button>
            </div>
        </div>
        <button class="btn-filtros-clear" id="prod-filtros-clear"><i class="fas fa-times"></i> Limpiar</button>
    </div>

    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Codigo</th>
                        <th>Precio</th>
                        <th>IVA</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="productos-tbody">
                    <tr>
                        <td colspan="7" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="prod-paginacion"></div>
</section>
