<section id="statistics" class="content-section">
    <div class="section-header">
        <h2 class="section-title">Estadísticas</h2>
        <p class="section-subtitle">Análisis del rendimiento del CRM</p>
    </div>

    <!-- KPI cards -->
    <div class="stats-crm-grid">
        <div class="stat-crm-card">
            <div class="stat-crm-icon" style="background:#e0e7ff;color:#3730a3">
                <i class="fas fa-address-book"></i>
            </div>
            <div class="stat-crm-content">
                <span class="stat-crm-value" id="stat-crm-contactos">—</span>
                <span class="stat-crm-label">Contactos</span>
            </div>
        </div>
        <div class="stat-crm-card">
            <div class="stat-crm-icon" style="background:#dbeafe;color:#1d4ed8">
                <i class="fas fa-funnel-dollar"></i>
            </div>
            <div class="stat-crm-content">
                <span class="stat-crm-value" id="stat-crm-leads">—</span>
                <span class="stat-crm-label">Leads</span>
            </div>
        </div>
        <div class="stat-crm-card">
            <div class="stat-crm-icon" style="background:#ede9fe;color:#7c3aed">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="stat-crm-content">
                <span class="stat-crm-value" id="stat-crm-oportunidades">—</span>
                <span class="stat-crm-label">Oportunidades activas</span>
            </div>
        </div>
        <div class="stat-crm-card">
            <div class="stat-crm-icon" style="background:#dcfce7;color:#15803d">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-crm-content">
                <span class="stat-crm-value" id="stat-crm-pipeline">—</span>
                <span class="stat-crm-label">Valor pipeline</span>
            </div>
        </div>
        <div class="stat-crm-card">
            <div class="stat-crm-icon" style="background:#fef9c3;color:#92400e">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-crm-content">
                <span class="stat-crm-value" id="stat-crm-conversion">—</span>
                <span class="stat-crm-label">Tasa de conversión</span>
                <div class="stat-progress" style="margin-top:6px">
                    <div class="stat-progress-bar" id="stat-conversion-barra" style="width:0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas (se renderizan desde JS) -->
    <div id="stats-charts">
        <div class="crm-loading">
            <i class="fas fa-spinner fa-spin"></i> Cargando estadísticas...
        </div>
    </div>
</section>
