<section id="dashboard" class="content-section active">
    <div class="section-header">
        <h2 class="section-title">Dashboard</h2>
        <p class="section-subtitle">Resumen general del estado de tu servidor</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-hdd"></i></div>
            <div class="stat-content">
                <h3 class="stat-value" id="disco-texto">0%</h3>
                <p class="stat-label">Uso de Disco</p>
                <div class="stat-progress">
                    <div class="stat-progress-bar" id="disco-barra" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-memory"></i></div>
            <div class="stat-content">
                <h3 class="stat-value" id="ram-texto">0%</h3>
                <p class="stat-label">Uso de RAM</p>
                <div class="stat-progress">
                    <div class="stat-progress-bar" id="ram-barra" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-microchip"></i></div>
            <div class="stat-content">
                <h3 class="stat-value" id="cpu-texto">0%</h3>
                <p class="stat-label">Uso de CPU</p>
                <div class="stat-progress">
                    <div class="stat-progress-bar" id="cpu-barra" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-content">
                <h3 class="stat-value">5</h3>
                <p class="stat-label">Dominios Activos</p>
            </div>
        </div>
    </div>

    <div class="quick-actions">
        <h3 class="quick-actions-title">Acciones Rápidas</h3>
        <div class="quick-actions-grid">
            <button class="quick-action-btn" data-action="create-ftp">
                <i class="fas fa-server"></i>
                <span>Crear Cuenta FTP</span>
            </button>
            <button class="quick-action-btn" data-action="install-ssl">
                <i class="fas fa-lock"></i>
                <span>Instalar SSL</span>
            </button>
            <button class="quick-action-btn" data-action="create-email">
                <i class="fas fa-envelope"></i>
                <span>Crear Email</span>
            </button>
            <button class="quick-action-btn" data-action="backup">
                <i class="fas fa-shield-alt"></i>
                <span>Crear Backup</span>
            </button>
        </div>
    </div>

    <div class="recent-activity">
        <h3 class="activity-title">Actividad Reciente</h3>
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-icon success"><i class="fas fa-check"></i></div>
                <div class="activity-content">
                    <p class="activity-text">Certificado SSL instalado para ejemplo.com</p>
                    <span class="activity-time">Hace 2 horas</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon info"><i class="fas fa-info"></i></div>
                <div class="activity-content">
                    <p class="activity-text">Nueva cuenta FTP creada: usuario_ftp</p>
                    <span class="activity-time">Hace 4 horas</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="activity-content">
                    <p class="activity-text">Backup programado completado</p>
                    <span class="activity-time">Hace 1 día</span>
                </div>
            </div>
        </div>
    </div>
</section>
