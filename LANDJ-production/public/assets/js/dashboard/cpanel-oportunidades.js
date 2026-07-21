// ─── Módulo Oportunidades ─────────────────────────────────────────────────────

const Oportunidades = (() => {
    let editId          = null;
    let detalleId       = null;
    let buscarTimer     = null;
    let _dragId         = null;
    let _dragSrcEtapa   = null;
    let _dragInit       = false;

    const ETAPAS = {
        prospecto:       { label: 'Prospecto',   cls: 'etapa-prospecto'   },
        propuesta:       { label: 'Propuesta',   cls: 'etapa-propuesta'   },
        negociacion:     { label: 'Negociación', cls: 'etapa-negociacion' },
        cerrada_ganada:  { label: 'Ganada',      cls: 'etapa-ganada'      },
        cerrada_perdida: { label: 'Perdida',     cls: 'etapa-perdida'     },
    };
    const ETAPAS_ORDER = ['prospecto', 'propuesta', 'negociacion', 'cerrada_ganada', 'cerrada_perdida'];

    let _estado = { buscar: '', etapa: '', valor_min: '', valor_max: '', cierre_desde: '', cierre_hasta: '', orden: 'created_at', dir: 'desc' };

    function _contarFiltrosActivos() {
        return ['valor_min', 'valor_max', 'cierre_desde', 'cierre_hasta'].filter(k => _estado[k] !== '').length;
    }

    function _actualizarBadge() {
        const n = _contarFiltrosActivos();
        const badge = document.getElementById('op-filtros-badge');
        if (badge) { badge.textContent = n; badge.style.display = n > 0 ? '' : 'none'; }
    }

    function init() {
        const overlay = document.getElementById('opor-detalle-overlay');
        if (overlay) document.body.appendChild(overlay);

        document.getElementById('opor-nuevo-btn')
            ?.addEventListener('click', () => abrirForm());
        document.getElementById('opor-cancelar-btn')
            ?.addEventListener('click', cerrarForm);
        document.getElementById('opor-form')
            ?.addEventListener('submit', guardar);

        document.getElementById('opor-buscar')
            ?.addEventListener('input', (e) => {
                clearTimeout(buscarTimer);
                _estado.buscar = e.target.value.trim();
                buscarTimer = setTimeout(cargar, 400);
            });

        document.getElementById('opor-filtro-etapa')
            ?.addEventListener('change', (e) => { _estado.etapa = e.target.value; cargar(); });

        const desc = document.getElementById('of-descripcion');
        if (desc) desc.addEventListener('input', () => {
            const cnt = document.getElementById('odesc-count');
            if (cnt) cnt.textContent = desc.value.length;
        });

        // Toggle filtros avanzados
        document.getElementById('op-filtros-toggle')
            ?.addEventListener('click', () => {
                document.getElementById('op-filtros-avanzados')?.classList.toggle('active');
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

        filtroDebounced('valor_min',    'op-filtro-valor-min');
        filtroDebounced('valor_max',    'op-filtro-valor-max');
        filtroImmediate('cierre_desde', 'op-filtro-cierre-desde');
        filtroImmediate('cierre_hasta', 'op-filtro-cierre-hasta');
        filtroImmediate('orden',        'op-filtro-orden');

        document.getElementById('op-filtro-dir')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            _estado.dir = _estado.dir === 'desc' ? 'asc' : 'desc';
            btn.dataset.dir = _estado.dir;
            btn.querySelector('i').className = _estado.dir === 'asc'
                ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down';
            cargar();
        });

        document.getElementById('op-filtros-clear')?.addEventListener('click', () => {
            _estado = { buscar: _estado.buscar, etapa: _estado.etapa, valor_min: '', valor_max: '', cierre_desde: '', cierre_hasta: '', orden: 'created_at', dir: 'desc' };
            ['op-filtro-valor-min','op-filtro-valor-max','op-filtro-cierre-desde','op-filtro-cierre-hasta'].forEach(id => {
                const el = document.getElementById(id); if (el) el.value = '';
            });
            const ord = document.getElementById('op-filtro-orden'); if (ord) ord.value = 'created_at';
            const dir = document.getElementById('op-filtro-dir');
            if (dir) { dir.dataset.dir = 'desc'; dir.querySelector('i').className = 'fas fa-sort-amount-down'; }
            _actualizarBadge();
            cargar();
        });

        _initDragDrop();
        cargar();
    }

    function _initDragDrop() {
        if (_dragInit) return;
        _dragInit = true;

        ETAPAS_ORDER.forEach(etapa => {
            const zone = document.getElementById('cards-' + etapa);
            if (!zone) return;

            zone.addEventListener('dragover', e => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                zone.classList.add('drop-target');
            });

            zone.addEventListener('dragleave', e => {
                if (!zone.contains(e.relatedTarget)) {
                    zone.classList.remove('drop-target');
                }
            });

            zone.addEventListener('drop', async e => {
                e.preventDefault();
                zone.classList.remove('drop-target');
                document.querySelectorAll('.pipeline-card.dragging')
                    .forEach(c => c.classList.remove('dragging'));

                if (!_dragId || etapa === _dragSrcEtapa) return;
                await _moverEtapaDirecto(_dragId, etapa);
                _dragId = null;
                _dragSrcEtapa = null;
            });
        });
    }

    async function _moverEtapaDirecto(id, etapa) {
        try {
            const r = await fetchSeguro(`/api/oportunidades.php?id=${id}&action=etapa`, {
                method:  'PUT',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({ etapa }),
            });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast(`Movida a "${ETAPAS[etapa]?.label ?? etapa}"`, 'success');
            cargar();
        } catch (e) { manejarApiError(e, 'Error al mover la oportunidad'); }
    }

    async function cargar() {
        const params = new URLSearchParams();
        Object.entries(_estado).forEach(([k, v]) => { if (v !== '') params.set(k, v); });
        const url = '/api/oportunidades.php' + (params.size ? '?' + params : '');
        try {
            const r = await fetchSeguro(url);
            const d = await r.json();
            d.ok ? renderBoard(d.data) : mostrarToast(d.error, 'error');
        } catch (e) { manejarApiError(e, 'Error al cargar oportunidades'); }
    }

    function renderBoard(lista) {
        const etapasVis = _estado.etapa ? [_estado.etapa] : ETAPAS_ORDER;

        etapasVis.forEach(etapa => {
            const items = lista.filter(o => o.etapa === etapa);
            const cont  = document.getElementById('cards-' + etapa);
            const cnt   = document.getElementById('cnt-' + etapa);
            if (!cont) return;
            if (cnt) cnt.textContent = items.length;

            if (!items.length) {
                cont.innerHTML = '<p class="pipeline-empty">Sin oportunidades</p>';
                return;
            }
            cont.innerHTML = items.map(o => `
                <div class="pipeline-card" data-oid="${o.id_oportunidad}" draggable="true">
                    <div class="pipeline-card-drag-handle"><i class="fas fa-grip-vertical"></i></div>
                    <div class="pipeline-card-titulo">${esc(o.titulo)}</div>
                    ${o.valor !== null ? `<div class="pipeline-card-valor">${formatValor(o.valor)}</div>` : ''}
                    <div class="pipeline-card-meta">
                        ${o.contacto_nombre ? `<span><i class="fas fa-user"></i>${esc(o.contacto_nombre)}</span>` : ''}
                        ${o.lead_nombre     ? `<span><i class="fas fa-funnel-dollar"></i>${esc(o.lead_nombre)}</span>` : ''}
                        ${o.fecha_cierre_esperada ? `<span><i class="fas fa-calendar-alt"></i>${formatFechaCorta(o.fecha_cierre_esperada)}</span>` : ''}
                    </div>
                </div>`).join('');

            cont.querySelectorAll('[data-oid]').forEach(card => {
                card.addEventListener('click', () => abrirDetalle(parseInt(card.dataset.oid)));

                card.addEventListener('dragstart', e => {
                    _dragId       = parseInt(card.dataset.oid);
                    _dragSrcEtapa = etapa;
                    card.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', String(_dragId));
                });

                card.addEventListener('dragend', () => {
                    card.classList.remove('dragging');
                    document.querySelectorAll('.pipeline-cards.drop-target')
                        .forEach(z => z.classList.remove('drop-target'));
                });
            });
        });
    }

    async function abrirForm(id = null) {
        editId = id;
        const panel  = document.getElementById('opor-form-panel');
        const titulo = document.getElementById('opor-form-titulo');
        if (titulo) titulo.textContent = id ? 'Editar Oportunidad' : 'Nueva Oportunidad';
        document.getElementById('opor-form')?.reset();
        const cnt = document.getElementById('odesc-count');
        if (cnt) cnt.textContent = '0';
        if (id) {
            try {
                const r = await fetchSeguro(`/api/oportunidades.php?id=${id}`);
                const d = await r.json();
                if (d.ok) rellenarForm(d.data);
            } catch (e) { manejarApiError(e, 'Error al cargar datos'); return; }
        }
        panel?.classList.add('active');
        panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        document.getElementById('opor-form-panel')?.classList.remove('active');
        document.getElementById('opor-form')?.reset();
        editId = null;
    }

    function rellenarForm(o) {
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v ?? ''; };
        set('of-titulo',        o.titulo);
        set('of-valor',         o.valor ?? '');
        set('of-etapa',         o.etapa);
        set('of-fecha-cierre',  o.fecha_cierre_esperada ?? '');
        set('of-descripcion',   o.descripcion ?? '');
        const cnt = document.getElementById('odesc-count');
        if (cnt) cnt.textContent = (o.descripcion ?? '').length;
    }

    async function guardar(e) {
        e.preventDefault();
        const titulo = document.getElementById('of-titulo')?.value.trim() ?? '';
        if (!titulo) {
            mostrarToast('El título es obligatorio', 'error');
            document.getElementById('of-titulo')?.focus();
            return;
        }
        const btn = document.getElementById('opor-guardar-btn');
        if (btn) btn.disabled = true;

        const payload = {
            titulo,
            descripcion:           document.getElementById('of-descripcion')?.value ?? '',
            valor:                 document.getElementById('of-valor')?.value ?? '',
            etapa:                 document.getElementById('of-etapa')?.value ?? 'prospecto',
            fecha_cierre_esperada: document.getElementById('of-fecha-cierre')?.value ?? '',
        };

        const url    = editId ? `/api/oportunidades.php?id=${editId}` : '/api/oportunidades.php';
        const method = editId ? 'PUT' : 'POST';
        try {
            const r = await fetchSeguro(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const d = await r.json();
            if (d.ok) {
                cerrarForm();
                cargar();
                mostrarToast(editId ? 'Oportunidad actualizada' : 'Oportunidad creada', 'success');
            } else {
                mostrarToast(d.error || 'Error al guardar', 'error');
            }
        } catch (e) { manejarApiError(e, 'Error de conexión'); }
        finally { if (btn) btn.disabled = false; }
    }

    async function abrirDetalle(id) {
        detalleId = id;
        const overlay = document.getElementById('opor-detalle-overlay');
        overlay?.classList.add('active');
        document.body.classList.add('detalle-open');

        const cerrarBtn  = document.getElementById('odet-cerrar-btn');
        if (cerrarBtn)  cerrarBtn.onclick  = cerrarDetalle;
        if (overlay)    overlay.onclick    = (e) => { if (e.target === overlay) cerrarDetalle(); };
        const editarBtn  = document.getElementById('odet-editar-btn');
        if (editarBtn)  editarBtn.onclick  = () => { const id = detalleId; cerrarDetalle(); abrirForm(id); };
        const eliminarBtn = document.getElementById('odet-eliminar-btn');
        if (eliminarBtn) eliminarBtn.onclick = () => { const id = detalleId; cerrarDetalle(); eliminar(id); };

        try {
            const r = await fetchSeguro(`/api/oportunidades.php?id=${id}`);
            const d = await r.json();
            if (d.ok) renderDetalle(d.data);
            else mostrarToast(d.error, 'error');
        } catch (e) { manejarApiError(e, 'Error al cargar la oportunidad'); }

        ActividadesWidget.init({ prefix: 'odet', entityType: 'oportunidad', entityId: id });
    }

    function cerrarDetalle() {
        document.getElementById('opor-detalle-overlay')?.classList.remove('active');
        document.body.classList.remove('detalle-open');
        detalleId = null;
    }

    function renderDetalle(o) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
        set('odet-titulo',         o.titulo);
        set('odet-valor',          o.valor !== null ? formatValor(o.valor) : '—');
        set('odet-fecha-cierre',   o.fecha_cierre_esperada ? formatFechaCorta(o.fecha_cierre_esperada) : '—');
        set('odet-contacto',       o.contacto_nombre || '—');
        set('odet-lead',           o.lead_nombre     || '—');
        set('odet-fecha-creacion', formatFecha(o.created_at));

        const badgeEl = document.getElementById('odet-etapa-badge');
        if (badgeEl) {
            const e = ETAPAS[o.etapa] ?? { label: o.etapa, cls: '' };
            badgeEl.innerHTML = `<span class="etapa-badge ${e.cls}">${e.label}</span>`;
        }

        const descBloque = document.getElementById('odet-desc-bloque');
        const descEl     = document.getElementById('odet-descripcion');
        if (descEl) descEl.textContent = o.descripcion || '';
        if (descBloque) descBloque.style.display = o.descripcion ? '' : 'none';

        const btnsContainer = document.getElementById('odet-etapa-btns');
        if (btnsContainer) {
            btnsContainer.innerHTML = ETAPAS_ORDER.map(etapa => {
                const e = ETAPAS[etapa];
                const active = etapa === o.etapa ? ' active' : '';
                return `<button class="opor-etapa-btn${active}" data-etapa="${etapa}">${e.label}</button>`;
            }).join('');
            btnsContainer.querySelectorAll('[data-etapa]').forEach(btn => {
                if (btn.classList.contains('active')) return;
                btn.onclick = () => moverEtapa(detalleId, btn.dataset.etapa);
            });
        }
    }

    function moverEtapa(id, etapa) {
        mostrarConfirm(
            'Cambiar etapa',
            `¿Mover a <strong>${ETAPAS[etapa]?.label ?? etapa}</strong>?`,
            async () => {
                try {
                    const r = await fetchSeguro(`/api/oportunidades.php?id=${id}&action=etapa`, {
                        method:  'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body:    JSON.stringify({ etapa }),
                    });
                    const d = await r.json();
                    if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                    mostrarToast(`Movida a ${ETAPAS[etapa]?.label ?? etapa}`, 'success');
                    renderDetalle(d.data);
                    cargar();
                } catch (e) { manejarApiError(e, 'Error al cambiar etapa'); }
            },
            'Mover',
            'info'
        );
    }

    function eliminar(id) {
        mostrarConfirm(
            '¿Eliminar oportunidad?',
            'Esta acción no se puede deshacer.',
            async () => {
                try {
                    const r = await fetchSeguro(`/api/oportunidades.php?id=${id}`, { method: 'DELETE' });
                    const d = await r.json();
                    if (!d.ok) { mostrarToast(d.error, 'error'); cargar(); return; }
                    mostrarToast('Oportunidad eliminada', 'success');
                    cargar();
                } catch (e) { manejarApiError(e, 'Error al eliminar'); }
            }
        );
    }

    function formatValor(v) {
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(v);
    }

    function formatFechaCorta(d) {
        if (!d) return '—';
        const [y, m, day] = d.split('-');
        return `${day}/${m}/${y}`;
    }

    function formatFecha(ts) {
        if (!ts) return '—';
        return new Date(ts).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function esc(str) {
        const el = document.createElement('div');
        el.textContent = String(str ?? '');
        return el.innerHTML;
    }

    return { init };
})();
