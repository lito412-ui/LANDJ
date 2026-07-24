<section id="databases" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Bases de Datos</h2>
            <p class="section-subtitle">Estado y estadísticas de las tablas MySQL</p>
        </div>
        <a href="http://localhost:8082" target="_blank" rel="noopener" class="btn-secondary db-pma-link">
            <i class="fas fa-external-link-alt"></i> phpMyAdmin
        </a>
    </div>

    <!-- Tarjetas resumen -->
    <div class="db-summary" id="db-summary" style="display:none">
        <div class="db-summary-card">
            <i class="fas fa-database"></i>
            <div>
                <span class="db-summary-label">Base de datos</span>
                <strong id="db-nombre">—</strong>
            </div>
        </div>
        <div class="db-summary-card">
            <i class="fas fa-table"></i>
            <div>
                <span class="db-summary-label">Tablas</span>
                <strong id="db-total-tablas">—</strong>
            </div>
        </div>
        <div class="db-summary-card">
            <i class="fas fa-list-ol"></i>
            <div>
                <span class="db-summary-label">Filas totales</span>
                <strong id="db-total-filas">—</strong>
            </div>
        </div>
        <div class="db-summary-card">
            <i class="fas fa-hdd"></i>
            <div>
                <span class="db-summary-label">Tamaño total</span>
                <strong id="db-total-size">—</strong>
            </div>
        </div>
    </div>

    <!-- Tabla de tablas -->
    <div class="content-card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tabla</th>
                        <th>Motor</th>
                        <th>Filas</th>
                        <th>Tamaño</th>
                        <th>Colación</th>
                        <th>Última modificación</th>
                    </tr>
                </thead>
                <tbody id="db-tbody">
                    <tr>
                        <td colspan="6" class="crm-loading">
                            <i class="fas fa-spinner fa-spin"></i> Cargando...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
