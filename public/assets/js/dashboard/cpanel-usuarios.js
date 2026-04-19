// ─── Módulo Usuarios ──────────────────────────────────────────────────────────

const Usuarios = (() => {
    let editId      = null;
    let buscarTimer = null;
    let meId        = null;

    const ROLES = {
        administrador: { label: 'Administrador', cls: 'badge-administrador' },
        usuario:       { label: 'Usuario',        cls: 'badge-usuario'       },
    };

    function badgeRol(rol) {
        const r = ROLES[rol] ?? { label: rol, cls: 'badge-usuario' };
        return `<span class="user-badge ${r.cls}">${r.label}</span>`;
    }

    function esMeId(id) {
        return meId !== null && parseInt(id) === parseInt(meId);
    }

    function init() {
        if (meId === null && perfilData) meId = perfilData.id_usuario ?? null;

        const nuevoBtn   = document.getElementById('users-nuevo-btn');
        const cancelBtn  = document.getElementById('users-cancelar-btn');
        const form       = document.getElementById('users-form');
        const buscarInp  = document.getElementById('users-buscar');
        const filtroRol  = document.getElementById('users-filtro-rol');

        nuevoBtn?.addEventListener('click',  () => abrirForm(null));
        cancelBtn?.addEventListener('click', cerrarForm);
        form?.addEventListener('submit',     guardar);

        buscarInp?.addEventListener('input', () => {
            clearTimeout(buscarTimer);
            buscarTimer = setTimeout(cargar, 400);
        });
        filtroRol?.addEventListener('change', cargar);

        initCampos();
        cargar();
    }

    async function cargar() {
        const buscar = document.getElementById('users-buscar')?.value.trim() ?? '';
        const rol    = document.getElementById('users-filtro-rol')?.value ?? '';

        const params = new URLSearchParams();
        if (buscar) params.set('buscar', buscar);

        const url = '/api/usuarios.php' + (params.toString() ? '?' + params : '');
        try {
            const r = await fetchSeguro(url);
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }

            let lista = d.data;
            if (rol) lista = lista.filter(u => u.rol === rol);

            renderTabla(lista);
        } catch { mostrarToast('Error al cargar usuarios', 'error'); }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('users-tbody');
        if (!tbody) return;

        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="5" class="crm-empty">
                    <i class="fas fa-users"></i>
                    <p>No se encontraron usuarios. <button class="btn-link" id="user-crear-primero">Crear el primero</button></p>
                </td></tr>`;
            tbody.querySelector('#user-crear-primero')?.addEventListener('click', () => abrirForm(null));
            return;
        }

        tbody.innerHTML = lista.map(u => {
            const iniciales = (u.nombre || '?').slice(0, 2).toUpperCase();
            const yoTag     = esMeId(u.id_usuario) ? '<span class="user-yo-tag">Tú</span>' : '';
            return `<tr>
                <td>
                    <div class="crm-nombre-cell">
                        <span class="crm-avatar">${esc(iniciales)}</span>
                        <div>
                            <strong>${esc(u.nombre)}${yoTag}</strong>
                        </div>
                    </div>
                </td>
                <td>${esc(u.email || '—')}</td>
                <td>${badgeRol(u.rol)}</td>
                <td>${formatFecha(u.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" data-uedit="${u.id_usuario}" title="Editar"><i class="fas fa-edit"></i></button>
                        ${esMeId(u.id_usuario)
                            ? `<button class="btn-icon danger" disabled title="No puedes eliminarte a ti mismo" style="opacity:.35;cursor:not-allowed"><i class="fas fa-trash"></i></button>`
                            : `<button class="btn-icon danger" data-udel="${u.id_usuario}" data-unombre="${esc(u.nombre)}" title="Eliminar"><i class="fas fa-trash"></i></button>`
                        }
                    </div>
                </td>
            </tr>`;
        }).join('');

        tbody.querySelectorAll('[data-uedit]').forEach(btn =>
            btn.addEventListener('click', () => abrirForm(parseInt(btn.dataset.uedit)))
        );
        tbody.querySelectorAll('[data-udel]').forEach(btn =>
            btn.addEventListener('click', () => eliminar(parseInt(btn.dataset.udel), btn.dataset.unombre))
        );
    }

    function abrirForm(id) {
        editId = id;
        const panel  = document.getElementById('users-form-panel');
        const titulo = document.getElementById('users-form-titulo');
        const label  = document.getElementById('uf-pass-label');

        limpiarEstados();
        limpiarForm();

        if (id) {
            titulo.textContent = 'Editar Usuario';
            if (label) label.innerHTML = 'Contraseña <small style="color:#94a3b8;font-weight:400">(dejar vacío para no cambiarla)</small>';
            cargarForm(id);
        } else {
            titulo.textContent = 'Nuevo Usuario';
            if (label) label.innerHTML = 'Contraseña <span class="form-required">*</span>';
        }

        panel?.classList.add('active');
        panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function cargarForm(id) {
        try {
            const r = await fetchSeguro(`/api/usuarios.php?id=${id}`);
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            const u = d.data;
            setValue('uf-nombre',   u.nombre);
            setValue('uf-email',    u.email ?? '');
            setValue('uf-rol',      u.rol);
            setValue('uf-password', '');
        } catch { mostrarToast('Error al cargar usuario', 'error'); }
    }

    function cerrarForm() {
        document.getElementById('users-form-panel')?.classList.remove('active');
        editId = null;
        limpiarForm();
        limpiarEstados();
    }

    async function guardar(e) {
        e.preventDefault();
        if (!validarTodo()) return;

        const body = {
            nombre:   getValue('uf-nombre'),
            email:    getValue('uf-email')    || null,
            rol:      getValue('uf-rol'),
            password: getValue('uf-password') || '',
        };

        const url    = editId ? `/api/usuarios.php?id=${editId}` : '/api/usuarios.php';
        const method = editId ? 'PUT' : 'POST';

        try {
            const r = await fetchSeguro(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast(editId ? 'Usuario actualizado' : 'Usuario creado', 'success');
            cerrarForm();
            cargar();
        } catch { mostrarToast('Error al guardar', 'error'); }
    }

    function eliminar(id, nombre) {
        mostrarConfirm(
            'Eliminar usuario',
            `¿Eliminar a <strong>${nombre}</strong>? Esta acción no se puede deshacer.`,
            async () => {
                try {
                    const r = await fetchSeguro(`/api/usuarios.php?id=${id}`, { method: 'DELETE' });
                    const d = await r.json();
                    if (!d.ok) { mostrarToast(d.error, 'error'); cargar(); return; }
                    mostrarToast('Usuario eliminado', 'success');
                    cargar();
                } catch { mostrarToast('Error al eliminar', 'error'); }
            }
        );
    }

    // ─── Validación inline ───────────────────────────────────────────────────

    const NOMBRE_RE = /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ0-9\s_\-.]+$/u;
    const EMAIL_RE  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const rules = {
        'uf-nombre':   { required: true,  max: 100, pattern: NOMBRE_RE, patternMsg: 'Solo letras, números, espacios, guiones y puntos' },
        'uf-email':    { required: false, max: 255, pattern: EMAIL_RE,  patternMsg: 'Formato de email inválido' },
        'uf-rol':      { required: true },
        'uf-password': { required: false, min: 8, max: 72, passStrength: true },
    };

    function validarCampo(id, value) {
        const r = rules[id];
        if (!r) return null;
        const v = value.trim();

        const esNuevo = editId === null;
        if (id === 'uf-password') {
            if (esNuevo && v === '') return 'La contraseña es obligatoria';
            if (v !== '') {
                if (v.length < 8)  return 'Mínimo 8 caracteres';
                if (v.length > 72) return 'Máximo 72 caracteres';
                if (!/[a-zA-Z]/.test(v) || !/[0-9]/.test(v)) return 'Debe contener letras y números';
            }
            return null;
        }

        if (r.required && v === '') return 'Este campo es obligatorio';
        if (!r.required && v === '') return null;
        if (r.max && v.length > r.max) return `Máximo ${r.max} caracteres`;
        if (r.pattern && !r.pattern.test(v)) return r.patternMsg;
        return null;
    }

    function mostrarError(id, msg) {
        const input = document.getElementById(id);
        const key   = id.replace('uf-', '');
        const span  = document.getElementById('uerr-' + key);
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
            const msg = validarCampo(id, el.value);
            mostrarError(id, msg);
            if (msg) valido = false;
        });
        return valido;
    }

    function initCampos() {
        Object.keys(rules).forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', () => mostrarError(id, validarCampo(id, el.value)));
            el.addEventListener('blur',  () => mostrarError(id, validarCampo(id, el.value)));
        });
    }

    function limpiarEstados() {
        Object.keys(rules).forEach(id => {
            const el   = document.getElementById(id);
            const key  = id.replace('uf-', '');
            const span = document.getElementById('uerr-' + key);
            if (el)   el.classList.remove('form-input--error', 'form-input--ok');
            if (span) span.textContent = '';
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    function limpiarForm() {
        ['uf-nombre', 'uf-email', 'uf-password'].forEach(id => setValue(id, ''));
        setValue('uf-rol', 'usuario');
    }

    function getValue(id) {
        return document.getElementById(id)?.value ?? '';
    }

    function setValue(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val;
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

    return { init };
})();
