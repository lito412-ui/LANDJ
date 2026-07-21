<section id="domains" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Dominios</h2>
            <p class="section-subtitle">Administra tus dominios y subdominios</p>
        </div>
        <button class="btn-primary" id="dom-nuevo-btn">
            <i class="fas fa-plus"></i> Añadir Dominio
        </button>
    </div>

    <!-- Panel crear / editar -->
    <div class="form-panel" id="dom-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="dom-form-titulo">Nuevo Dominio</h3>
            </div>
            <form id="dom-form" class="form-grid" novalidate>
                <div class="form-group full-width">
                    <label class="form-label">Dominio <span class="form-required">*</span></label>
                    <input type="text" id="df-dominio" class="form-input" placeholder="ejemplo.com" maxlength="255" autocomplete="off">
                    <span class="form-error" id="err-dom-dominio"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select id="df-tipo" class="form-input">
                        <option value="principal">Principal</option>
                        <option value="subdominio">Subdominio</option>
                        <option value="addon">Addon</option>
                        <option value="parked">Parked</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="df-estado" class="form-input">
                        <option value="pendiente">Pendiente</option>
                        <option value="activo">Activo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">IP asociada</label>
                    <input type="text" id="df-ip" class="form-input" placeholder="192.168.1.1" maxlength="45" autocomplete="off">
                    <span class="form-error" id="err-dom-ip"></span>
                </div>
                <div class="form-group dom-ssl-group">
                    <label class="form-label">SSL</label>
                    <label class="dom-ssl-toggle">
                        <input type="checkbox" id="df-ssl">
                        <span class="dom-ssl-label">Certificado SSL activo</span>
                    </label>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Notas</label>
                    <textarea id="df-notas" class="form-textarea" rows="3" placeholder="Observaciones..." maxlength="500"></textarea>
                    <div class="form-counter"><span id="dom-notas-count">0</span>/500</div>
                </div>
                <div class="form-actions">
                    <button type="submit" id="dom-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="dom-cancelar-btn" class="btn-secondary">
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
            <input type="text" id="dom-buscar" class="crm-search-input"
                   placeholder="Buscar dominio o IP...">
        </div>
        <div class="crm-filtros">
            <select id="dom-filtro-tipo" class="crm-select">
                <option value="">Todos los tipos</option>
                <option value="principal">Principal</option>
                <option value="subdominio">Subdominio</option>
                <option value="addon">Addon</option>
                <option value="parked">Parked</option>
            </select>
            <select id="dom-filtro-estado" class="crm-select">
                <option value="">Todos los estados</option>
                <option value="activo">Activo</option>
                <option value="pendiente">Pendiente</option>
                <option value="suspendido">Suspendido</option>
            </select>
            <button class="btn-filtros-toggle" id="dom-filtros-toggle" title="Filtros avanzados">
                <i class="fas fa-sliders-h"></i> Filtros
                <span class="filtros-badge" id="dom-filtros-badge" style="display:none">0</span>
            </button>
        </div>
    </div>
    <div class="crm-filtros-avanzados" id="dom-filtros-avanzados">
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Ordenar por</label>
            <div class="crm-orden-wrap">
                <select id="dom-filtro-orden" class="crm-select">
                    <option value="created_at">Fecha registro</option>
                    <option value="dominio">Dominio</option>
                    <option value="tipo">Tipo</option>
                    <option value="estado">Estado</option>
                </select>
                <button class="btn-dir" id="dom-filtro-dir" data-dir="desc" title="Dirección">
                    <i class="fas fa-sort-amount-down"></i>
                </button>
            </div>
        </div>
        <button class="btn-filtros-clear" id="dom-filtros-clear">
            <i class="fas fa-times"></i> Limpiar
        </button>
    </div>

    <!-- Tabla -->
    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Dominio</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>IP</th>
                        <th>SSL</th>
                        <th>Añadido</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="dom-tbody">
                    <tr>
                        <td colspan="7" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="dom-paginacion"></div>
</section>
