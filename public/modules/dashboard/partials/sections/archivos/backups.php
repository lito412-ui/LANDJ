<section id="backups" class="content-section">
    <div class="section-header">
        <h2 class="section-title">Copias de Seguridad</h2>
        <p class="section-subtitle">Genera y gestiona backups completos de la base de datos</p>
        <button class="btn-primary" id="backup-crear-btn">
            <i class="fas fa-plus"></i> Crear Backup
        </button>
    </div>

    <!-- Resumen -->
    <div class="backup-summary" id="backup-summary">
        <div class="backup-stat">
            <i class="fas fa-archive"></i>
            <div>
                <span class="backup-stat-value" id="backup-total">—</span>
                <span class="backup-stat-label">Backups guardados</span>
            </div>
        </div>
        <div class="backup-stat">
            <i class="fas fa-hdd"></i>
            <div>
                <span class="backup-stat-value" id="backup-size">—</span>
                <span class="backup-stat-label">Espacio total</span>
            </div>
        </div>
        <div class="backup-stat">
            <i class="fas fa-clock"></i>
            <div>
                <span class="backup-stat-value" id="backup-ultimo">—</span>
                <span class="backup-stat-label">Último backup</span>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Backups disponibles</h3>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Tamaño</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="backup-tbody">
                    <tr><td colspan="4" class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
