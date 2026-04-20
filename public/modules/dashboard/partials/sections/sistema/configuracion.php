<section id="configuracion" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Configuración</h2>
            <p class="section-subtitle">Gestiona tu cuenta y preferencias</p>
        </div>
    </div>

    <div class="config-grid">

        <!-- ── Datos de la cuenta ─────────────────────────────────────── -->
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-edit"></i> Datos de la cuenta</h3>
            </div>
            <form id="config-perfil-form" class="config-form" novalidate>
                <div class="form-group">
                    <label class="form-label">Nombre de usuario <span class="form-required">*</span></label>
                    <input type="text" id="cfg-nombre" class="form-input"
                           placeholder="Nombre único de acceso" maxlength="100" autocomplete="off">
                    <span class="form-error" id="cfg-err-nombre"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" id="cfg-email" class="form-input"
                           placeholder="correo@ejemplo.com" maxlength="255" autocomplete="off">
                    <span class="form-error" id="cfg-err-email"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="cfg-perfil-btn">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Cambiar contraseña ─────────────────────────────────────── -->
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lock"></i> Cambiar contraseña</h3>
            </div>
            <form id="config-pass-form" class="config-form" novalidate>
                <div class="form-group">
                    <label class="form-label">Contraseña actual <span class="form-required">*</span></label>
                    <div class="config-pass-wrap">
                        <input type="password" id="cfg-pass-actual" class="form-input"
                               placeholder="Introduce tu contraseña actual" maxlength="72">
                        <button type="button" class="config-pass-toggle" data-target="cfg-pass-actual" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="form-error" id="cfg-err-actual"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Nueva contraseña <span class="form-required">*</span></label>
                    <div class="config-pass-wrap">
                        <input type="password" id="cfg-pass-nueva" class="form-input"
                               placeholder="Mínimo 8 caracteres con letras y números" maxlength="72">
                        <button type="button" class="config-pass-toggle" data-target="cfg-pass-nueva" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="config-pass-strength" id="cfg-pass-strength" style="display:none">
                        <div class="config-pass-bar"><div id="cfg-pass-bar-fill"></div></div>
                        <span id="cfg-pass-strength-label"></span>
                    </div>
                    <span class="form-error" id="cfg-err-nueva"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar contraseña <span class="form-required">*</span></label>
                    <div class="config-pass-wrap">
                        <input type="password" id="cfg-pass-confirmar" class="form-input"
                               placeholder="Repite la nueva contraseña" maxlength="72">
                        <button type="button" class="config-pass-toggle" data-target="cfg-pass-confirmar" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span class="form-error" id="cfg-err-confirmar"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary" id="cfg-pass-btn">
                        <i class="fas fa-key"></i> Cambiar contraseña
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Apariencia ─────────────────────────────────────────────── -->
        <div class="content-card config-card-full">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-palette"></i> Apariencia</h3>
            </div>
            <div class="config-apariencia">
                <div class="config-tema-opcion" data-tema="light" id="cfg-tema-light">
                    <div class="config-tema-preview config-tema-preview--light">
                        <div class="ctp-header"></div>
                        <div class="ctp-sidebar"></div>
                        <div class="ctp-content"><div></div><div></div><div></div></div>
                    </div>
                    <span>Tema claro</span>
                </div>
                <div class="config-tema-opcion" data-tema="dark" id="cfg-tema-dark">
                    <div class="config-tema-preview config-tema-preview--dark">
                        <div class="ctp-header"></div>
                        <div class="ctp-sidebar"></div>
                        <div class="ctp-content"><div></div><div></div><div></div></div>
                    </div>
                    <span>Tema oscuro</span>
                </div>
            </div>
        </div>

    </div>
</section>
