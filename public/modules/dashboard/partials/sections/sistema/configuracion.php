<section id="configuracion" class="content-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Configuración</h2>
            <p class="section-subtitle">Gestiona tu cuenta, seguridad y preferencias</p>
        </div>
    </div>

    <div class="config-vertical">

        <!-- Identidad -->
        <div class="content-card config-identity-card-h">
            <div class="config-identity-h">
                <div class="config-resumen-avatar" id="cfg-avatar">??</div>
                <div class="config-identity-h-info">
                    <div class="config-identity-nombre" id="cfg-resumen-nombre">—</div>
                    <div class="config-identity-email" id="cfg-resumen-email">—</div>
                    <span class="config-resumen-badge" id="cfg-resumen-badge"></span>
                </div>
                <div class="config-identity-h-meta">
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
        </div>

        <!-- Datos de la cuenta -->
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

        <!-- Cambiar contraseña -->
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
                <div class="config-seg-item">
                    <div class="config-seg-icono config-seg-icono--green">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="config-seg-texto">
                        <div class="config-seg-titulo">Verificación en dos pasos</div>
                        <div class="config-seg-desc" id="cfg-2fa-desc">
                            Protege tu cuenta con un código enviado a tu correo al iniciar sesión.
                        </div>
                    </div>
                    <label class="toggle-switch" id="cfg-2fa-toggle-wrap" title="">
                        <input type="checkbox" id="cfg-2fa-toggle">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Avisos -->
        <div class="content-card" id="cfg-notif-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell"></i> Avisos</h3>
                <span class="card-subtitle">Cómo recibir las notificaciones de tus actividades</span>
            </div>
            <div class="config-notif">

                <div class="config-notif-row">
                    <div class="config-notif-icono">
                        <i class="fas fa-power-off"></i>
                    </div>
                    <div class="config-notif-info">
                        <div class="config-notif-titulo">Activar notificaciones</div>
                        <div class="config-notif-desc">Recibe avisos cuando tus recordatorios programados se cumplan.</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="cfg-notif-activas">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="config-notif-row">
                    <div class="config-notif-icono">
                        <i class="fas fa-volume-up"></i>
                    </div>
                    <div class="config-notif-info">
                        <div class="config-notif-titulo">Sonido al recibir</div>
                        <div class="config-notif-desc">Reproduce un pitido suave cuando llegue una notificación nueva.</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="cfg-notif-sonido">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="config-notif-row">
                    <div class="config-notif-icono">
                        <i class="fas fa-desktop"></i>
                    </div>
                    <div class="config-notif-info">
                        <div class="config-notif-titulo">Notificaciones del navegador</div>
                        <div class="config-notif-desc">Muestra avisos del sistema operativo aunque la pestaña esté en segundo plano. <span id="cfg-notif-browser-estado" class="config-notif-estado"></span></div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="cfg-notif-browser">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

            </div>
        </div>

        <!-- Recordatorios -->
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock"></i> Recordatorios</h3>
                <span class="card-subtitle">Cuándo y con qué frecuencia comprobar tus recordatorios</span>
            </div>
            <div class="config-notif">

                <div class="config-notif-row">
                    <div class="config-notif-icono">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="config-notif-info">
                        <div class="config-notif-titulo">Antelación por defecto</div>
                        <div class="config-notif-desc">Minutos antes del recordatorio en los que empezar a avisarte por defecto al crear una tarea.</div>
                    </div>
                    <select id="cfg-notif-antelacion" class="form-input form-input-sm config-notif-select">
                        <option value="5">5 minutos</option>
                        <option value="15">15 minutos</option>
                        <option value="30">30 minutos</option>
                        <option value="60">1 hora</option>
                        <option value="120">2 horas</option>
                        <option value="1440">1 día</option>
                    </select>
                </div>

                <div class="config-notif-row">
                    <div class="config-notif-icono config-notif-icono--purple">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="config-notif-info">
                        <div class="config-notif-titulo">Frecuencia de comprobación</div>
                        <div class="config-notif-desc">Cada cuánto el panel comprueba si hay nuevos recordatorios.</div>
                    </div>
                    <select id="cfg-notif-frecuencia" class="form-input form-input-sm config-notif-select">
                        <option value="30">Cada 30 segundos</option>
                        <option value="60">Cada minuto</option>
                        <option value="120">Cada 2 minutos</option>
                        <option value="300">Cada 5 minutos</option>
                    </select>
                </div>

                <div class="config-notif-actions">
                    <button type="button" class="btn-secondary" id="cfg-notif-test">
                        <i class="fas fa-vial"></i> Probar
                    </button>
                    <button type="button" class="btn-primary" id="cfg-notif-guardar">
                        <i class="fas fa-save"></i> Guardar preferencias
                    </button>
                </div>

            </div>
        </div>

        <!-- SMTP del Sistema — solo visible para administradores -->
        <?php if (($_SESSION['rol'] ?? '') === 'administrador'): ?>
        <div class="content-card" id="cfg-smtp-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-at"></i> Servidor de Correo de la Empresa (SMTP Comercial)</h3>
                <span class="card-subtitle">Configura el servidor SMTP desde el que se enviarán presupuestos y facturas a tus clientes</span>
            </div>
            <div class="card-body">
                <p class="config-section-desc">
                    Las credenciales que configures aquí se guardan en la base de datos de esta instalación y se utilizarán para todos los envíos comerciales (facturas, presupuestos y avisos a clientes).<br>
                    <small style="color: #64748b; display: block; margin-top: 6px;">
                        <i class="fas fa-shield-alt"></i> <strong>Seguridad:</strong> Los correos internos del sistema (códigos 2FA al iniciar sesión) se gestionan de forma centralizada y segura a través del archivo <code>.env</code> del servidor.
                    </small>
                </p>
                <form id="cfg-smtp-form" class="config-form" novalidate>
                    <div class="form-group">
                        <label class="form-label">Servidor SMTP (Host) <span class="form-required">*</span></label>
                        <input type="text" id="cfg-smtp-host" class="form-input"
                               placeholder="smtp.gmail.com" autocomplete="off">
                        <span class="form-hint">Ejemplos: smtp.gmail.com, smtp.office365.com, mail.tudominio.com</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Puerto <span class="form-required">*</span></label>
                            <input type="number" id="cfg-smtp-port" class="form-input"
                                   placeholder="587" min="1" max="65535">
                            <span class="form-hint">TLS: 587 · SSL: 465 · Sin cifrado: 25</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cifrado</label>
                            <select id="cfg-smtp-encryption" class="form-input crm-select">
                                <option value="tls">STARTTLS (Recomendado)</option>
                                <option value="ssl">SSL / SMTPS</option>
                                <option value="none">Sin cifrado</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Usuario / Email SMTP <span class="form-required">*</span></label>
                        <input type="text" id="cfg-smtp-user" class="form-input"
                               placeholder="correo@ejemplo.com" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña SMTP</label>
                        <div class="config-pass-wrap">
                            <input type="password" id="cfg-smtp-pass" class="form-input"
                                   placeholder="Déjalo vacío para mantener la contraseña actual" autocomplete="new-password">
                            <button type="button" class="config-pass-toggle" data-target="cfg-smtp-pass" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span class="form-hint">Para Gmail utiliza una «Contraseña de Aplicación» de 16 caracteres (sin espacios).</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dirección del Remitente ("De")</label>
                        <input type="email" id="cfg-smtp-from" class="form-input"
                               placeholder="noreply@tudominio.com" autocomplete="off">
                        <span class="form-hint">Email que verán los destinatarios. Si se deja vacío se usará el usuario SMTP.</span>
                    </div>
                    <div class="form-actions cfg-smtp-actions">
                        <button type="submit" class="btn-primary" id="cfg-smtp-guardar">
                            <i class="fas fa-save"></i> Guardar configuración SMTP
                        </button>
                        <button type="button" class="btn-secondary" id="cfg-smtp-test">
                            <i class="fas fa-paper-plane"></i> Enviar email de prueba
                        </button>
                    </div>
                    <div id="cfg-smtp-msg" style="display:none;" class="form-alert"></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.config-vertical -->
</section>
