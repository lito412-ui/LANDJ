<header class="header">
    <div class="header-container">
        <div class="header-left">
            <h1 class="logo">L&J</h1>
            <span class="welcome-text">Bienvenido, <span id="user-name">Usuario</span></span>
        </div>
        <div class="header-right">
            <div class="user-menu">
                <button class="user-menu-btn" id="user-menu-btn">
                    <i class="fas fa-user-circle"></i>
                    <span id="header-username">Usuario</span>
                    <span id="header-rol-badge" class="rol-badge" style="display:none"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown" id="user-dropdown">
                    <a href="#perfil" class="dropdown-item nav-link" data-section="perfil">
                        <i class="fas fa-user"></i>
                        Mi Perfil
                    </a>
                    <a href="#" class="dropdown-item">
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
