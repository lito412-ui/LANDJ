// ─── Fetch con CSRF automático ────────────────────────────────────────────────

const _csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function fetchSeguro(url, opciones = {}) {
    const method = (opciones.method ?? 'GET').toUpperCase();
    const cabeceras = { ...(opciones.headers ?? {}) };
    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
        cabeceras['X-CSRF-Token'] = _csrfToken;
    }
    return fetch(url, { ...opciones, headers: cabeceras });
}

document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    actualizarMetricas();
    setInterval(actualizarMetricas, 3000);
    initSidebarNavigation();
    initUserMenu();
    initThemeToggle();
    initQuickActions();
});

let perfilData = null;

async function checkAuth() {
    try {
        const response = await fetchSeguro('/api/get_user.php?t=' + Date.now());
        if (!response.ok) throw new Error('No se pudo validar la sesion');
        const data = await response.json();
        if (!data.logged) {
            window.location.href = '/modules/site/login.html';
            return;
        }

        perfilData = data;

        const headerUsername = document.getElementById('header-username');
        const userName = document.getElementById('user-name');
        if (headerUsername) headerUsername.textContent = data.nombre;
        if (userName) userName.textContent = data.nombre;

        _poblarDropdownHeader(data);
        aplicarPermisosUI(data.rol);
        cargarActividadReciente();

        if (typeof Configuracion !== 'undefined') Configuracion.cargar(data);
    } catch (error) {
        console.error('Error validando sesion:', error.message);
        window.location.href = '/modules/site/login.html';
    }
}

function _poblarDropdownHeader(data) {
    const avatar = document.getElementById('dropdown-avatar');
    if (avatar) avatar.textContent = data.nombre.slice(0, 2).toUpperCase();

    const nombre = document.getElementById('dropdown-nombre');
    if (nombre) nombre.textContent = data.nombre;

    const email = document.getElementById('dropdown-email');
    if (email) email.textContent = data.email || '';

    const rolBadge = document.getElementById('dropdown-rol-badge');
    if (rolBadge) {
        const esAdmin = data.rol === 'administrador';
        rolBadge.textContent = esAdmin ? 'Admin' : 'Usuario';
        rolBadge.className   = 'dropdown-rol-badge' + (esAdmin ? '' : ' rol-usuario');
    }
}

function mostrarPerfil(data) {
    const iniciales = data.nombre.slice(0, 2).toUpperCase();
    const avatar = document.getElementById('perfil-avatar');
    if (avatar) avatar.textContent = iniciales;

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };

    set('perfil-nombre',     data.nombre);
    set('perfil-rol',        data.rol);
    set('perfil-det-nombre', data.nombre);
    set('perfil-det-email',  data.email || 'Sin correo registrado');
    set('perfil-det-rol',    data.rol);

    const fecha = data.created_at
        ? new Date(data.created_at).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })
        : '—';
    set('perfil-det-fecha', fecha);
}

async function actualizarMetricas() {
    try {
        const r = await fetchSeguro('/api/monitorizacion.php?t=' + Date.now());
        if (!r.ok) return;
        const d = await r.json();
        if (!d.ok) return;

        const m = d.data;
        setMetrica('disco-texto', 'disco-barra', m.disco);

        const ramPct = m.ram_total > 0
            ? Math.min((m.ram_usada / m.ram_total) * 100, 100).toFixed(1)
            : '0.0';
        setMetrica('ram-texto', 'ram-barra', ramPct);

        setMetrica('cpu-texto', 'cpu-barra', m.cpu);

    } catch (e) {
        console.error('[monitorizacion]', e);
    }
}

function setMetrica(idTexto, idBarra, valor) {
    const txt = document.getElementById(idTexto);
    const bar = document.getElementById(idBarra);
    if (txt) txt.innerText = valor + '%';
    if (bar) bar.style.width = Math.min(parseFloat(valor), 100) + '%';
}

function initSidebarNavigation() {
    const links = document.querySelectorAll('.nav-link');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            const sectionId = link.getAttribute('data-section');
            if (!sectionId) return;
            e.preventDefault();
            if (ADMIN_SECTIONS.includes(sectionId) && perfilData?.rol !== 'administrador') return;
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            link.parentElement.classList.add('active');
            document.getElementById(sectionId)?.classList.add('active');
            document.getElementById('user-dropdown')?.classList.remove('show');

            if (sectionId === 'perfil'         && perfilData) mostrarPerfil(perfilData);
            if (sectionId === 'configuracion') Configuracion.init();
            if (sectionId === 'statistics')    Estadisticas.init();
            if (sectionId === 'contactos')     Contactos.init();
            if (sectionId === 'leads')         Leads.init();
            if (sectionId === 'users'        && typeof Usuarios   !== 'undefined') Usuarios.init();
            if (sectionId === 'oportunidades') Oportunidades.init();
            if (sectionId === 'email')        Email.init();
            if (sectionId === 'domains')      Dominios.init();
            if (sectionId === 'logs'         && typeof Auditoria  !== 'undefined') Auditoria.init();
            if (sectionId === 'databases'    && typeof Databases  !== 'undefined') Databases.init();
            if (sectionId === 'backups'      && typeof Backups    !== 'undefined') Backups.init();
        });
    });
}

function initUserMenu() {
    const btn = document.getElementById('user-menu-btn');
    const menu = document.getElementById('user-dropdown');
    if (btn && menu) {
        btn.onclick = (e) => { e.stopPropagation(); menu.classList.toggle('show'); };
        window.onclick = () => menu.classList.remove('show');
    }
}

function initThemeToggle() {
    const btn         = document.getElementById('theme-toggle-btn');
    const btnDropdown = document.getElementById('dropdown-theme-toggle');
    const STORAGE_KEY = 'theme';
    const temaGuardado = localStorage.getItem(STORAGE_KEY) ?? 'light';

    _aplicarTema(temaGuardado, btn, btnDropdown);

    const toggleTema = () => {
        const oscuro = document.documentElement.dataset.theme === 'dark';
        const nuevo  = oscuro ? 'light' : 'dark';
        _aplicarTema(nuevo, btn, btnDropdown);
        localStorage.setItem(STORAGE_KEY, nuevo);
    };

    btn?.addEventListener('click', toggleTema);
    btnDropdown?.addEventListener('click', (e) => { e.stopPropagation(); toggleTema(); });
}

function _aplicarTema(tema, btn, btnDropdown) {
    document.documentElement.dataset.theme = tema;
    const oscuro = tema === 'dark';
    const icoClass = oscuro ? 'fas fa-sun' : 'fas fa-moon';
    const titulo   = oscuro ? 'Tema claro' : 'Tema oscuro';
    if (btn) { btn.querySelector('i').className = icoClass; btn.title = titulo; }
    if (btnDropdown) { btnDropdown.querySelector('i').className = icoClass; btnDropdown.title = titulo; }
}

// ─── Utilidades globales ──────────────────────────────────────────────────────

function mostrarToast(msg, tipo = 'info') {
    let cont = document.getElementById('toast-container');
    if (!cont) {
        cont = document.createElement('div');
        cont.id = 'toast-container';
        cont.className = 'toast-container';
        document.body.appendChild(cont);
    }
    const t = document.createElement('div');
    const ico = tipo === 'success' ? 'fa-check-circle' : tipo === 'error' ? 'fa-times-circle' : 'fa-info-circle';
    t.className = `toast ${tipo}`;
    t.innerHTML = `<i class="fas ${ico}"></i> ${msg}`;
    cont.appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

function mostrarConfirm(titulo, texto, onConfirm, btnLabel = 'Eliminar', variant = 'danger') {
    const iconos   = { danger: 'fa-exclamation-triangle', success: 'fa-user-check', info: 'fa-info-circle' };
    const clases   = { danger: 'confirm-box',             success: 'confirm-box confirm-box--success', info: 'confirm-box confirm-box--info' };
    const overlay  = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = `
        <div class="${clases[variant] ?? clases.danger}">
            <i class="fas ${iconos[variant] ?? iconos.danger}"></i>
            <h4>${titulo}</h4>
            <p>${texto}</p>
            <div class="confirm-actions">
                <button class="btn-primary confirm-ok">${btnLabel}</button>
                <button class="btn-secondary confirm-cancel">Cancelar</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('.confirm-ok').onclick     = () => { overlay.remove(); onConfirm(); };
    overlay.querySelector('.confirm-cancel').onclick = () => overlay.remove();
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
}

// ─── Permisos UI ─────────────────────────────────────────────────────────────

const ADMIN_SECTIONS = ['users', 'logs', 'databases', 'backups'];

function aplicarPermisosUI(rol) {
    const esAdmin = rol === 'administrador';

    // Mostrar/ocultar elementos marcados con data-admin-only
    document.querySelectorAll('[data-admin-only]').forEach(el => {
        el.style.display = esAdmin ? '' : 'none';
    });

    // Badge de rol en el header
    const rolBadge = document.getElementById('header-rol-badge');
    if (rolBadge) {
        rolBadge.textContent  = esAdmin ? 'Admin' : 'Usuario';
        rolBadge.className    = 'rol-badge rol-badge--' + (esAdmin ? 'admin' : 'usuario');
        rolBadge.style.display = '';
    }

    // Si la sección activa es de admin y el usuario no lo es, volver al dashboard
    if (!esAdmin) {
        const activo = document.querySelector('.content-section.active');
        if (activo && ADMIN_SECTIONS.includes(activo.id)) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            document.getElementById('dashboard')?.classList.add('active');
            document.querySelector('[data-section="dashboard"]')?.parentElement.classList.add('active');
        }
    }
}

// ─── Manejo de errores unificado ──────────────────────────────────────────────

function manejarApiError(e, msg) {
    console.error('[API]', msg, e);
    mostrarToast(msg, 'error');
}

// ─── Paginación ───────────────────────────────────────────────────────────────

function renderPaginacion(meta, containerId, onPageChange) {
    const cont = document.getElementById(containerId);
    if (!cont) return;
    if (!meta || meta.paginas <= 1) { cont.innerHTML = ''; return; }

    const { total, pagina, limite, paginas } = meta;
    const desde = (pagina - 1) * limite + 1;
    const hasta  = Math.min(pagina * limite, total);

    let start = Math.max(1, pagina - 2);
    let end   = Math.min(paginas, start + 4);
    if (end - start < 4) start = Math.max(1, end - 4);

    let pagesHtml = '';
    if (start > 1) pagesHtml += `<button class="pag-btn" data-pag="1">1</button>`;
    if (start > 2) pagesHtml += `<span class="pag-sep">…</span>`;
    for (let i = start; i <= end; i++) {
        pagesHtml += `<button class="pag-btn${i === pagina ? ' active' : ''}" data-pag="${i}">${i}</button>`;
    }
    if (end < paginas - 1) pagesHtml += `<span class="pag-sep">…</span>`;
    if (end < paginas) pagesHtml += `<button class="pag-btn" data-pag="${paginas}">${paginas}</button>`;

    cont.innerHTML = `
        <div class="pag-info">Mostrando ${desde}–${hasta} de ${total}</div>
        <div class="pag-controles">
            <button class="pag-btn pag-prev" data-pag="${pagina - 1}" ${pagina <= 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left"></i>
            </button>
            ${pagesHtml}
            <button class="pag-btn pag-next" data-pag="${pagina + 1}" ${pagina >= paginas ? 'disabled' : ''}>
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>`;

    cont.querySelectorAll('.pag-btn:not([disabled])').forEach(btn =>
        btn.addEventListener('click', () => onPageChange(parseInt(btn.dataset.pag))));
}

// ─── Actividad Reciente ───────────────────────────────────────────────────────

const _ACTIVIDAD_ICONOS = {
    success: 'fa-check',
    info:    'fa-pencil-alt',
    danger:  'fa-trash',
};

async function cargarActividadReciente() {
    try {
        const r = await fetchSeguro('/api/actividad_reciente.php?t=' + Date.now());
        const d = await r.json();
        if (!d.ok) return;

        const lista = document.getElementById('actividad-lista');
        if (!lista) return;

        if (!d.data.length) {
            lista.innerHTML = `
                <div class="activity-item">
                    <div class="activity-icon info"><i class="fas fa-inbox"></i></div>
                    <div class="activity-content">
                        <p class="activity-text">Sin actividad registrada</p>
                    </div>
                </div>`;
            return;
        }

        lista.innerHTML = d.data.map(a => {
            const ico = _ACTIVIDAD_ICONOS[a.tipo] ?? 'fa-circle';
            return `
                <div class="activity-item">
                    <div class="activity-icon ${a.tipo}"><i class="fas ${ico}"></i></div>
                    <div class="activity-content">
                        <p class="activity-text">${a.texto}</p>
                        <span class="activity-time">${a.tiempo} · ${a.usuario}</span>
                    </div>
                </div>`;
        }).join('');
    } catch (_) { /* silencio — no crítico */ }
}

// ─── Navegación programática ──────────────────────────────────────────────────

function navegarA(sectionId) {
    if (ADMIN_SECTIONS.includes(sectionId) && perfilData?.rol !== 'administrador') return;

    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));

    const link = document.querySelector(`.nav-link[data-section="${sectionId}"]`);
    if (link) link.parentElement.classList.add('active');
    document.getElementById(sectionId)?.classList.add('active');

    if (sectionId === 'configuracion') Configuracion.init();
    if (sectionId === 'statistics')  Estadisticas.init();
    if (sectionId === 'contactos')   Contactos.init();
    if (sectionId === 'leads')       Leads.init();
    if (sectionId === 'oportunidades') Oportunidades.init();
    if (sectionId === 'email')       Email.init();
    if (sectionId === 'domains')     Dominios.init();
    if (sectionId === 'users'      && typeof Usuarios  !== 'undefined') Usuarios.init();
    if (sectionId === 'logs'       && typeof Auditoria !== 'undefined') Auditoria.init();
    if (sectionId === 'databases'  && typeof Databases !== 'undefined') Databases.init();
    if (sectionId === 'backups'    && typeof Backups   !== 'undefined') Backups.init();
}

// ─── Acciones rápidas del dashboard ──────────────────────────────────────────

function initQuickActions() {
    document.querySelectorAll('.quick-action-btn[data-action]').forEach(btn => {
        btn.addEventListener('click', () => manejarAccionRapida(btn.dataset.action));
    });
}

function manejarAccionRapida(action) {
    switch (action) {
        case 'backup':
            if (typeof Backups !== 'undefined') {
                Backups.confirmarCrear();
            } else {
                mostrarToast('Solo los administradores pueden crear backups', 'error');
            }
            break;

        case 'create-email':
            navegarA('email');
            setTimeout(() => document.getElementById('email-nuevo-btn')?.click(), 50);
            break;

        case 'create-ftp':
            navegarA('ftp');
            break;

        case 'install-ssl':
            navegarA('ssl');
            break;
    }
}
