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

            if (sectionId === 'perfil'    && perfilData) mostrarPerfil(perfilData);
            if (sectionId === 'contactos') Contactos.init();
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

function mostrarConfirm(titulo, texto, onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'confirm-overlay';
    overlay.innerHTML = `
        <div class="confirm-box">
            <i class="fas fa-exclamation-triangle"></i>
            <h4>${titulo}</h4>
            <p>${texto}</p>
            <div class="confirm-actions">
                <button class="btn-primary" id="confirm-ok">Eliminar</button>
                <button class="btn-secondary" id="confirm-cancel">Cancelar</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('#confirm-ok').onclick = () => { overlay.remove(); onConfirm(); };
    overlay.querySelector('#confirm-cancel').onclick = () => overlay.remove();
    overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };
}

// ─── Validaciones de Contactos ───────────────────────────────────────────────

const ContactosValidacion = (() => {
    const NOMBRE_RE = /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s'\-]+$/u;
    const EMAIL_RE  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const TEL_RE    = /^[6-9]\d{8}$/;

    const rules = {
        'f-nombre':    { required: true,  max: 100, pattern: NOMBRE_RE,  patternMsg: 'Solo letras, espacios, guiones y apóstrofes' },
        'f-apellidos': { required: false, max: 100, pattern: NOMBRE_RE,  patternMsg: 'Solo letras, espacios, guiones y apóstrofes' },
        'f-email':     { required: false, max: 255, pattern: EMAIL_RE,   patternMsg: 'Formato de email inválido' },
        'f-telefono':  { required: false, max: 30,  phone: true },
        'f-empresa':   { required: false, max: 150, noHtml: true },
        'f-notas':     { required: false, max: 500 },
    };

    function normalizarNombre(val) {
        return val.replace(/\b(\w)/g, c => c.toUpperCase());
    }

    function normalizarTelefono(val) {
        return val.replace(/[^\d\s\-]/g, '');
    }

    function validar(id, value) {
        const r = rules[id];
        if (!r) return null;
        const v = value.trim();

        if (r.required && v === '') return 'Este campo es obligatorio';
        if (!r.required && v === '') return null;

        if (r.max && v.length > r.max) return `Máximo ${r.max} caracteres`;

        if (r.phone) {
            const digits = v.replace(/[\s\-]/g, '');
            if (!TEL_RE.test(digits)) return 'Teléfono español inválido (ej: 612 345 678)';
        }

        if (r.pattern && !r.pattern.test(v)) return r.patternMsg;

        if (r.noHtml && /<|>|&lt;|&gt;/.test(v)) return 'No se permiten caracteres HTML';

        return null;
    }

    function mostrarError(id, msg) {
        const input = document.getElementById(id);
        const span  = document.getElementById('err-' + id.replace('f-', ''));
        if (!input) return;
        if (msg) {
            input.classList.add('form-input--error');
            input.classList.remove('form-input--ok');
            if (span) span.textContent = msg;
        } else {
            input.classList.remove('form-input--error');
            if (input.value.trim()) input.classList.add('form-input--ok');
            if (span) span.textContent = '';
        }
    }

    function validarTodo() {
        let valido = true;
        Object.keys(rules).forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const msg = validar(id, el.value);
            mostrarError(id, msg);
            if (msg) valido = false;
        });
        return valido;
    }

    function initCampos() {
        Object.keys(rules).forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            el.addEventListener('input', () => {
                if (id === 'f-notas') {
                    const cnt = document.getElementById('notas-count');
                    if (cnt) cnt.textContent = el.value.length;
                }
                if (id === 'f-telefono') el.value = normalizarTelefono(el.value);
                const msg = validar(id, el.value);
                mostrarError(id, msg);
            });

            el.addEventListener('blur', () => {
                if (id === 'f-nombre' || id === 'f-apellidos') {
                    el.value = normalizarNombre(el.value);
                }
                const msg = validar(id, el.value);
                mostrarError(id, msg);
            });
        });
    }

    function limpiarEstados() {
        Object.keys(rules).forEach(id => {
            const el   = document.getElementById(id);
            const span = document.getElementById('err-' + id.replace('f-', ''));
            if (el) { el.classList.remove('form-input--error', 'form-input--ok'); }
            if (span) span.textContent = '';
        });
        const cnt = document.getElementById('notas-count');
        if (cnt) cnt.textContent = '0';
    }

    return { initCampos, validarTodo, limpiarEstados };
})();

// ─── Módulo Contactos ─────────────────────────────────────────────────────────

const Contactos = (() => {
    let editId      = null;
    let buscarTimer = null;

    function init() {
        // Mover el panel de detalle a <body> para evitar que quede atrapado
        // por el contexto de apilamiento del section padre
        const overlay = document.getElementById('contacto-detalle-overlay');
        if (overlay) document.body.appendChild(overlay);

        document.getElementById('contactos-nuevo-btn')
            ?.addEventListener('click', () => abrirForm());
        document.getElementById('contactos-cancelar-btn')
            ?.addEventListener('click', cerrarForm);
        document.getElementById('contactos-form')
            ?.addEventListener('submit', guardar);
        document.getElementById('contactos-buscar')
            ?.addEventListener('input', (e) => {
                clearTimeout(buscarTimer);
                buscarTimer = setTimeout(() => cargar(e.target.value.trim()), 400);
            });
        ContactosValidacion.initCampos();
        cargar();
    }

    async function cargar(buscar = '') {
        const url = '/api/contactos.php' + (buscar ? '?buscar=' + encodeURIComponent(buscar) : '');
        try {
            const r = await fetchSeguro(url);
            const d = await r.json();
            d.ok ? renderTabla(d.data) : mostrarToast(d.error, 'error');
        } catch {
            mostrarToast('Error al cargar contactos', 'error');
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('contactos-tbody');
        if (!tbody) return;
        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="6" class="crm-empty">
                    <i class="fas fa-address-book"></i>
                    <p>No hay contactos.
                        <button class="btn-link" id="crm-crear-primero">Crear el primero</button>
                    </p>
                </td></tr>`;
            tbody.querySelector('#crm-crear-primero')
                ?.addEventListener('click', () => abrirForm());
            return;
        }
        tbody.innerHTML = lista.map(c => `
            <tr>
                <td>
                    <div class="crm-nombre-cell">
                        <span class="crm-avatar">${esc(c.nombre).slice(0,2).toUpperCase()}</span>
                        <div>
                            <strong>${esc(c.nombre)}${c.apellidos ? ' ' + esc(c.apellidos) : ''}</strong>
                            <small>${esc(c.empresa || '')}</small>
                        </div>
                    </div>
                </td>
                <td>${esc(c.email    || '—')}</td>
                <td>${esc(c.telefono || '—')}</td>
                <td>${esc(c.empresa  || '—')}</td>
                <td>${formatFecha(c.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" data-ver="${c.id_contacto}" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-icon" data-edit="${c.id_contacto}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon danger" data-del="${c.id_contacto}" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('');

        tbody.querySelectorAll('[data-ver]').forEach(btn =>
            btn.addEventListener('click', () => abrirDetalle(parseInt(btn.dataset.ver))));
        tbody.querySelectorAll('[data-edit]').forEach(btn =>
            btn.addEventListener('click', () => abrirForm(parseInt(btn.dataset.edit))));
        tbody.querySelectorAll('[data-del]').forEach(btn =>
            btn.addEventListener('click', () => eliminar(parseInt(btn.dataset.del))));
    }

    async function abrirForm(id = null) {
        editId = id;
        const panel  = document.getElementById('contactos-form-panel');
        const titulo = document.getElementById('contactos-form-titulo');
        if (titulo) titulo.textContent = id ? 'Editar Contacto' : 'Nuevo Contacto';
        limpiarForm();
        if (id) {
            try {
                const r = await fetchSeguro(`/api/contactos.php?id=${id}`);
                const d = await r.json();
                if (d.ok) rellenarForm(d.data);
            } catch { mostrarToast('Error al cargar datos', 'error'); return; }
        }
        panel?.classList.add('active');
        panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        document.getElementById('contactos-form-panel')?.classList.remove('active');
        limpiarForm();
        editId = null;
    }

    function limpiarForm() {
        document.getElementById('contactos-form')?.reset();
        ContactosValidacion.limpiarEstados();
    }

    function rellenarForm(c) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };
        set('f-nombre',    c.nombre);
        set('f-apellidos', c.apellidos);
        set('f-email',     c.email);
        set('f-telefono',  c.telefono);
        set('f-empresa',   c.empresa);
        set('f-notas',     c.notas);
    }

    async function guardar(e) {
        e.preventDefault();
        if (!ContactosValidacion.validarTodo()) return;
        const btn = document.getElementById('contactos-guardar-btn');
        btn.disabled = true;
        const payload = {
            nombre:    document.getElementById('f-nombre').value.trim(),
            apellidos: document.getElementById('f-apellidos').value.trim(),
            email:     document.getElementById('f-email').value.trim(),
            telefono:  document.getElementById('f-telefono').value.trim(),
            empresa:   document.getElementById('f-empresa').value.trim(),
            notas:     document.getElementById('f-notas').value.trim(),
        };
        const url    = editId ? `/api/contactos.php?id=${editId}` : '/api/contactos.php';
        const method = editId ? 'PUT' : 'POST';
        try {
            const r = await fetchSeguro(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (d.ok) {
                cerrarForm();
                cargar(document.getElementById('contactos-buscar')?.value.trim() || '');
                mostrarToast(editId ? 'Contacto actualizado' : 'Contacto creado', 'success');
            } else {
                mostrarToast(d.error || 'Error al guardar', 'error');
            }
        } catch {
            mostrarToast('Error de conexión', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    function eliminar(id) {
        mostrarConfirm(
            '¿Eliminar este contacto?',
            'Esta acción no se puede deshacer.',
            async () => {
                try {
                    const r = await fetchSeguro(`/api/contactos.php?id=${id}`, { method: 'DELETE' });
                    const d = await r.json();
                    if (d.ok) {
                        cargar(document.getElementById('contactos-buscar')?.value.trim() || '');
                        mostrarToast('Contacto eliminado', 'success');
                    } else {
                        mostrarToast(d.error || 'Error al eliminar', 'error');
                    }
                } catch {
                    mostrarToast('Error de conexión', 'error');
                }
            }
        );
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    function formatFecha(ts) {
        if (!ts) return '—';
        return new Date(ts).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    // ── Detalle ───────────────────────────────────────────────────────────────

    let detalleId = null;

    async function abrirDetalle(id) {
        detalleId = id;
        const overlay = document.getElementById('contacto-detalle-overlay');
        overlay?.classList.add('active');
        document.body.classList.add('detalle-open');

        document.getElementById('det-cerrar-btn')
            ?.addEventListener('click', cerrarDetalle, { once: true });
        overlay?.addEventListener('click', (e) => {
            if (e.target === overlay) cerrarDetalle();
        }, { once: true });
        document.getElementById('det-editar-btn')
            ?.addEventListener('click', () => { cerrarDetalle(); abrirForm(detalleId); }, { once: true });
        document.getElementById('det-eliminar-btn')
            ?.addEventListener('click', () => { cerrarDetalle(); eliminar(detalleId); }, { once: true });

        try {
            const r = await fetchSeguro(`/api/contactos.php?id=${id}`);
            const d = await r.json();
            if (d.ok) renderDetalle(d.data);
            else mostrarToast(d.error, 'error');
        } catch {
            mostrarToast('Error al cargar el contacto', 'error');
        }

        cargarActividadesDetalle(id);
    }

    function cerrarDetalle() {
        document.getElementById('contacto-detalle-overlay')?.classList.remove('active');
        document.body.classList.remove('detalle-open');
        detalleId = null;
    }

    function renderDetalle(c) {
        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val || '—';
        };
        const iniciales = (c.nombre || '?').slice(0, 2).toUpperCase();
        set('det-avatar',        iniciales);
        set('det-nombre',        [c.nombre, c.apellidos].filter(Boolean).join(' '));
        set('det-empresa',       c.empresa || '');
        set('det-email',         c.email);
        set('det-telefono',      c.telefono);
        set('det-empresa-campo', c.empresa);
        set('det-fecha',         formatFecha(c.created_at));

        const notasBloque = document.getElementById('det-notas-bloque');
        const notasEl     = document.getElementById('det-notas');
        if (notasEl) notasEl.textContent = c.notas || '';
        if (notasBloque) notasBloque.style.display = c.notas ? '' : 'none';
    }

    async function cargarActividadesDetalle(contactoId) {
        const lista = document.getElementById('det-actividades-lista');
        if (!lista) return;
        try {
            const r = await fetchSeguro(`/api/actividades.php?contacto_id=${contactoId}&limite=5`);
            if (!r.ok) throw new Error();
            const d = await r.json();
            if (!d.ok || !d.data?.length) {
                lista.innerHTML = '<li class="det-act-vacio">Sin actividades registradas</li>';
                return;
            }
            const iconos = { nota: 'fa-sticky-note', llamada: 'fa-phone', reunion: 'fa-users', tarea: 'fa-tasks', email: 'fa-envelope' };
            lista.innerHTML = d.data.map(a => `
                <li class="det-act-item">
                    <span class="det-act-icono det-act-${esc(a.tipo)}">
                        <i class="fas ${iconos[a.tipo] || 'fa-circle'}"></i>
                    </span>
                    <div class="det-act-info">
                        <span class="det-act-desc">${esc(a.descripcion)}</span>
                        <span class="det-act-fecha">${formatFecha(a.fecha || a.created_at)}</span>
                    </div>
                </li>`).join('');
        } catch {
            lista.innerHTML = '<li class="det-act-vacio">No disponible</li>';
        }
    }

    return { init };
})();