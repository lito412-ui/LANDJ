document.addEventListener('DOMContentLoaded', () => {
    // Cargar el nombre de usuario desde localStorage
    loadUserData();
    
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

// Función para cargar datos del usuario
function loadUserData() {
    const username = localStorage.getItem('pcwp_username') || 'Usuario';
    
    // Actualizar elementos que muestran el nombre de usuario
    const usernameDisplays = document.querySelectorAll('#username-display, #header-username');
    usernameDisplays.forEach(element => {
        element.textContent = username;
    });
}

// Navegación del sidebar
function initSidebarNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const contentSections = document.querySelectorAll('.content-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            const targetSection = link.getAttribute('data-section');
            
            // Remover clase activa de todos los elementos
            navLinks.forEach(l => l.parentElement.classList.remove('active'));
            contentSections.forEach(s => s.classList.remove('active'));
            
            // Agregar clase activa al elemento seleccionado
            link.parentElement.classList.add('active');
            const targetElement = document.getElementById(targetSection);
            if (targetElement) {
                targetElement.classList.add('active');
            }
        });
    });
}

// Menú de usuario
function initUserMenu() {
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');
    
    userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('show');
        userMenuBtn.classList.toggle('active');
    });
    
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', () => {
        userDropdown.classList.remove('show');
        userMenuBtn.classList.remove('active');
    });
    
    // Prevenir que el menú se cierre al hacer clic dentro
    userDropdown.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}

// Inicializar formularios
function initForms() {
    initFTPForm();
    initSSLForm();
}

// Formulario FTP
function initFTPForm() {
    const createFTPBtn = document.getElementById('create-ftp-btn');
    const ftpFormCard = document.getElementById('ftp-form-card');
    const cancelFTPBtn = document.getElementById('cancel-ftp-btn');
    const ftpForm = document.getElementById('ftp-form');
    
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
        
        const formData = new FormData(ftpForm);
        const ftpData = {
            username: formData.get('ftp-username') || document.getElementById('ftp-username').value,
            password: formData.get('ftp-password') || document.getElementById('ftp-password').value,
            directory: formData.get('ftp-directory') || document.getElementById('ftp-directory').value,
            quota: formData.get('ftp-quota') || document.getElementById('ftp-quota').value
        };
        
        // Simular creación de cuenta FTP
        createFTPAccount(ftpData);
        
        // Ocultar formulario y resetear
        ftpFormCard.style.display = 'none';
        ftpForm.reset();
        
        // Mostrar mensaje de éxito
        showNotification('Cuenta FTP creada exitosamente', 'success');
    });
}

// Formulario SSL
function initSSLForm() {
    const installSSLBtn = document.getElementById('install-ssl-btn');
    const sslFormCard = document.getElementById('ssl-form-card');
    const cancelSSLBtn = document.getElementById('cancel-ssl-btn');
    const sslForm = document.getElementById('ssl-form');
    const sslTypeRadios = document.querySelectorAll('input[name="ssl-type"]');
    const customSSLFields = document.getElementById('custom-ssl-fields');
    const customSSLKey = document.getElementById('custom-ssl-key');
    
    installSSLBtn.addEventListener('click', () => {
        sslFormCard.style.display = 'block';
        sslFormCard.scrollIntoView({ behavior: 'smooth' });
    });
    
    cancelSSLBtn.addEventListener('click', () => {
        sslFormCard.style.display = 'none';
        sslForm.reset();
        customSSLFields.style.display = 'none';
        customSSLKey.style.display = 'none';
    });
    
    // Mostrar/ocultar campos personalizados según el tipo de SSL
    sslTypeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'custom') {
                customSSLFields.style.display = 'block';
                customSSLKey.style.display = 'block';
            } else {
                customSSLFields.style.display = 'none';
                customSSLKey.style.display = 'none';
            }
        });
    });
    
    sslForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const formData = new FormData(sslForm);
        const sslData = {
            domain: formData.get('ssl-domain') || document.getElementById('ssl-domain').value,
            type: formData.get('ssl-type'),
            certificate: document.getElementById('ssl-certificate').value,
            privateKey: document.getElementById('ssl-private-key').value
        };
        
        // Simular instalación de SSL
        installSSLCertificate(sslData);
        
        // Ocultar formulario y resetear
        sslFormCard.style.display = 'none';
        sslForm.reset();
        customSSLFields.style.display = 'none';
        customSSLKey.style.display = 'none';
        
        // Mostrar mensaje de éxito
        showNotification('Certificado SSL instalado exitosamente', 'success');
    });
}

// Acciones rápidas
function initQuickActions() {
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');
    
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.getAttribute('data-action');
            handleQuickAction(action);
        });
    });
}

function handleQuickAction(action) {
    switch (action) {
        case 'create-ftp':
            // Cambiar a la sección FTP y mostrar formulario
            document.querySelector('[data-section="ftp"]').click();
            setTimeout(() => {
                document.getElementById('create-ftp-btn').click();
            }, 300);
            break;
        case 'install-ssl':
            // Cambiar a la sección SSL y mostrar formulario
            document.querySelector('[data-section="ssl"]').click();
            setTimeout(() => {
                document.getElementById('install-ssl-btn').click();
            }, 300);
            break;
        case 'create-email':
            // Cambiar a la sección de email
            document.querySelector('[data-section="email"]').click();
            showNotification('Función de correo en desarrollo', 'info');
            break;
        case 'backup':
            // Cambiar a la sección de backups
            document.querySelector('[data-section="backups"]').click();
            showNotification('Función de backup en desarrollo', 'info');
            break;
    }
}

// Simular creación de cuenta FTP
function createFTPAccount(ftpData) {
    // En una implementación real, esto haría una petición al servidor
    console.log('Creando cuenta FTP:', ftpData);
    
    // Agregar nueva fila a la tabla FTP
    const ftpTable = document.querySelector('#ftp .data-table tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <div class="user-info">
                <i class="fas fa-user"></i>
                <span>${ftpData.username}</span>
            </div>
        </td>
        <td>${ftpData.directory}</td>
        <td>${ftpData.quota} MB</td>
        <td><span class="status-badge active">Activo</span></td>
        <td>
            <div class="action-buttons">
                <button class="btn-icon" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon danger" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </td>
    `;
    ftpTable.appendChild(newRow);
    
    // Agregar actividad reciente
    addRecentActivity(`Nueva cuenta FTP creada: ${ftpData.username}`, 'info');
}

// Simular instalación de certificado SSL
function installSSLCertificate(sslData) {
    // En una implementación real, esto haría una petición al servidor
    console.log('Instalando certificado SSL:', sslData);
    
    // Agregar nueva tarjeta SSL
    const sslGrid = document.querySelector('.ssl-grid');
    const newSSLCard = document.createElement('div');
    newSSLCard.className = 'ssl-card';
    
    const expiryDate = new Date();
    expiryDate.setMonth(expiryDate.getMonth() + 3); // 3 meses desde ahora
    
    newSSLCard.innerHTML = `
        <div class="ssl-status">
            <i class="fas fa-shield-alt ssl-icon active"></i>
            <span class="ssl-status-text active">Activo</span>
        </div>
        <div class="ssl-info">
            <h4 class="ssl-domain">${sslData.domain}</h4>
            <p class="ssl-issuer">${sslData.type === 'letsencrypt' ? "Let's Encrypt" : 'Certificado Personalizado'}</p>
            <p class="ssl-expiry">Expira: ${expiryDate.toLocaleDateString('es-ES')}</p>
        </div>
        <div class="ssl-actions">
            <button class="btn-icon" title="Renovar">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="btn-icon" title="Descargar">
                <i class="fas fa-download"></i>
            </button>
            <button class="btn-icon danger" title="Revocar">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    sslGrid.appendChild(newSSLCard);
    
    // Agregar actividad reciente
    addRecentActivity(`Certificado SSL instalado para ${sslData.domain}`, 'success');
}

// Agregar actividad reciente
function addRecentActivity(text, type) {
    const activityList = document.querySelector('.activity-list');
    const newActivity = document.createElement('div');
    newActivity.className = 'activity-item';
    
    const iconClass = type === 'success' ? 'fas fa-check' : 
                     type === 'info' ? 'fas fa-info' : 
                     'fas fa-exclamation-triangle';
    
    newActivity.innerHTML = `
        <div class="activity-icon ${type}">
            <i class="${iconClass}"></i>
        </div>
        <div class="activity-content">
            <p class="activity-text">${text}</p>
            <span class="activity-time">Ahora</span>
        </div>
    `;
    
    // Insertar al principio de la lista
    activityList.insertBefore(newActivity, activityList.firstChild);
    
    // Limitar a 5 actividades
    const activities = activityList.querySelectorAll('.activity-item');
    if (activities.length > 5) {
        activityList.removeChild(activities[activities.length - 1]);
    }
}

// Sistema de notificaciones
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 
                           type === 'error' ? 'fa-exclamation-circle' : 
                           'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Agregar estilos si no existen
    if (!document.querySelector('#notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 80px;
                right: 20px;
                background: white;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 16px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                z-index: 1002;
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 300px;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            }
            
            .notification-success {
                border-left: 4px solid #16a34a;
            }
            
            .notification-error {
                border-left: 4px solid #dc2626;
            }
            
            .notification-info {
                border-left: 4px solid #2563eb;
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 8px;
                flex: 1;
            }
            
            .notification-content i {
                font-size: 1.1rem;
            }
            
            .notification-success .notification-content i {
                color: #16a34a;
            }
            
            .notification-error .notification-content i {
                color: #dc2626;
            }
            
            .notification-info .notification-content i {
                color: #2563eb;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: #64748b;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                transition: all 0.2s ease;
            }
            
            .notification-close:hover {
                background: #f1f5f9;
                color: #1e293b;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Manejar cierre
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    });
    
    // Auto-cerrar después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            closeBtn.click();
        }
    }, 5000);
}

// Actualizaciones en tiempo real (simuladas)
function initRealTimeUpdates() {
    // Actualizar estadísticas cada 30 segundos
    setInterval(updateStats, 30000);
    
    // Actualizar tiempo de actividades cada minuto
    setInterval(updateActivityTimes, 60000);
}

function updateStats() {
    // Simular cambios en las estadísticas
    const statValues = document.querySelectorAll('.stat-value');
    const statBars = document.querySelectorAll('.stat-progress-bar');
    
    statValues.forEach((value, index) => {
        if (value.textContent.includes('%')) {
            const currentValue = parseInt(value.textContent);
            const change = Math.floor(Math.random() * 10) - 5; // -5 a +5
            const newValue = Math.max(0, Math.min(100, currentValue + change));
            
            value.textContent = newValue + '%';
            if (statBars[index]) {
                statBars[index].style.width = newValue + '%';
            }
        }
    });
}

function updateActivityTimes() {
    const activityTimes = document.querySelectorAll('.activity-time');
    activityTimes.forEach(time => {
        if (time.textContent === 'Ahora') {
            time.textContent = 'Hace 1 minuto';
        } else if (time.textContent.includes('minuto')) {
            const minutes = parseInt(time.textContent.match(/\d+/)[0]) + 1;
            if (minutes < 60) {
                time.textContent = `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
            } else {
                const hours = Math.floor(minutes / 60);
                time.textContent = `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
            }
        }
    });
}

// Función para cerrar sesión
function logout() {
    localStorage.removeItem('pcwp_username');
    window.location.href = 'index.html';
}

// Agregar event listener para el enlace de cerrar sesión
document.addEventListener('click', (e) => {
    if (e.target.closest('.logout')) {
        e.preventDefault();
        logout();
    }
});

// Verificar si el usuario está autenticado
function checkAuth() {
    const username = localStorage.getItem('pcwp_username');
    if (!username) {
        window.location.href = 'login.html';
    }
}

// Verificar autenticación al cargar la página
checkAuth();

