<header class="header">
    <div class="header-container">
        <div class="header-left">
            <button class="mobile-menu-btn" id="mobile-menu-btn" type="button" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <a class="logo" href="#dashboard" aria-label="L&J cPanel, ir al panel principal">
                <img src="/assets/img/logo_proyecto.png" alt="L&J cPanel">
            </a>
            <span class="welcome-text">Bienvenido, <span id="user-name">Usuario</span></span>
        </div>
        <div class="header-right">
            <div class="notif-menu">
                <button class="notif-btn" id="notif-btn" title="Notificaciones" aria-haspopup="true">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge" id="notif-badge" style="display:none">0</span>
                </button>
                <div class="notif-dropdown" id="notif-dropdown">
                    <div class="notif-header">
                        <span class="notif-titulo"><i class="fas fa-bell"></i> Notificaciones</span>
                        <button class="notif-config-btn" id="notif-config-btn" title="Configurar">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                    <ul class="notif-lista" id="notif-lista">
                        <li class="notif-vacio">
                            <i class="fas fa-bell-slash"></i>
                            <span>Sin notificaciones</span>
                        </li>
                    </ul>
                    <div class="notif-footer">
                        <button class="notif-marcar-todas" id="notif-marcar-todas">
                            <i class="fas fa-check-double"></i> Marcar todas como leídas
                        </button>
                    </div>
                </div>
            </div>
            <button class="theme-toggle-btn" id="theme-toggle-btn" title="Cambiar tema">
                <i class="fas fa-moon"></i>
            </button>
            <div class="user-menu">
                <button class="user-menu-btn" id="user-menu-btn">
                    <i class="fas fa-user-circle"></i>
                    <span id="header-username">Usuario</span>
                    <span id="header-rol-badge" class="rol-badge" style="display:none"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown" id="user-dropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-avatar" id="dropdown-avatar">?</div>
                        <div class="dropdown-user-info">
                            <span class="dropdown-nombre" id="dropdown-nombre">Usuario</span>
                            <span class="dropdown-email" id="dropdown-email"></span>
                            <span class="dropdown-rol-badge" id="dropdown-rol-badge"></span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="#perfil" class="dropdown-item nav-link" data-section="perfil">
                        <i class="fas fa-user"></i>
                        Mi Perfil
                    </a>
                    <a href="#configuracion" class="dropdown-item nav-link" data-section="configuracion">
                        <i class="fas fa-cog"></i>
                        Configuración
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="/auth/logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
