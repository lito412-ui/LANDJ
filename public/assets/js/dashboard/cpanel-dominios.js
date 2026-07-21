const Dominios = (() => {
    let _editId     = null;
    let _buscarTimer = null;
    let _initialized = false;

    let _estado = {
        buscar: '', tipo: '', estado: '',
        orden: 'created_at', dir: 'desc',
        pagina: 1, limite: 20,
    };

    function init() {
        if (_initialized) { cargar(); return; }
        _initialized = true;

        document.getElementById('dom-nuevo-btn')
            ?.addEventListener('click', () => abrirFormulario(null));
        document.getElementById('dom-cancelar-btn')
            ?.addEventListener('click', cerrarFormulario);
        document.getElementById('dom-form')
            ?.addEventListener('submit', guardar);

        document.getElementById('dom-buscar')?.addEventListener('input', e => {
            clearTimeout(_buscarTimer);
            _buscarTimer = setTimeout(() => {
                _estado.buscar = e.target.value;
                _estado.pagina = 1;
                actualizarBadge();
                cargar();
            }, 400);
        });

        document.getElementById('dom-filtro-tipo')?.addEventListener('change', e => {
            _estado.tipo   = e.target.value;
            _estado.pagina = 1;
            actualizarBadge();
            cargar();
        });
        document.getElementById('dom-filtro-estado')?.addEventListener('change', e => {
            _estado.estado = e.target.value;
            _estado.pagina = 1;
            actualizarBadge();
            cargar();
        });

        document.getElementById('dom-filtros-toggle')?.addEventListener('click', () => {
            const panel = document.getElementById('dom-filtros-avanzados');
            const btn   = document.getElementById('dom-filtros-toggle');
            panel?.classList.toggle('active');
            btn?.classList.toggle('active');
        });

        document.getElementById('dom-filtro-orden')?.addEventListener('change', e => {
            _estado.orden  = e.target.value;
            _estado.pagina = 1;
            cargar();
        });

        document.getElementById('dom-filtro-dir')?.addEventListener('click', () => {
            const btn = document.getElementById('dom-filtro-dir');
            const nuevo = _estado.dir === 'desc' ? 'asc' : 'desc';
            _estado.dir  = nuevo;
            _estado.pagina = 1;
            if (btn) {
                btn.dataset.dir = nuevo;
                btn.querySelector('i').className = nuevo === 'asc'
                    ? 'fas fa-sort-amount-up'
                    : 'fas fa-sort-amount-down';
            }
            cargar();
        });

        document.getElementById('dom-filtros-clear')?.addEventListener('click', () => {
            _estado.buscar = '';
            _estado.tipo   = '';
            _estado.estado = '';
            _estado.orden  = 'created_at';
            _estado.dir    = 'desc';
            _estado.pagina = 1;

            const buscar = document.getElementById('dom-buscar');
            if (buscar) buscar.value = '';
            const selTipo   = document.getElementById('dom-filtro-tipo');
            if (selTipo)   selTipo.value   = '';
            const selEstado = document.getElementById('dom-filtro-estado');
            if (selEstado) selEstado.value = '';
            const selOrden  = document.getElementById('dom-filtro-orden');
            if (selOrden)  selOrden.value  = 'created_at';
            const btnDir = document.getElementById('dom-filtro-dir');
            if (btnDir) {
                btnDir.dataset.dir = 'desc';
                btnDir.querySelector('i').className = 'fas fa-sort-amount-down';
            }
            actualizarBadge();
            cargar();
        });

        document.getElementById('df-notas')?.addEventListener('input', e => {
            const cnt = document.getElementById('dom-notas-count');
            if (cnt) cnt.textContent = e.target.value.length;
        });

        cargar();
    }

    async function cargar() {
        const tbody = document.getElementById('dom-tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

        const params = new URLSearchParams({
            buscar: _estado.buscar,
            tipo:   _estado.tipo,
            estado: _estado.estado,
            orden:  _estado.orden,
            dir:    _estado.dir,
            pagina: _estado.pagina,
            limite: _estado.limite,
            t:      Date.now(),
        });

        try {
            const r = await fetchSeguro('/api/dominios.php?' + params);
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            renderTabla(d.data);
            renderPaginacion(d.meta, 'dom-paginacion', p => { _estado.pagina = p; cargar(); });
        } catch (e) {
            manejarApiError(e, 'Error al cargar los dominios');
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="crm-empty">Error al cargar los datos</td></tr>';
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('dom-tbody');
        if (!tbody) return;

        if (!lista.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="crm-empty"><i class="fas fa-globe"></i><p>No hay dominios registrados</p></td></tr>';
            return;
        }

        tbody.innerHTML = lista.map(d => `
            <tr>
                <td><strong>${d.dominio}</strong></td>
                <td>${badgeTipo(d.tipo)}</td>
                <td>${badgeEstado(d.estado)}</td>
                <td>${d.ip ?? '<span style="color:#94a3b8">—</span>'}</td>
                <td>${badgeSSL(d.ssl)}</td>
                <td>${formatFecha(d.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" data-edit="${d.id_dominio}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon danger" data-del="${d.id_dominio}" data-nombre="${d.dominio}" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('');

        tbody.querySelectorAll('[data-edit]').forEach(btn =>
            btn.addEventListener('click', () => editar(parseInt(btn.dataset.edit))));
        tbody.querySelectorAll('[data-del]').forEach(btn =>
            btn.addEventListener('click', () => eliminar(parseInt(btn.dataset.del), btn.dataset.nombre)));
    }

    function abrirFormulario(id) {
        _editId = id;
        limpiarFormulario();
        document.getElementById('dom-form-titulo').textContent = id ? 'Editar Dominio' : 'Nuevo Dominio';
        document.getElementById('dom-form-panel')?.classList.add('active');

        if (id) cargarParaEditar(id);
    }

    async function cargarParaEditar(id) {
        try {
            const r = await fetchSeguro(`/api/dominios.php?id=${id}`);
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            // si no hay endpoint GET por ID, buscamos en la lista cargada
        } catch (_) { /* silencio */ }
    }

    function rellenarFormulario(d) {
        setVal('df-dominio', d.dominio);
        setVal('df-tipo',    d.tipo);
        setVal('df-estado',  d.estado);
        setVal('df-ip',      d.ip ?? '');
        setVal('df-notas',   d.notas ?? '');
        const ssl = document.getElementById('df-ssl');
        if (ssl) ssl.checked = !!parseInt(d.ssl);
        const cnt = document.getElementById('dom-notas-count');
        if (cnt) cnt.textContent = (d.notas ?? '').length;
    }

    function cerrarFormulario() {
        document.getElementById('dom-form-panel')?.classList.remove('active');
        limpiarFormulario();
        _editId = null;
    }

    function limpiarFormulario() {
        ['df-dominio','df-ip','df-notas'].forEach(id => setVal(id, ''));
        setVal('df-tipo',   'principal');
        setVal('df-estado', 'pendiente');
        const ssl = document.getElementById('df-ssl');
        if (ssl) ssl.checked = false;
        const cnt = document.getElementById('dom-notas-count');
        if (cnt) cnt.textContent = '0';
        document.querySelectorAll('#dom-form .form-error').forEach(el => el.textContent = '');
        document.querySelectorAll('#dom-form .form-input').forEach(el => {
            el.classList.remove('form-input--error', 'form-input--ok');
        });
    }

    async function guardar(e) {
        e.preventDefault();

        const dominio = document.getElementById('df-dominio')?.value.trim();
        const errSpan = document.getElementById('err-dom-dominio');
        const DOMAIN_RE = /^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/;

        if (!dominio) {
            if (errSpan) errSpan.textContent = 'El dominio es obligatorio';
            document.getElementById('df-dominio')?.classList.add('form-input--error');
            return;
        }
        if (!DOMAIN_RE.test(dominio)) {
            if (errSpan) errSpan.textContent = 'Formato no válido (ej: ejemplo.com)';
            document.getElementById('df-dominio')?.classList.add('form-input--error');
            return;
        }
        if (errSpan) errSpan.textContent = '';

        const ip = document.getElementById('df-ip')?.value.trim();
        const IP_RE = /^(\d{1,3}\.){3}\d{1,3}$|^([0-9a-fA-F:]+)$/;
        const errIp = document.getElementById('err-dom-ip');
        if (ip && !IP_RE.test(ip)) {
            if (errIp) errIp.textContent = 'Formato de IP no válido';
            document.getElementById('df-ip')?.classList.add('form-input--error');
            return;
        }
        if (errIp) errIp.textContent = '';

        const payload = {
            dominio: dominio,
            tipo:    document.getElementById('df-tipo')?.value,
            estado:  document.getElementById('df-estado')?.value,
            ip:      ip || null,
            ssl:     document.getElementById('df-ssl')?.checked ? 1 : 0,
            notas:   document.getElementById('df-notas')?.value.trim() || null,
        };

        const btn = document.getElementById('dom-guardar-btn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; }

        try {
            const url    = _editId ? `/api/dominios.php?id=${_editId}` : '/api/dominios.php';
            const method = _editId ? 'PUT' : 'POST';
            const r = await fetchSeguro(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            mostrarToast(_editId ? 'Dominio actualizado' : 'Dominio creado', 'success');
            cerrarFormulario();
            cargar();
        } catch (e) {
            manejarApiError(e, 'Error al guardar el dominio');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar'; }
        }
    }

    async function editar(id) {
        // Obtenemos los datos de la fila ya cargada en tabla
        try {
            const params = new URLSearchParams({ pagina: 1, limite: 100, t: Date.now() });
            const r = await fetchSeguro('/api/dominios.php?' + params);
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            const dom = d.data.find(x => x.id_dominio == id);
            if (dom) { abrirFormulario(id); rellenarFormulario(dom); }
        } catch (e) {
            manejarApiError(e, 'Error al cargar el dominio');
        }
    }

    async function eliminar(id, nombre) {
        mostrarConfirm(
            'Eliminar dominio',
            `¿Eliminar <strong>${nombre}</strong>? Esta acción no se puede deshacer.`,
            async () => {
                try {
                    const r = await fetchSeguro(`/api/dominios.php?id=${id}`, { method: 'DELETE' });
                    const d = await r.json();
                    if (!d.ok) throw new Error(d.error);
                    mostrarToast('Dominio eliminado', 'success');
                    cargar();
                } catch (e) {
                    manejarApiError(e, 'Error al eliminar el dominio');
                }
            },
            'Eliminar',
            'danger'
        );
    }

    function actualizarBadge() {
        const activos = [_estado.tipo, _estado.estado, _estado.buscar].filter(v => v !== '').length;
        const badge   = document.getElementById('dom-filtros-badge');
        if (!badge) return;
        if (activos > 0) { badge.textContent = activos; badge.style.display = ''; }
        else               badge.style.display = 'none';
    }

    // ─── Helpers de presentación ──────────────────────────────────────────────

    function badgeTipo(tipo) {
        const label = { principal:'Principal', subdominio:'Subdominio', addon:'Addon', parked:'Parked' }[tipo] ?? tipo;
        return `<span class="status-badge status-badge--${tipo}">${label}</span>`;
    }

    function badgeEstado(estado) {
        const label = { activo:'Activo', pendiente:'Pendiente', suspendido:'Suspendido' }[estado] ?? estado;
        return `<span class="status-badge status-badge--${estado}">${label}</span>`;
    }

    function badgeSSL(ssl) {
        return parseInt(ssl)
            ? '<span class="ssl-on"><i class="fas fa-lock"></i> Sí</span>'
            : '<span class="ssl-off"><i class="fas fa-lock-open"></i> No</span>';
    }

    function formatFecha(fecha) {
        if (!fecha) return '—';
        return new Date(fecha).toLocaleDateString('es-ES', { day:'2-digit', month:'short', year:'numeric' });
    }

    function setVal(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val;
    }

    return { init };
})();
