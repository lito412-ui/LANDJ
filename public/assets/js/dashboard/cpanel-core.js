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
    } catch (error) {
        console.error('Error validando sesion:', error.message);
        window.location.href = '/modules/site/login.html';
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
        if (!r.ok) throw new Error('Error de conexión con el servidor');
        const d = await r.json();
        if (d.error) return;

        setMetrica('disco-texto', 'disco-barra', d.disco);

        const ramPct = d.ram_total > 0
            ? Math.min((d.ram_usada / d.ram_total) * 100, 100).toFixed(1)
            : '0.0';
        setMetrica('ram-texto', 'ram-barra', ramPct);

        setMetrica('cpu-texto', 'cpu-barra', d.cpu);

    } catch (e) {
        console.error('Error en actualización:', e.message);
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
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            link.parentElement.classList.add('active');
            document.getElementById(sectionId)?.classList.add('active');
            document.getElementById('user-dropdown')?.classList.remove('show');

            if (sectionId === 'perfil'        && perfilData) mostrarPerfil(perfilData);
            if (sectionId === 'contactos')    Contactos.init();
            if (sectionId === 'leads')        Leads.init();
            if (sectionId === 'users')        Usuarios.init();
            if (sectionId === 'oportunidades') Oportunidades.init();
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
