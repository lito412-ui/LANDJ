document.addEventListener('DOMContentLoaded', () => {
    // 1. Verificar autenticación con el servidor antes de mostrar nada
    checkAuth();
    
    // Inicializar navegación del sidebar
    initSidebarNavigation();
    
    // Inicializar menú de usuario
    initUserMenu();
    
    // Inicializar formularios
    initForms();
    
    // Inicializar acciones rápidas
    initQuickActions();
    
    // Simular datos en tiempo real
    initRealTimeUpdates();
});

function checkAuth() {
    // Llamamos al archivo PHP que nos dice si hay una sesión activa
    fetch('/api/get_user.php')
        .then(response => response.json())
        .then(data => {
            if (data.logged) {
                // Si el servidor confirma la sesión, cargamos el nombre en el HTML
                loadUserData(data.nombre);
            } else {
                // Si no hay sesión real en el servidor, redirigimos al login
                window.location.href = '/index.html';
            }
        })
        .catch(error => {
            console.error('Error verificando sesión:', error);
            window.location.href = '/index.html';
        });
}

function loadUserData(nombreReal) {
    // Actualizar elementos que muestran el nombre de usuario con el dato de la DB
    const usernameDisplays = document.querySelectorAll('#username-display, #header-username');
    usernameDisplays.forEach(element => {
        element.textContent = nombreReal;
    });
}

function logout() {
    // Redirigir al script de PHP que destruye la sesión
    window.location.href = '/auth/logout.php';
}

// Escuchar clicks en cualquier elemento que tenga la clase "logout"
document.addEventListener('click', (e) => {
    if (e.target.closest('.logout')) {
        e.preventDefault();
        logout();
    }
});


function initSidebarNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const contentSections = document.querySelectorAll('.content-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetSection = link.getAttribute('data-section');
            navLinks.forEach(l => l.parentElement.classList.remove('active'));
            contentSections.forEach(s => s.classList.remove('active'));
            link.parentElement.classList.add('active');
            const targetElement = document.getElementById(targetSection);
            if (targetElement) {
                targetElement.classList.add('active');
            }
        });
    });
}

function initUserMenu() {
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');
    
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
            userMenuBtn.classList.toggle('active');
        });
        
        document.addEventListener('click', () => {
            userDropdown.classList.remove('show');
            userMenuBtn.classList.remove('active');
        });

        userDropdown.addEventListener('click', (e) => e.stopPropagation());
    }
}

function initForms() {
    initFTPForm();
    initSSLForm();
}

function initFTPForm() {
    const createFTPBtn = document.getElementById('create-ftp-btn');
    const ftpFormCard = document.getElementById('ftp-form-card');
    const cancelFTPBtn = document.getElementById('cancel-ftp-btn');
    const ftpForm = document.getElementById('ftp-form');
    
    if (createFTPBtn) {
        createFTPBtn.addEventListener('click', () => {
            ftpFormCard.style.display = 'block';
            ftpFormCard.scrollIntoView({ behavior: 'smooth' });
        });
        
        cancelFTPBtn.addEventListener('click', () => {
            ftpFormCard.style.display = 'none';
            ftpForm.reset();
        });
        
        ftpForm.addEventListener('submit', (e) => {
            e.preventDefault();
            showNotification('Cuenta FTP creada exitosamente', 'success');
            ftpFormCard.style.display = 'none';
            ftpForm.reset();
        });
    }
}

function initSSLForm() {
    const installSSLBtn = document.getElementById('install-ssl-btn');
    const sslFormCard = document.getElementById('ssl-form-card');
    const cancelSSLBtn = document.getElementById('cancel-ssl-btn');
    const sslForm = document.getElementById('ssl-form');
    
    if (installSSLBtn) {
        installSSLBtn.addEventListener('click', () => {
            sslFormCard.style.display = 'block';
            sslFormCard.scrollIntoView({ behavior: 'smooth' });
        });
        
        cancelSSLBtn.addEventListener('click', () => {
            sslFormCard.style.display = 'none';
            sslForm.reset();
        });
    }
}

function initQuickActions() {
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.getAttribute('data-action');
            showNotification('Acción iniciada: ' + action, 'info');
        });
    });
}

function showNotification(message, type = 'info') {
    console.log(`[${type.toUpperCase()}] ${message}`);
    alert(message); // Temporal para verificar que funciona
}

function initRealTimeUpdates() {
    setInterval(() => {
    }, 30000);
}