document.addEventListener('DOMContentLoaded', () => {
    // 1. Verificar si el usuario está logueado en el servidor
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

// Función para verificar la autenticación real (PHP)
function checkAuth() {
    fetch('get_user.php')
        .then(response => response.json())
        .then(data => {
            if (data.logged) {
                // Si está logueado, cargamos su nombre en el HTML
                loadUserData(data.nombre);
            } else {
                // Si no hay sesión activa, lo echamos al login
                window.location.href = 'index.html';
            }
        })
        .catch(error => {
            console.error('Error de autenticación:', error);
            window.location.href = 'index.html';
        });
}

// Función para cargar el nombre del usuario en los elementos correspondientes
function loadUserData(nombre) {
    // Actualizar elementos que muestran el nombre de usuario
    const usernameDisplays = document.querySelectorAll('#username-display, #header-username');
    usernameDisplays.forEach(element => {
        element.textContent = nombre;
    });
}

// Función para cerrar sesión
function logout() {
    window.location.href = 'logout.php';
}

// Agregar event listener para el enlace de cerrar sesión
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
            if (targetElement) targetElement.classList.add('active');
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
    
    if (createFTPBtn && ftpFormCard) {
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
            showNotification('Cuenta FTP creada exitosamente (Simulación)', 'success');
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
    
    if (installSSLBtn && sslFormCard) {
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
            showNotification(`Acción ejecutada: ${action}`, 'info');
        });
    });
}

function showNotification(message, type = 'info') {
    console.log(`[Notificación ${type}]: ${message}`);
}

function initRealTimeUpdates() {
    setInterval(() => {
        console.log('Actualizando datos en segundo plano...');
    }, 30000);
}