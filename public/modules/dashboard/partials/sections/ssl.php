<section id="ssl" class="content-section">
    <div class="section-header">
        <h2 class="section-title">Certificados SSL/TLS</h2>
        <p class="section-subtitle">Gestiona los certificados SSL para conexiones seguras</p>
        <button class="btn-primary" id="install-ssl-btn">
            <i class="fas fa-plus"></i>
            Instalar Certificado SSL
        </button>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Certificados SSL Instalados</h3>
        </div>
        <div class="ssl-grid">
            <div class="ssl-card">
                <div class="ssl-status">
                    <i class="fas fa-shield-alt ssl-icon active"></i>
                    <span class="ssl-status-text active">Activo</span>
                </div>
                <div class="ssl-info">
                    <h4 class="ssl-domain">ejemplo.com</h4>
                    <p class="ssl-issuer">Let's Encrypt</p>
                    <p class="ssl-expiry">Expira: 15/03/2025</p>
                </div>
                <div class="ssl-actions">
                    <button class="btn-icon" title="Renovar"><i class="fas fa-sync-alt"></i></button>
                    <button class="btn-icon" title="Descargar"><i class="fas fa-download"></i></button>
                    <button class="btn-icon danger" title="Revocar"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <div class="ssl-card">
                <div class="ssl-status">
                    <i class="fas fa-shield-alt ssl-icon warning"></i>
                    <span class="ssl-status-text warning">Por Expirar</span>
                </div>
                <div class="ssl-info">
                    <h4 class="ssl-domain">www.ejemplo.com</h4>
                    <p class="ssl-issuer">Let's Encrypt</p>
                    <p class="ssl-expiry">Expira: 28/10/2024</p>
                </div>
                <div class="ssl-actions">
                    <button class="btn-icon" title="Renovar"><i class="fas fa-sync-alt"></i></button>
                    <button class="btn-icon" title="Descargar"><i class="fas fa-download"></i></button>
                    <button class="btn-icon danger" title="Revocar"><i class="fas fa-times"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card" id="ssl-form-card" style="display: none;">
        <div class="card-header">
            <h3 class="card-title">Instalar Certificado SSL</h3>
        </div>
        <form class="form-grid" id="ssl-form">
            <div class="form-group full-width">
                <label for="ssl-domain" class="form-label">Dominio</label>
                <input type="text" id="ssl-domain" class="form-input" placeholder="ejemplo.com" required>
            </div>
            <div class="form-group full-width">
                <label class="form-label">Tipo de Certificado</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" name="ssl-type" value="letsencrypt" checked>
                        <span class="radio-custom"></span>
                        <span class="radio-label">Let's Encrypt (Gratuito)</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" name="ssl-type" value="custom">
                        <span class="radio-custom"></span>
                        <span class="radio-label">Certificado Personalizado</span>
                    </label>
                </div>
            </div>
            <div class="form-group full-width" id="custom-ssl-fields" style="display: none;">
                <label for="ssl-certificate" class="form-label">Certificado (CRT)</label>
                <textarea id="ssl-certificate" class="form-textarea" placeholder="-----BEGIN CERTIFICATE-----" rows="6"></textarea>
            </div>
            <div class="form-group full-width" id="custom-ssl-key" style="display: none;">
                <label for="ssl-private-key" class="form-label">Clave Privada</label>
                <textarea id="ssl-private-key" class="form-textarea" placeholder="-----BEGIN PRIVATE KEY-----" rows="6"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-lock"></i>
                    Instalar Certificado
                </button>
                <button type="button" class="btn-secondary" id="cancel-ssl-btn">Cancelar</button>
            </div>
        </form>
    </div>
</section>
