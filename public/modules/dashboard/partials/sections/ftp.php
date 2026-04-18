<section id="ftp" class="content-section">
    <div class="section-header">
        <h2 class="section-title">Cuentas FTP</h2>
        <p class="section-subtitle">Gestiona las cuentas FTP para transferencia de archivos</p>
        <button class="btn-primary" id="create-ftp-btn">
            <i class="fas fa-plus"></i>
            Crear Nueva Cuenta FTP
        </button>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Cuentas FTP Existentes</h3>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Directorio</th>
                        <th>Cuota</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="user-info">
                                <i class="fas fa-user"></i>
                                <span>ftp_usuario1</span>
                            </div>
                        </td>
                        <td>/public_html/uploads</td>
                        <td>500 MB</td>
                        <td><span class="status-badge active">Activo</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon" title="Editar"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="user-info">
                                <i class="fas fa-user"></i>
                                <span>backup_ftp</span>
                            </div>
                        </td>
                        <td>/backups</td>
                        <td>2 GB</td>
                        <td><span class="status-badge active">Activo</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon" title="Editar"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="content-card" id="ftp-form-card" style="display: none;">
        <div class="card-header">
            <h3 class="card-title">Crear Nueva Cuenta FTP</h3>
        </div>
        <form class="form-grid" id="ftp-form">
            <div class="form-group">
                <label for="ftp-username" class="form-label">Nombre de Usuario</label>
                <input type="text" id="ftp-username" class="form-input" placeholder="Ingresa el nombre de usuario FTP" required>
            </div>
            <div class="form-group">
                <label for="ftp-password" class="form-label">Contraseña</label>
                <input type="password" id="ftp-password" class="form-input" placeholder="Contraseña segura" required>
            </div>
            <div class="form-group">
                <label for="ftp-directory" class="form-label">Directorio</label>
                <input type="text" id="ftp-directory" class="form-input" placeholder="/public_html/" required>
            </div>
            <div class="form-group">
                <label for="ftp-quota" class="form-label">Cuota (MB)</label>
                <input type="number" id="ftp-quota" class="form-input" placeholder="500" min="1" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i>
                    Crear Cuenta FTP
                </button>
                <button type="button" class="btn-secondary" id="cancel-ftp-btn">Cancelar</button>
            </div>
        </form>
    </div>
</section>
