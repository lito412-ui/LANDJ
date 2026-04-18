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
        const response = await fetch('/api/get_user.php?t=' + Date.now());
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
        const r = await fetch('/api/monitorizacion.php?t=' + Date.now());
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

            if (sectionId === 'perfil' && perfilData) mostrarPerfil(perfilData);
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