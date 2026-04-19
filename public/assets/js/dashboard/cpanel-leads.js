// ─── Módulo Leads ─────────────────────────────────────────────────────────────

const Leads = (() => {
    let editId      = null;
    let buscarTimer = null;
    let detalleId   = null;

    const ESTADOS = {
        nuevo:       { label: 'Nuevo',       cls: 'badge-nuevo' },
        contactado:  { label: 'Contactado',  cls: 'badge-contactado' },
        calificado:  { label: 'Calificado',  cls: 'badge-calificado' },
        convertido:  { label: 'Convertido',  cls: 'badge-convertido' },
        descartado:  { label: 'Descartado',  cls: 'badge-descartado' },
    };

    function badgeHTML(estado) {
        const e = ESTADOS[estado] ?? { label: estado, cls: '' };
        return `<span class="lead-badge ${e.cls}">${e.label}</span>`;
    }

    let _estado = { buscar: '', estado: '', origen: '', desde: '', hasta: '', orden: 'created_at', dir: 'desc' };

    function _contarFiltrosActivos() {
        return ['origen', 'desde', 'hasta'].filter(k => _estado[k] !== '').length;
    }

    function _actualizarBadge() {
        const n = _contarFiltrosActivos();
        const badge = document.getElementById('ld-filtros-badge');
        if (badge) { badge.textContent = n; badge.style.display = n > 0 ? '' : 'none'; }
    }

    function init() {
        const overlay = document.getElementById('lead-detalle-overlay');
        if (overlay) document.body.appendChild(overlay);

        document.getElementById('leads-nuevo-btn')
            ?.addEventListener('click', () => abrirForm());
        document.getElementById('leads-cancelar-btn')
            ?.addEventListener('click', cerrarForm);
        document.getElementById('leads-form')
            ?.addEventListener('submit', guardar);

        document.getElementById('leads-buscar')
            ?.addEventListener('input', (e) => {
                clearTimeout(buscarTimer);
                _estado.buscar = e.target.value.trim();
                buscarTimer = setTimeout(cargar, 400);
            });

        document.getElementById('leads-filtro-estado')
            ?.addEventListener('change', (e) => { _estado.estado = e.target.value; cargar(); });

        // Toggle filtros avanzados
        document.getElementById('ld-filtros-toggle')
            ?.addEventListener('click', () => {
                document.getElementById('ld-filtros-avanzados')?.classList.toggle('active');
            });

        // Filtros avanzados
        const filtroImmediate = (key, id) => {
            document.getElementById(id)?.addEventListener('change', (e) => {
                _estado[key] = e.target.value;
                _actualizarBadge();
                cargar();
            });
        };
        const filtroDebounced = (key, id) => {
            document.getElementById(id)?.addEventListener('input', (e) => {
                clearTimeout(buscarTimer);
                _estado[key] = e.target.value.trim();
                buscarTimer = setTimeout(() => { _actualizarBadge(); cargar(); }, 400);
            });
        };

        filtroDebounced('origen', 'ld-filtro-origen');
        filtroImmediate('desde',  'ld-filtro-desde');
        filtroImmediate('hasta',  'ld-filtro-hasta');
        filtroImmediate('orden',  'ld-filtro-orden');

        document.getElementById('ld-filtro-dir')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            _estado.dir = _estado.dir === 'desc' ? 'asc' : 'desc';
            btn.dataset.dir = _estado.dir;
            btn.querySelector('i').className = _estado.dir === 'asc'
                ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down';
            cargar();
        });

        document.getElementById('ld-filtros-clear')?.addEventListener('click', () => {
            _estado = { buscar: _estado.buscar, estado: _estado.estado, origen: '', desde: '', hasta: '', orden: 'created_at', dir: 'desc' };
            ['ld-filtro-origen','ld-filtro-desde','ld-filtro-hasta'].forEach(id => {
                const el = document.getElementById(id); if (el) el.value = '';
            });
            const ord = document.getElementById('ld-filtro-orden'); if (ord) ord.value = 'created_at';
            const dir = document.getElementById('ld-filtro-dir');
            if (dir) { dir.dataset.dir = 'desc'; dir.querySelector('i').className = 'fas fa-sort-amount-down'; }
            _actualizarBadge();
            cargar();
        });

        initCampos();
        cargar();
    }

    function initCampos() {
        const NOMBRE_RE = /^[a-zA-ZáéíóúüñÁÉÍÓÚÜÑ\s'\-]+$/u;
        const EMAIL_RE  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        const rules = {
            'lf-nombre':   { required: true,  max: 100, pattern: NOMBRE_RE, patternMsg: 'Solo letras, espacios, guiones y apóstrofes' },
            'lf-email':    { required: false, max: 255, pattern: EMAIL_RE,  patternMsg: 'Formato de email inválido' },
            'lf-telefono': { required: false, max: 30,  phone: true },
            'lf-empresa':  { required: false, max: 150 },
            'lf-origen':   { required: false, max: 50 },
            'lf-notas':    { required: false, max: 500 },
        };

        Object.keys(rules).forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('input', () => {
                if (id === 'lf-notas') {
                    const cnt = document.getElementById('lnotas-count');
                    if (cnt) cnt.textContent = el.value.length;
                }
                validarCampo(id, el.value, rules);
            });
            el.addEventListener('blur', () => {
                if (id === 'lf-nombre') el.value = el.value.replace(/\b(\w)/g, c => c.toUpperCase());
                validarCampo(id, el.value, rules);
            });
        });

        window._leadsRules = rules;
    }

    function validarCampo(id, value, rules) {
        const r = rules[id];
        if (!r) return null;
        const v = value.trim();
        let msg = null;
        if (r.required && v === '') msg = 'Este campo es obligatorio';
        else if (!r.required && v === '') msg = null;
        else if (r.max && v.length > r.max) msg = `Máximo ${r.max} caracteres`;
        else if (r.phone) {
            const digits = v.replace(/[\s\-]/g, '');
            if (!/^[6-9]\d{8}$/.test(digits)) msg = 'Teléfono español inválido (ej: 612 345 678)';
        }
        else if (r.pattern && !r.pattern.test(v)) msg = r.patternMsg;

        const input = document.getElementById(id);
        const span  = document.getElementById('lerr-' + id.replace('lf-', ''));
        if (input) {
            input.classList.toggle('form-input--error', !!msg);
            input.classList.toggle('form-input--ok', !msg && v !== '');
        }
        if (span) span.textContent = msg ?? '';
        return msg;
    }

    function validarTodo() {
        const rules = window._leadsRules ?? {};
        return Object.keys(rules).every(id => {
            const el = document.getElementById(id);
            return !validarCampo(id, el?.value ?? '', rules);
        });
    }

    function limpiarEstados() {
        ['lf-nombre','lf-email','lf-telefono','lf-empresa','lf-origen','lf-notas'].forEach(id => {
            const el   = document.getElementById(id);
            const span = document.getElementById('lerr-' + id.replace('lf-', ''));
            if (el) el.classList.remove('form-input--error', 'form-input--ok');
            if (span) span.textContent = '';
        });
        const cnt = document.getElementById('lnotas-count');
        if (cnt) cnt.textContent = '0';
    }

    async function cargar() {
        const params = new URLSearchParams();
        Object.entries(_estado).forEach(([k, v]) => { if (v !== '') params.set(k, v); });
        const url = '/api/leads.php' + (params.size ? '?' + params : '');
        try {
            const r = await fetchSeguro(url);
            const d = await r.json();
            d.ok ? renderTabla(d.data) : mostrarToast(d.error, 'error');
        } catch {
            mostrarToast('Error al cargar leads', 'error');
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('leads-tbody');
        if (!tbody) return;
        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="crm-empty">
                    <i class="fas fa-funnel-dollar"></i>
                    <p>No hay leads. <button class="btn-link" id="lead-crear-primero">Crear el primero</button></p>
                </td></tr>`;
            tbody.querySelector('#lead-crear-primero')?.addEventListener('click', () => abrirForm());
            return;
        }
        tbody.innerHTML = lista.map(l => `
            <tr>
                <td>
                    <div class="crm-nombre-cell">
                        <span class="crm-avatar lead-av">${esc(l.nombre).slice(0,2).toUpperCase()}</span>
                        <div>
                            <strong>${esc(l.nombre)}</strong>
                            <small>${esc(l.empresa || '')}</small>
                        </div>
                    </div>
                </td>
                <td>${esc(l.email    || '—')}</td>
                <td>${esc(l.telefono || '—')}</td>
                <td>${esc(l.origen   || '—')}</td>
                <td>${badgeHTML(l.estado)}</td>
                <td>${formatFecha(l.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" data-lver="${l.id_lead}" title="Ver detalle"><i class="fas fa-eye"></i></button>
                        <button class="btn-icon" data-ledit="${l.id_lead}" title="Editar"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon danger" data-ldel="${l.id_lead}" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>`).join('');

        tbody.querySelectorAll('[data-lver]').forEach(btn =>
            btn.addEventListener('click', () => abrirDetalle(parseInt(btn.dataset.lver))));
        tbody.querySelectorAll('[data-ledit]').forEach(btn =>
            btn.addEventListener('click', () => abrirForm(parseInt(btn.dataset.ledit))));
        tbody.querySelectorAll('[data-ldel]').forEach(btn =>
            btn.addEventListener('click', () => eliminar(parseInt(btn.dataset.ldel))));
    }

    async function abrirForm(id = null) {
        editId = id;
        const panel  = document.getElementById('leads-form-panel');
        const titulo = document.getElementById('leads-form-titulo');
        if (titulo) titulo.textContent = id ? 'Editar Lead' : 'Nuevo Lead';
        document.getElementById('leads-form')?.reset();
        limpiarEstados();
        if (id) {
            try {
                const r = await fetchSeguro(`/api/leads.php?id=${id}`);
                const d = await r.json();
                if (d.ok) rellenarForm(d.data);
            } catch { mostrarToast('Error al cargar datos', 'error'); return; }
        }
        panel?.classList.add('active');
        panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        document.getElementById('leads-form-panel')?.classList.remove('active');
        document.getElementById('leads-form')?.reset();
        limpiarEstados();
        editId = null;
    }

    function rellenarForm(l) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };
        set('lf-nombre',   l.nombre);
        set('lf-email',    l.email);
        set('lf-telefono', l.telefono);
        set('lf-empresa',  l.empresa);
        set('lf-origen',   l.origen);
        set('lf-notas',    l.notas);
        const estadoEl = document.getElementById('lf-estado');
        if (estadoEl) estadoEl.value = l.estado ?? 'nuevo';
        const cnt = document.getElementById('lnotas-count');
        if (cnt) cnt.textContent = (l.notas ?? '').length;
    }

    async function guardar(e) {
        e.preventDefault();
        if (!validarTodo()) return;
        const btn = document.getElementById('leads-guardar-btn');
        btn.disabled = true;
        const payload = {
            nombre:   document.getElementById('lf-nombre').value.trim(),
            email:    document.getElementById('lf-email').value.trim(),
            telefono: document.getElementById('lf-telefono').value.trim(),
            empresa:  document.getElementById('lf-empresa').value.trim(),
            origen:   document.getElementById('lf-origen').value.trim(),
            estado:   document.getElementById('lf-estado').value,
            notas:    document.getElementById('lf-notas').value.trim(),
        };
        const url    = editId ? `/api/leads.php?id=${editId}` : '/api/leads.php';
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
                mostrarToast(editId ? 'Lead actualizado' : 'Lead creado', 'success');
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
        mostrarConfirm('¿Eliminar este lead?', 'Esta acción no se puede deshacer.',
            async () => {
                try {
                    const r = await fetchSeguro(`/api/leads.php?id=${id}`, { method: 'DELETE' });
                    const d = await r.json();
                    if (d.ok) { cargar(); mostrarToast('Lead eliminado', 'success'); }
                    else { mostrarToast(d.error || 'Error al eliminar', 'error'); cargar(); }
                } catch { mostrarToast('Error de conexión', 'error'); }
            }
        );
    }

    async function abrirDetalle(id) {
        detalleId = id;
        const overlay = document.getElementById('lead-detalle-overlay');
        overlay?.classList.add('active');
        document.body.classList.add('detalle-open');

        const cerrarBtn = document.getElementById('ldet-cerrar-btn');
        if (cerrarBtn) cerrarBtn.onclick = cerrarDetalle;
        if (overlay) overlay.onclick = (e) => { if (e.target === overlay) cerrarDetalle(); };
        const editarBtn = document.getElementById('ldet-editar-btn');
        if (editarBtn) editarBtn.onclick = () => { const id = detalleId; cerrarDetalle(); abrirForm(id); };
        const eliminarBtn = document.getElementById('ldet-eliminar-btn');
        if (eliminarBtn) eliminarBtn.onclick = () => { const id = detalleId; cerrarDetalle(); eliminar(id); };
        const convertirBtn = document.getElementById('ldet-convertir-btn');
        if (convertirBtn) convertirBtn.onclick = () => convertir(detalleId);

        try {
            const r = await fetchSeguro(`/api/leads.php?id=${id}`);
            const d = await r.json();
            if (d.ok) renderDetalle(d.data);
            else mostrarToast(d.error, 'error');
        } catch { mostrarToast('Error al cargar el lead', 'error'); }

        ActividadesWidget.init({ prefix: 'ldet', entityType: 'lead', entityId: id });
    }

    function cerrarDetalle() {
        document.getElementById('lead-detalle-overlay')?.classList.remove('active');
        document.body.classList.remove('detalle-open');
        detalleId = null;
    }

    function renderDetalle(l) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
        set('ldet-avatar',        (l.nombre || '?').slice(0,2).toUpperCase());
        set('ldet-nombre',        l.nombre);
        set('ldet-empresa',       l.empresa || '');
        set('ldet-email',         l.email);
        set('ldet-telefono',      l.telefono);
        set('ldet-empresa-campo', l.empresa);
        set('ldet-origen',        l.origen);
        set('ldet-fecha',         formatFecha(l.created_at));

        const estadoBloque = document.getElementById('ldet-estado-bloque');
        if (estadoBloque) estadoBloque.innerHTML = badgeHTML(l.estado);

        const notasBloque = document.getElementById('ldet-notas-bloque');
        const notasEl     = document.getElementById('ldet-notas');
        if (notasEl) notasEl.textContent = l.notas || '';
        if (notasBloque) notasBloque.style.display = l.notas ? '' : 'none';

        const btnConvertir = document.getElementById('ldet-convertir-btn');
        if (btnConvertir) {
            const yaConvertido = l.estado === 'convertido' || !!l.contacto_id;
            btnConvertir.disabled = yaConvertido;
            btnConvertir.title    = yaConvertido ? 'Este lead ya fue convertido' : 'Convertir a contacto';
            btnConvertir.style.opacity = yaConvertido ? '0.35' : '';
        }
    }

    function convertir(id) {
        mostrarConfirm(
            'Convertir lead a contacto',
            'Se creará un nuevo contacto con los datos de este lead y se marcará como <strong>convertido</strong>.',
            async () => {
                try {
                    const r = await fetchSeguro(`/api/leads.php?id=${id}&action=convertir`, { method: 'PUT' });
                    const d = await r.json();
                    if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                    mostrarToast('Lead convertido a contacto correctamente', 'success');
                    cerrarDetalle();
                    cargar();
                } catch { mostrarToast('Error al convertir el lead', 'error'); }
            },
            'Convertir',
            'success'
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

    return { init };
})();
