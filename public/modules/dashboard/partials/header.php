<header class="header">
    <div class="header-container">
        <div class="header-left">
            <h1 class="logo">L&J</h1>
            <span class="welcome-text">Bienvenido, <span id="user-name">Usuario</span></span>
        </div>
        <div class="header-right">
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
                    <span class="dropdown-section-label">
                        <i class="fas fa-cog"></i> Configuración
                    </span>
                    <a href="#configuracion" class="dropdown-item dropdown-sub-item nav-link" data-section="configuracion">
                        <i class="fas fa-id-card"></i>
                        Datos de la cuenta
                    </a>
                    <a href="#configuracion" class="dropdown-item dropdown-sub-item nav-link" data-section="configuracion">
                        <i class="fas fa-lock"></i>
                        Cambiar contraseña
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
