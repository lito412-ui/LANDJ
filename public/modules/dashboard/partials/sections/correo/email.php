<section id="email" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Cuentas de Correo</h2>
            <p class="section-subtitle">Gestiona las cuentas de correo electrónico</p>
        </div>
        <button class="btn-primary" id="email-nuevo-btn">
            <i class="fas fa-plus"></i> Nueva Cuenta
        </button>
    </div>

    <!-- Panel crear / editar -->
    <div class="form-panel" id="email-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="email-form-titulo">Nueva Cuenta</h3>
            </div>
            <form id="email-form" class="form-grid" novalidate>
                <div class="form-group full-width">
                    <label class="form-label">Dirección de correo <span class="form-required">*</span></label>
                    <input type="text" id="ef-email" class="form-input" placeholder="usuario@dominio.com" maxlength="255" autocomplete="off">
                    <span class="form-error" id="err-ef-email"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Cuota (MB)</label>
                    <input type="number" id="ef-cuota" class="form-input" placeholder="500" min="0" max="99999" value="500">
                    <span class="form-hint">0 = sin límite</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select id="ef-estado" class="form-input">
                        <option value="activo">Activo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Notas</label>
                    <textarea id="ef-notas" class="form-textarea" rows="3" placeholder="Observaciones..." maxlength="500"></textarea>
                    <div class="form-counter"><span id="email-notas-count">0</span>/500</div>
                </div>
                <div class="form-actions">
                    <button type="submit" id="email-guardar-btn" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" id="email-cancelar-btn" class="btn-secondary">
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
            <input type="text" id="email-buscar" class="crm-search-input"
                   placeholder="Buscar por email o dominio...">
        </div>
        <div class="crm-filtros">
            <select id="email-filtro-estado" class="crm-select">
                <option value="">Todos los estados</option>
                <option value="activo">Activo</option>
                <option value="suspendido">Suspendido</option>
            </select>
            <button class="btn-filtros-toggle" id="email-filtros-toggle" title="Filtros avanzados">
                <i class="fas fa-sliders-h"></i> Filtros
                <span class="filtros-badge" id="email-filtros-badge" style="display:none">0</span>
            </button>
        </div>
    </div>
    <div class="crm-filtros-avanzados" id="email-filtros-avanzados">
        <div class="crm-filtro-grupo">
            <label class="crm-filtro-label">Ordenar por</label>
            <div class="crm-orden-wrap">
                <select id="email-filtro-orden" class="crm-select">
                    <option value="created_at">Fecha registro</option>
                    <option value="email">Email</option>
                    <option value="dominio">Dominio</option>
                    <option value="cuota">Cuota</option>
                    <option value="estado">Estado</option>
                </select>
                <button class="btn-dir" id="email-filtro-dir" data-dir="desc" title="Dirección">
                    <i class="fas fa-sort-amount-down"></i>
                </button>
            </div>
        </div>
        <button class="btn-filtros-clear" id="email-filtros-clear">
            <i class="fas fa-times"></i> Limpiar
        </button>
    </div>

    <!-- Tabla -->
    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Dominio</th>
                        <th>Cuota</th>
                        <th>Estado</th>
                        <th>Creada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="email-tbody">
                    <tr>
                        <td colspan="6" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="crm-paginacion" id="email-paginacion"></div>
</section>
