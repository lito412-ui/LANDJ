<section id="configuracion" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Configuración</h2>
            <p class="section-subtitle">Gestiona tu cuenta, seguridad y preferencias</p>
        </div>
    </div>

    <div class="config-layout">

        <!-- ── Aside: identidad ───────────────────────────────────────── -->
        <aside class="config-aside">
            <div class="content-card config-identity-card">
                <div class="config-identity">
                    <div class="config-resumen-avatar" id="cfg-avatar">??</div>
                    <div class="config-identity-nombre" id="cfg-resumen-nombre">—</div>
                    <div class="config-identity-email" id="cfg-resumen-email">—</div>
                    <span class="config-resumen-badge" id="cfg-resumen-badge"></span>
                </div>
                <div class="config-identity-meta">
                    <div class="config-identity-campo">
                        <span class="config-identity-label"><i class="fas fa-shield-alt"></i> Rol</span>
                        <span class="config-identity-valor" id="cfg-resumen-rol">—</span>
                    </div>
                    <div class="config-identity-campo">
                        <span class="config-identity-label"><i class="fas fa-calendar-alt"></i> Miembro desde</span>
                        <span class="config-identity-valor" id="cfg-resumen-fecha">—</span>
                    </div>
                    <div class="config-identity-campo">
                        <span class="config-identity-label"><i class="fas fa-envelope"></i> Correo</span>
                        <span class="config-identity-valor" id="cfg-resumen-correo">—</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ── Main: ajustes ──────────────────────────────────────────── -->
        <div class="config-main">

            <!-- Datos de la cuenta + Cambiar contraseña -->
            <div class="config-forms-row">

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

            </div><!-- /.config-forms-row -->

            <!-- Apariencia + Seguridad -->
            <div class="config-forms-row">

            <!-- Apariencia -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-palette"></i> Apariencia</h3>
                    <span class="card-subtitle">Selecciona el tema visual del panel</span>
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

            <!-- Seguridad -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shield-alt"></i> Seguridad</h3>
                    <span class="card-subtitle">Gestiona el acceso y la sesión activa</span>
                </div>
                <div class="config-seguridad">
                    <div class="config-seg-item">
                        <div class="config-seg-icono">
                            <i class="fas fa-desktop"></i>
                        </div>
                        <div class="config-seg-texto">
                            <div class="config-seg-titulo">Sesión activa</div>
                            <div class="config-seg-desc">
                                Tienes una sesión activa en este dispositivo. Al cerrar sesión
                                deberás volver a identificarte con tus credenciales.
                            </div>
                        </div>
                        <a href="/auth/logout.php" class="btn-secondary config-seg-btn">
                            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                        </a>
                    </div>
                    <div class="config-seg-item">
                        <div class="config-seg-icono config-seg-icono--warn">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="config-seg-texto">
                            <div class="config-seg-titulo">Contraseña segura</div>
                            <div class="config-seg-desc">
                                Usa una contraseña de al menos 8 caracteres combinando letras,
                                números y símbolos. Cámbiala periódicamente para mantener la seguridad.
                            </div>
                        </div>
                        <button type="button" class="btn-secondary config-seg-btn" id="cfg-ir-password-btn">
                            <i class="fas fa-lock"></i> Cambiar contraseña
                        </button>
                    </div>
                </div>
            </div>

            </div><!-- /.config-forms-row apariencia+seguridad -->

        </div><!-- /.config-main -->

    </div><!-- /.config-layout -->
</section>
