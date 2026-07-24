const Email = (() => {
    let _editId      = null;
    let _buscarTimer = null;
    let _initialized = false;

    let _estado = {
        buscar: '', estado: '',
        orden: 'created_at', dir: 'desc',
        pagina: 1, limite: 20,
    };

    function init() {
        if (_initialized) { cargar(); return; }
        _initialized = true;

        document.getElementById('email-nuevo-btn')
            ?.addEventListener('click', () => abrirFormulario(null));
        document.getElementById('email-cancelar-btn')
            ?.addEventListener('click', cerrarFormulario);
        document.getElementById('email-form')
            ?.addEventListener('submit', guardar);

        document.getElementById('email-buscar')?.addEventListener('input', e => {
            clearTimeout(_buscarTimer);
            _buscarTimer = setTimeout(() => {
                _estado.buscar = e.target.value;
                _estado.pagina = 1;
                actualizarBadge();
                cargar();
            }, 400);
        });

        document.getElementById('email-filtro-estado')?.addEventListener('change', e => {
            _estado.estado = e.target.value;
            _estado.pagina = 1;
            actualizarBadge();
            cargar();
        });

        document.getElementById('email-filtros-toggle')?.addEventListener('click', () => {
            document.getElementById('email-filtros-avanzados')?.classList.toggle('active');
            document.getElementById('email-filtros-toggle')?.classList.toggle('active');
        });

        document.getElementById('email-filtro-orden')?.addEventListener('change', e => {
            _estado.orden  = e.target.value;
            _estado.pagina = 1;
            cargar();
        });

        document.getElementById('email-filtro-dir')?.addEventListener('click', () => {
            const btn  = document.getElementById('email-filtro-dir');
            const nuevo = _estado.dir === 'desc' ? 'asc' : 'desc';
            _estado.dir    = nuevo;
            _estado.pagina = 1;
            if (btn) {
                btn.dataset.dir = nuevo;
                btn.querySelector('i').className = nuevo === 'asc'
                    ? 'fas fa-sort-amount-up'
                    : 'fas fa-sort-amount-down';
            }
            cargar();
        });

        document.getElementById('email-filtros-clear')?.addEventListener('click', () => {
            _estado.buscar = '';
            _estado.estado = '';
            _estado.orden  = 'created_at';
            _estado.dir    = 'desc';
            _estado.pagina = 1;

            const buscar = document.getElementById('email-buscar');
            if (buscar) buscar.value = '';
            const selEstado = document.getElementById('email-filtro-estado');
            if (selEstado) selEstado.value = '';
            const selOrden  = document.getElementById('email-filtro-orden');
            if (selOrden)  selOrden.value  = 'created_at';
            const btnDir = document.getElementById('email-filtro-dir');
            if (btnDir) {
                btnDir.dataset.dir = 'desc';
                btnDir.querySelector('i').className = 'fas fa-sort-amount-down';
            }
            actualizarBadge();
            cargar();
        });

        document.getElementById('ef-notas')?.addEventListener('input', e => {
            const cnt = document.getElementById('email-notas-count');
            if (cnt) cnt.textContent = e.target.value.length;
        });

        cargar();
    }

    async function cargar() {
        const tbody = document.getElementById('email-tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

        const params = new URLSearchParams({
            buscar: _estado.buscar,
            estado: _estado.estado,
            orden:  _estado.orden,
            dir:    _estado.dir,
            pagina: _estado.pagina,
            limite: _estado.limite,
            t:      Date.now(),
        });

        try {
            const r = await fetchSeguro('/api/cuentas_correo.php?' + params);
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            renderTabla(d.data);
            renderPaginacion(d.meta, 'email-paginacion', p => { _estado.pagina = p; cargar(); });
        } catch (e) {
            manejarApiError(e, 'Error al cargar las cuentas de correo');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="crm-empty">Error al cargar los datos</td></tr>';
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('email-tbody');
        if (!tbody) return;

        if (!lista.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="crm-empty"><i class="fas fa-envelope"></i><p>No hay cuentas de correo registradas</p></td></tr>';
            return;
        }

        tbody.innerHTML = lista.map(c => `
            <tr>
                <td>
                    <div class="crm-nombre-cell">
                        <span class="crm-avatar">${c.email.slice(0, 2).toUpperCase()}</span>
                        <strong>${c.email}</strong>
                    </div>
                </td>
                <td>${c.dominio}</td>
                <td>${formatCuota(c.cuota)}</td>
                <td>${badgeEstado(c.estado)}</td>
                <td>${formatFecha(c.created_at)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" data-edit="${c.id_cuenta}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon danger" data-del="${c.id_cuenta}" data-nombre="${c.email}" title="Eliminar">
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
        document.getElementById('email-form-titulo').textContent = id ? 'Editar Cuenta' : 'Nueva Cuenta';
        document.getElementById('email-form-panel')?.classList.add('active');
    }

    function cerrarFormulario() {
        document.getElementById('email-form-panel')?.classList.remove('active');
        limpiarFormulario();
        _editId = null;
    }

    function limpiarFormulario() {
        setVal('ef-email', '');
        setVal('ef-cuota', '500');
        setVal('ef-estado', 'activo');
        setVal('ef-notas', '');
        const cnt = document.getElementById('email-notas-count');
        if (cnt) cnt.textContent = '0';
        document.querySelectorAll('#email-form .form-error').forEach(el => el.textContent = '');
        document.querySelectorAll('#email-form .form-input').forEach(el => {
            el.classList.remove('form-input--error', 'form-input--ok');
        });
    }

    function rellenarFormulario(c) {
        setVal('ef-email',  c.email);
        setVal('ef-cuota',  c.cuota);
        setVal('ef-estado', c.estado);
        setVal('ef-notas',  c.notas ?? '');
        const cnt = document.getElementById('email-notas-count');
        if (cnt) cnt.textContent = (c.notas ?? '').length;
    }

    async function guardar(e) {
        e.preventDefault();

        const email    = document.getElementById('ef-email')?.value.trim().toLowerCase();
        const errEmail = document.getElementById('err-ef-email');
        const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email) {
            if (errEmail) errEmail.textContent = 'El email es obligatorio';
            document.getElementById('ef-email')?.classList.add('form-input--error');
            return;
        }
        if (!EMAIL_RE.test(email)) {
            if (errEmail) errEmail.textContent = 'Formato no válido (ej: usuario@dominio.com)';
            document.getElementById('ef-email')?.classList.add('form-input--error');
            return;
        }
        if (errEmail) errEmail.textContent = '';

        const payload = {
            email:  email,
            cuota:  parseInt(document.getElementById('ef-cuota')?.value) || 0,
            estado: document.getElementById('ef-estado')?.value,
            notas:  document.getElementById('ef-notas')?.value.trim() || null,
        };

        const btn = document.getElementById('email-guardar-btn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; }

        try {
            const url    = _editId ? `/api/cuentas_correo.php?id=${_editId}` : '/api/cuentas_correo.php';
            const method = _editId ? 'PUT' : 'POST';
            const r = await fetchSeguro(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(payload),
            });
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            mostrarToast(_editId ? 'Cuenta actualizada' : 'Cuenta creada', 'success');
            cerrarFormulario();
            cargar();
        } catch (e) {
            manejarApiError(e, 'Error al guardar la cuenta');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar'; }
        }
    }

    async function editar(id) {
        try {
            const params = new URLSearchParams({ pagina: 1, limite: 100, t: Date.now() });
            const r = await fetchSeguro('/api/cuentas_correo.php?' + params);
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            const cuenta = d.data.find(x => x.id_cuenta == id);
            if (cuenta) { abrirFormulario(id); rellenarFormulario(cuenta); }
        } catch (e) {
            manejarApiError(e, 'Error al cargar la cuenta');
        }
    }

    async function eliminar(id, nombre) {
        mostrarConfirm(
            'Eliminar cuenta',
            `¿Eliminar <strong>${nombre}</strong>? Esta acción no se puede deshacer.`,
            async () => {
                try {
                    const r = await fetchSeguro(`/api/cuentas_correo.php?id=${id}`, { method: 'DELETE' });
                    const d = await r.json();
                    if (!d.ok) throw new Error(d.error);
                    mostrarToast('Cuenta eliminada', 'success');
                    cargar();
                } catch (e) {
                    manejarApiError(e, 'Error al eliminar la cuenta');
                }
            },
            'Eliminar',
            'danger'
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    function actualizarBadge() {
        const activos = [_estado.estado, _estado.buscar].filter(v => v !== '').length;
        const badge   = document.getElementById('email-filtros-badge');
        if (!badge) return;
        if (activos > 0) { badge.textContent = activos; badge.style.display = ''; }
        else               badge.style.display = 'none';
    }

    function badgeEstado(estado) {
        const label = { activo: 'Activo', suspendido: 'Suspendido' }[estado] ?? estado;
        return `<span class="status-badge status-badge--${estado}">${label}</span>`;
    }

    function formatCuota(cuota) {
        const mb = parseInt(cuota);
        if (!mb || mb === 0) return '<span style="color:#7c3aed">Sin límite</span>';
        if (mb >= 1024) return (mb / 1024).toFixed(1) + ' GB';
        return mb + ' MB';
    }

    function formatFecha(fecha) {
        if (!fecha) return '—';
        return new Date(fecha).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function setVal(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val;
    }

    return { init };
})();
