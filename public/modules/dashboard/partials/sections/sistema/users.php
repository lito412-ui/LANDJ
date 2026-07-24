<section id="users" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Gestión de Usuarios</h2>
            <p class="section-subtitle">Administra las cuentas de acceso al sistema</p>
        </div>
        <button class="btn-primary" id="users-nuevo-btn">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </button>
    </div>

    <!-- Panel crear / editar -->
    <div class="form-panel" id="users-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title" id="users-form-titulo">Nuevo Usuario</h3>
            </div>
            <form id="users-form" class="form-grid" novalidate>
                <div class="form-group">
                    <label class="form-label">Nombre de usuario <span class="form-required">*</span></label>
                    <input type="text" id="uf-nombre" class="form-input" placeholder="Nombre único de acceso" maxlength="100" autocomplete="off">
                    <span class="form-error" id="uerr-nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="text" id="uf-email" class="form-input" placeholder="correo@ejemplo.com" maxlength="255" autocomplete="off">
                    <span class="form-error" id="uerr-email"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Rol <span class="form-required">*</span></label>
                    <select id="uf-rol" class="form-input crm-select">
                        <option value="usuario">Usuario</option>
                        <option value="administrador">Administrador</option>
                    </select>
                    <span class="form-error" id="uerr-rol"></span>
                </div>
                <div class="form-group">
                    <label class="form-label" id="uf-pass-label">Contraseña <span class="form-required">*</span></label>
                    <input type="password" id="uf-password" class="form-input" placeholder="Mínimo 8 caracteres con letras y números" maxlength="72" autocomplete="new-password">
                    <span class="form-error" id="uerr-password"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="users-guardar-btn">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" class="btn-secondary" id="users-cancelar-btn">
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
            <input type="text" id="users-buscar" class="crm-search-input"
                   placeholder="Buscar por nombre o email...">
        </div>
        <div class="crm-filtros">
            <select id="users-filtro-rol" class="crm-select">
                <option value="">Todos los roles</option>
                <option value="usuario">Usuario</option>
                <option value="administrador">Administrador</option>
            </select>
        </div>
    </div>

    <!-- Tabla -->
    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Registrado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    <tr>
                        <td colspan="5" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
