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

    let _estado = { buscar: '', empresa: '', desde: '', hasta: '', orden: 'created_at', dir: 'desc', pagina: 1, limite: 20 };

    function _contarFiltrosActivos() {
        return ['empresa', 'desde', 'hasta'].filter(k => _estado[k] !== '').length;
    }

    function _actualizarBadge() {
        const n = _contarFiltrosActivos();
        const badge = document.getElementById('ct-filtros-badge');
        if (badge) { badge.textContent = n; badge.style.display = n > 0 ? '' : 'none'; }
    }

    function init() {
        const overlay = document.getElementById('contacto-detalle-overlay');
        if (overlay) document.body.appendChild(overlay);

        document.getElementById('contactos-nuevo-btn')
            ?.addEventListener('click', () => abrirForm());
        document.getElementById('contactos-cancelar-btn')
            ?.addEventListener('click', cerrarForm);
        document.getElementById('contactos-form')
            ?.addEventListener('submit', guardar);

        // Búsqueda principal (debounced)
        document.getElementById('contactos-buscar')
            ?.addEventListener('input', (e) => {
                clearTimeout(buscarTimer);
                _estado.buscar = e.target.value.trim();
                _estado.pagina = 1;
                buscarTimer = setTimeout(cargar, 400);
            });

        // Toggle filtros avanzados
        document.getElementById('ct-filtros-toggle')
            ?.addEventListener('click', () => {
                document.getElementById('ct-filtros-avanzados')?.classList.toggle('active');
            });

        // Filtros avanzados
        const filtroImmediate = (key, id) => {
            document.getElementById(id)?.addEventListener('change', (e) => {
                _estado[key] = e.target.value;
                _estado.pagina = 1;
                _actualizarBadge();
                cargar();
            });
        };
        const filtroDebounced = (key, id) => {
            document.getElementById(id)?.addEventListener('input', (e) => {
                clearTimeout(buscarTimer);
                _estado[key] = e.target.value.trim();
                _estado.pagina = 1;
                buscarTimer = setTimeout(() => { _actualizarBadge(); cargar(); }, 400);
            });
        };

        filtroDebounced('empresa', 'ct-filtro-empresa');
        filtroImmediate('desde',   'ct-filtro-desde');
        filtroImmediate('hasta',   'ct-filtro-hasta');
        filtroImmediate('orden',   'ct-filtro-orden');

        document.getElementById('ct-filtro-dir')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            _estado.dir = _estado.dir === 'desc' ? 'asc' : 'desc';
            _estado.pagina = 1;
            btn.dataset.dir = _estado.dir;
            btn.querySelector('i').className = _estado.dir === 'asc'
                ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down';
            cargar();
        });

        document.getElementById('ct-filtros-clear')?.addEventListener('click', () => {
            _estado = { buscar: _estado.buscar, empresa: '', desde: '', hasta: '', orden: 'created_at', dir: 'desc', pagina: 1, limite: 20 };
            ['ct-filtro-empresa','ct-filtro-desde','ct-filtro-hasta'].forEach(id => {
                const el = document.getElementById(id); if (el) el.value = '';
            });
            const ord = document.getElementById('ct-filtro-orden'); if (ord) ord.value = 'created_at';
            const dir = document.getElementById('ct-filtro-dir');
            if (dir) { dir.dataset.dir = 'desc'; dir.querySelector('i').className = 'fas fa-sort-amount-down'; }
            _actualizarBadge();
            cargar();
        });

        ContactosValidacion.initCampos();
        cargar();
    }

    async function cargar() {
        const params = new URLSearchParams();
        Object.entries(_estado).forEach(([k, v]) => { if (v !== '' && v !== 0) params.set(k, v); });
        const url = '/api/contactos.php' + (params.size ? '?' + params : '');
        try {
            const r = await fetchSeguro(url);
            const d = await r.json();
            if (d.ok) {
                renderTabla(d.data);
                renderPaginacion(d.meta, 'ct-paginacion', (p) => { _estado.pagina = p; cargar(); });
            } else {
                mostrarToast(d.error, 'error');
            }
        } catch (e) {
            manejarApiError(e, 'Error al cargar contactos');
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
            } catch (e) { manejarApiError(e, 'Error al cargar datos'); return; }
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
                cargar();
                mostrarToast(editId ? 'Contacto actualizado' : 'Contacto creado', 'success');
            } else {
                mostrarToast(d.error || 'Error al guardar', 'error');
            }
        } catch (e) {
            manejarApiError(e, 'Error de conexión');
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
                        cargar();
                        mostrarToast('Contacto eliminado', 'success');
                    } else {
                        mostrarToast(d.error || 'Error al eliminar', 'error');
                    }
                } catch (e) {
                    manejarApiError(e, 'Error de conexión');
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

        const cerrarBtn = document.getElementById('det-cerrar-btn');
        if (cerrarBtn) cerrarBtn.onclick = cerrarDetalle;
        if (overlay) overlay.onclick = (e) => { if (e.target === overlay) cerrarDetalle(); };
        const editarBtn = document.getElementById('det-editar-btn');
        if (editarBtn) editarBtn.onclick = () => { const id = detalleId; cerrarDetalle(); abrirForm(id); };
        const eliminarBtn = document.getElementById('det-eliminar-btn');
        if (eliminarBtn) eliminarBtn.onclick = () => { const id = detalleId; cerrarDetalle(); eliminar(id); };

        try {
            const r = await fetchSeguro(`/api/contactos.php?id=${id}`);
            const d = await r.json();
            if (d.ok) renderDetalle(d.data);
            else mostrarToast(d.error, 'error');
        } catch (e) {
            manejarApiError(e, 'Error al cargar el contacto');
        }

        ActividadesWidget.init({ prefix: 'det', entityType: 'contacto', entityId: id });
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

    return { init };
})();
