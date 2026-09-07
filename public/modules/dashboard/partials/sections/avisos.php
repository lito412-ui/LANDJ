<section id="avisos" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Avisos</h2>
            <p class="section-subtitle">Mensajes del equipo. Los administradores pueden enviar avisos a los demás usuarios.</p>
        </div>
        <div class="avisos-header-actions" data-admin-only>
            <button class="btn-secondary" id="avisos-ver-enviados-btn">
                <i class="fas fa-paper-plane"></i> Enviados
            </button>
            <button class="btn-primary" id="avisos-nuevo-btn">
                <i class="fas fa-plus"></i> Nuevo Aviso
            </button>
        </div>
    </div>

    <!-- Panel: nuevo aviso (admin) -->
    <div class="form-panel" id="avisos-form-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Nuevo Aviso</h3>
            </div>
            <form id="avisos-form" class="form-grid" novalidate>
                <div class="form-group full-width">
                    <label class="form-label">Título <span class="form-required">*</span></label>
                    <input type="text" id="av-titulo" class="form-input" placeholder="Ej: Mantenimiento programado el viernes" maxlength="150">
                    <span class="form-error" id="err-av-titulo"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select id="av-tipo" class="form-input">
                        <option value="info">Información</option>
                        <option value="exito">Buena noticia</option>
                        <option value="aviso">Aviso importante</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Destinatarios</label>
                    <select id="av-destinatarios-modo" class="form-input">
                        <option value="todos">Todos los usuarios</option>
                        <option value="especificos">Usuarios específicos...</option>
                    </select>
                </div>
                <div class="form-group full-width" id="av-usuarios-grupo" style="display:none">
                    <label class="form-label">Selecciona los usuarios</label>
                    <div class="avisos-usuarios-lista" id="av-usuarios-lista"></div>
                    <span class="form-error" id="err-av-destinatarios"></span>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Mensaje <span class="form-required">*</span></label>
                    <textarea id="av-mensaje" class="form-textarea" rows="4" maxlength="2000" placeholder="Escribe el mensaje que verán tus usuarios..."></textarea>
                    <span class="form-error" id="err-av-mensaje"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" id="avisos-enviar-btn" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar aviso
                    </button>
                    <button type="button" id="avisos-cancelar-btn" class="btn-secondary">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel: enviados (admin) -->
    <div class="form-panel" id="avisos-enviados-panel">
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Avisos enviados</h3>
            </div>
            <div id="avisos-enviados-lista"></div>
        </div>
    </div>

    <!-- Bandeja -->
    <div class="content-card">
        <div class="card-header">
            <h3 class="card-title">Mi bandeja</h3>
            <button class="btn-link" id="avisos-marcar-todas-btn">Marcar todas como leídas</button>
        </div>
        <div id="avisos-bandeja"></div>
    </div>
</section>
