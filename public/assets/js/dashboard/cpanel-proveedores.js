const Proveedores = (() => {
    let editId = null;
    let detalleId = null;
    let buscarTimer = null;
    let initDone = false;

    let _estado = { buscar: '', activo: '', orden: 'nombre', dir: 'asc', pagina: 1, limite: 20 };

    function init() {
        if (initDone) { cargar(); return; }
        initDone = true;

        const overlay = document.getElementById('proveedor-detalle-overlay');
        if (overlay) document.body.appendChild(overlay);

        document.getElementById('proveedores-nuevo-btn')?.addEventListener('click', () => abrirForm());
        document.getElementById('proveedores-cancelar-btn')?.addEventListener('click', cerrarForm);
        document.getElementById('proveedores-form')?.addEventListener('submit', guardar);

        document.getElementById('proveedores-buscar')?.addEventListener('input', (e) => {
            clearTimeout(buscarTimer);
            _estado.buscar = e.target.value.trim();
            _estado.pagina = 1;
            buscarTimer = setTimeout(cargar, 400);
        });

        document.getElementById('pv-filtro-activo')?.addEventListener('change', (e) => {
            _estado.activo = e.target.value;
            _estado.pagina = 1;
            cargar();
        });

        document.getElementById('proveedores-exportar-btn')?.addEventListener('click', () => {
            window.open('/api/proveedores.php?action=exportar', '_blank');
        });
        document.getElementById('proveedores-importar-btn')?.addEventListener('click', () => {
            document.getElementById('proveedores-importar-input')?.click();
        });
        document.getElementById('proveedores-importar-input')?.addEventListener('change', async (e) => {
            const file = e.target.files?.[0];
            e.target.value = '';
            if (!file) return;
            try {
                const d = await importarCsvArchivo('/api/proveedores.php?action=importar', file);
                mostrarResultadoImportacion(d);
                cargar();
            } catch (err) {
                manejarApiError(err, 'Error al importar proveedores');
            }
        });

        cargar();
    }

    async function cargar() {
        const params = new URLSearchParams();
        Object.entries(_estado).forEach(([k, v]) => { if (v !== '' && v !== 0) params.set(k, v); });
        try {
            const r = await fetchSeguro('/api/proveedores.php' + (params.size ? '?' + params : ''));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            renderTabla(d.data);
            renderPaginacion(d.meta, 'pv-paginacion', (p) => { _estado.pagina = p; cargar(); });
        } catch (e) {
            manejarApiError(e, 'Error al cargar proveedores');
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('proveedores-tbody');
        if (!tbody) return;
        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="6" class="crm-empty">
                    <i class="fas fa-truck"></i>
                    <p>No hay proveedores.
                        <button class="btn-link" id="pv-crear-primero">Crear el primero</button>
                    </p>
                </td></tr>`;
            tbody.querySelector('#pv-crear-primero')?.addEventListener('click', () => abrirForm());
            return;
        }

        tbody.innerHTML = lista.map(p => `
            <tr>
                <td>
                    <div class="crm-nombre-cell">
                        <span class="crm-avatar">${esc(p.nombre).slice(0, 2).toUpperCase()}</span>
                        <div><strong>${esc(p.nombre)}</strong></div>
                    </div>
                </td>
                <td>${esc(p.nif || '—')}</td>
                <td>${esc(p.email || '—')}</td>
                <td>${esc(p.telefono || '—')}</td>
                <td><span class="factura-badge ${p.activo ? 'factura-pagada' : 'factura-cancelada'}">${p.activo ? 'Activo' : 'Inactivo'}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" data-ver="${p.id_proveedor}" title="Ver detalle"><i class="fas fa-eye"></i></button>
                        <button class="btn-icon" data-edit="${p.id_proveedor}" title="Editar"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon danger" data-del="${p.id_proveedor}" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>`).join('');

        tbody.querySelectorAll('[data-ver]').forEach(btn => btn.addEventListener('click', () => abrirDetalle(parseInt(btn.dataset.ver))));
        tbody.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => abrirForm(parseInt(btn.dataset.edit))));
        tbody.querySelectorAll('[data-del]').forEach(btn => btn.addEventListener('click', () => eliminar(parseInt(btn.dataset.del))));
    }

    async function abrirForm(id = null) {
        editId = id;
        const panel = document.getElementById('proveedores-form-panel');
        const titulo = document.getElementById('proveedores-form-titulo');
        if (titulo) titulo.textContent = id ? 'Editar Proveedor' : 'Nuevo Proveedor';
        limpiarForm();
        if (id) {
            try {
                const r = await fetchSeguro(`/api/proveedores.php?id=${id}`);
                const d = await r.json();
                if (d.ok) rellenarForm(d.data);
                else { mostrarToast(d.error, 'error'); return; }
            } catch (e) { manejarApiError(e, 'Error al cargar datos'); return; }
        }
        cerrarDetalle();
        panel?.classList.add('active');
        panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        document.getElementById('proveedores-form-panel')?.classList.remove('active');
        limpiarForm();
        editId = null;
    }

    function limpiarForm() {
        document.getElementById('proveedores-form')?.reset();
        document.querySelectorAll('#proveedores-form .form-error').forEach(el => { el.textContent = ''; });
        setVal('pv-activo', '1');
    }

    function rellenarForm(p) {
        setVal('pv-nombre', p.nombre);
        setVal('pv-nif', p.nif);
        setVal('pv-email', p.email);
        setVal('pv-telefono', p.telefono);
        setVal('pv-direccion', p.direccion);
        setVal('pv-contacto-referencia', p.contacto_referencia);
        setVal('pv-notas', p.notas);
        setVal('pv-activo', p.activo ? '1' : '0');
    }

    async function guardar(e) {
        e.preventDefault();
        limpiarErrores();
        const payload = {
            nombre: document.getElementById('pv-nombre')?.value.trim(),
            nif: document.getElementById('pv-nif')?.value.trim(),
            email: document.getElementById('pv-email')?.value.trim(),
            telefono: document.getElementById('pv-telefono')?.value.trim(),
            direccion: document.getElementById('pv-direccion')?.value.trim(),
            contacto_referencia: document.getElementById('pv-contacto-referencia')?.value.trim(),
            notas: document.getElementById('pv-notas')?.value.trim(),
            activo: document.getElementById('pv-activo')?.value === '1',
        };
        if (!payload.nombre) { error('err-pv-nombre', 'El nombre es obligatorio'); return; }

        const btn = document.getElementById('proveedores-guardar-btn');
        if (btn) btn.disabled = true;
        try {
            const url = editId ? `/api/proveedores.php?id=${editId}` : '/api/proveedores.php';
            const r = await fetchSeguro(url, {
                method: editId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast(editId ? 'Proveedor actualizado' : 'Proveedor creado', 'success');
            cerrarForm();
            cargar();
        } catch (err) {
            manejarApiError(err, 'Error al guardar proveedor');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function eliminar(id) {
        mostrarConfirm('¿Eliminar este proveedor?', 'Esta acción no se puede deshacer. Los productos vinculados quedarán sin proveedor asignado.', async () => {
            try {
                const r = await fetchSeguro(`/api/proveedores.php?id=${id}`, { method: 'DELETE' });
                const d = await r.json();
                if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                mostrarToast('Proveedor eliminado', 'success');
                cerrarDetalle();
                cargar();
            } catch (e) {
                manejarApiError(e, 'Error al eliminar proveedor');
            }
        });
    }

    async function abrirDetalle(id) {
        detalleId = id;
        const overlay = document.getElementById('proveedor-detalle-overlay');
        overlay?.classList.add('active');
        document.body.classList.add('detalle-open');

        const cerrarBtn = document.getElementById('pv-det-cerrar-btn');
        if (cerrarBtn) cerrarBtn.onclick = cerrarDetalle;
        if (overlay) overlay.onclick = (e) => { if (e.target === overlay) cerrarDetalle(); };
        const editarBtn = document.getElementById('pv-det-editar-btn');
        if (editarBtn) editarBtn.onclick = () => { const i = detalleId; cerrarDetalle(); abrirForm(i); };
        const eliminarBtn = document.getElementById('pv-det-eliminar-btn');
        if (eliminarBtn) eliminarBtn.onclick = () => { const i = detalleId; cerrarDetalle(); eliminar(i); };

        try {
            const r = await fetchSeguro(`/api/proveedores.php?id=${id}`);
            const d = await r.json();
            if (d.ok) renderDetalle(d.data);
            else mostrarToast(d.error, 'error');
        } catch (e) {
            manejarApiError(e, 'Error al cargar el proveedor');
        }
    }

    function cerrarDetalle() {
        document.getElementById('proveedor-detalle-overlay')?.classList.remove('active');
        document.body.classList.remove('detalle-open');
        detalleId = null;
    }

    function renderDetalle(p) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
        set('pv-det-avatar', esc(p.nombre).slice(0, 2).toUpperCase());
        set('pv-det-nombre', p.nombre);
        set('pv-det-nif', p.nif || '');
        set('pv-det-email', p.email);
        set('pv-det-telefono', p.telefono);
        set('pv-det-direccion', p.direccion);
        set('pv-det-contacto-referencia', p.contacto_referencia);
        set('pv-det-estado', p.activo ? 'Activo' : 'Inactivo');
        set('pv-det-fecha', formatFecha(p.created_at));

        const notasBloque = document.getElementById('pv-det-notas-bloque');
        const notasEl = document.getElementById('pv-det-notas');
        if (notasEl) notasEl.textContent = p.notas || '';
        if (notasBloque) notasBloque.style.display = p.notas ? '' : 'none';
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

    function setVal(id, v) { const el = document.getElementById(id); if (el) el.value = v ?? ''; }
    function error(id, msg) { const el = document.getElementById(id); if (el) el.textContent = msg; }
    function limpiarErrores() { document.querySelectorAll('#proveedores-form .form-error').forEach(el => { el.textContent = ''; }); }

    return { init };
})();
