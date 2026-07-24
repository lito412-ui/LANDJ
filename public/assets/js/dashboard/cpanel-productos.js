const Productos = (() => {
    let editId = null;
    let buscarTimer = null;
    let initDone = false;
    let estado = { buscar: '', activo: '', orden: 'created_at', dir: 'desc', pagina: 1, limite: 20 };

    function init() {
        if (initDone) { cargar(); return; }
        initDone = true;

        document.getElementById('productos-nuevo-btn')?.addEventListener('click', () => abrirForm());
        document.getElementById('productos-cancelar-btn')?.addEventListener('click', cerrarForm);
        document.getElementById('productos-form')?.addEventListener('submit', guardar);
        document.getElementById('prod-descripcion')?.addEventListener('input', actualizarContador);

        document.getElementById('productos-buscar')?.addEventListener('input', (e) => {
            clearTimeout(buscarTimer);
            estado.buscar = e.target.value.trim();
            estado.pagina = 1;
            buscarTimer = setTimeout(cargar, 350);
        });

        document.getElementById('productos-filtro-activo')?.addEventListener('change', (e) => {
            estado.activo = e.target.value;
            estado.pagina = 1;
            actualizarBadge();
            cargar();
        });

        document.getElementById('prod-filtros-toggle')?.addEventListener('click', () => {
            document.getElementById('prod-filtros-avanzados')?.classList.toggle('active');
        });

        document.getElementById('prod-filtro-orden')?.addEventListener('change', (e) => {
            estado.orden = e.target.value;
            estado.pagina = 1;
            cargar();
        });

        document.getElementById('prod-filtro-dir')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            estado.dir = estado.dir === 'desc' ? 'asc' : 'desc';
            estado.pagina = 1;
            btn.dataset.dir = estado.dir;
            btn.querySelector('i').className = estado.dir === 'asc'
                ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down';
            cargar();
        });

        document.getElementById('prod-filtros-clear')?.addEventListener('click', limpiarFiltros);
        cargar();
    }

    function actualizarBadge() {
        const badge = document.getElementById('prod-filtros-badge');
        const n = estado.activo !== '' ? 1 : 0;
        if (badge) {
            badge.textContent = n;
            badge.style.display = n > 0 ? '' : 'none';
        }
    }

    function limpiarFiltros() {
        estado = { buscar: estado.buscar, activo: '', orden: 'created_at', dir: 'desc', pagina: 1, limite: 20 };
        const activo = document.getElementById('productos-filtro-activo');
        if (activo) activo.value = '';
        const orden = document.getElementById('prod-filtro-orden');
        if (orden) orden.value = 'created_at';
        const dir = document.getElementById('prod-filtro-dir');
        if (dir) {
            dir.dataset.dir = 'desc';
            dir.querySelector('i').className = 'fas fa-sort-amount-down';
        }
        actualizarBadge();
        cargar();
    }

    async function cargar() {
        const params = new URLSearchParams();
        Object.entries(estado).forEach(([k, v]) => { if (v !== '' && v !== 0) params.set(k, v); });
        try {
            const r = await fetchSeguro('/api/productos.php' + (params.size ? '?' + params : ''));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            renderTabla(d.data);
            renderPaginacion(d.meta, 'prod-paginacion', (p) => { estado.pagina = p; cargar(); });
        } catch (e) {
            manejarApiError(e, 'Error al cargar productos');
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('productos-tbody');
        if (!tbody) return;
        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="crm-empty">
                    <i class="fas fa-box-open"></i>
                    <p>No hay productos.
                        <button class="btn-link" id="prod-crear-primero">Crear el primero</button>
                    </p>
                </td></tr>`;
            tbody.querySelector('#prod-crear-primero')?.addEventListener('click', () => abrirForm());
            return;
        }

        tbody.innerHTML = lista.map(p => `
            <tr>
                <td>
                    <div class="crm-nombre-cell">
                        <span class="crm-avatar producto-av">${esc(p.nombre).slice(0,2).toUpperCase()}</span>
                        <div>
                            <strong>${esc(p.nombre)}</strong>
                            <small>${esc(p.descripcion || '')}</small>
                        </div>
                    </div>
                </td>
                <td>${esc(p.codigo || '-')}</td>
                <td><strong>${money(p.precio)}</strong></td>
                <td>${num(p.iva_porcentaje)}%</td>
                <td>${num(p.stock)}</td>
                <td>${badgeEstado(p.activo)}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" data-edit="${p.id_producto}" title="Editar"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon danger" data-del="${p.id_producto}" data-nombre="${escAttr(p.nombre)}" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>`).join('');

        tbody.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => editar(btn.dataset.edit)));
        tbody.querySelectorAll('[data-del]').forEach(btn => btn.addEventListener('click', () => eliminar(btn.dataset.del, btn.dataset.nombre)));
    }

    function abrirForm(data = null) {
        editId = data?.id_producto ?? null;
        document.getElementById('productos-form-titulo').textContent = editId ? `Editar ${data.nombre}` : 'Nuevo Producto';
        document.getElementById('productos-form-panel')?.classList.add('active');
        document.getElementById('productos-form')?.reset();
        limpiarErrores();
        setVal('prod-codigo', data?.codigo ?? '');
        setVal('prod-nombre', data?.nombre ?? '');
        setVal('prod-precio', num(data?.precio ?? 0));
        setVal('prod-iva', num(data?.iva_porcentaje ?? 21));
        setVal('prod-stock', num(data?.stock ?? 0));
        setVal('prod-descripcion', data?.descripcion ?? '');
        const activo = document.getElementById('prod-activo');
        if (activo) activo.checked = data ? String(data.activo) === '1' : true;
        actualizarContador();
        document.getElementById('productos-form-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        editId = null;
        document.getElementById('productos-form-panel')?.classList.remove('active');
        document.getElementById('productos-form')?.reset();
        limpiarErrores();
        actualizarContador();
    }

    async function editar(id) {
        try {
            const r = await fetchSeguro('/api/productos.php?id=' + encodeURIComponent(id));
            const d = await r.json();
            d.ok ? abrirForm(d.data) : mostrarToast(d.error, 'error');
        } catch (e) {
            manejarApiError(e, 'Error al cargar producto');
        }
    }

    async function guardar(e) {
        e.preventDefault();
        limpiarErrores();
        const payload = {
            codigo: val('prod-codigo'),
            nombre: val('prod-nombre'),
            precio: val('prod-precio'),
            iva_porcentaje: val('prod-iva'),
            stock: val('prod-stock'),
            activo: document.getElementById('prod-activo')?.checked ? 1 : 0,
            descripcion: val('prod-descripcion'),
        };
        if (!validar(payload)) return;

        const btn = document.getElementById('productos-guardar-btn');
        if (btn) btn.disabled = true;
        try {
            const url = '/api/productos.php' + (editId ? '?id=' + encodeURIComponent(editId) : '');
            const r = await fetchSeguro(url, {
                method: editId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast(editId ? 'Producto actualizado' : 'Producto creado', 'success');
            cerrarForm();
            cargar();
        } catch (err) {
            manejarApiError(err, 'Error al guardar producto');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function validar(payload) {
        let ok = true;
        if (payload.codigo && !/^[A-Za-z0-9._-]+$/.test(payload.codigo)) {
            error('err-prod-codigo', 'Solo letras, numeros, puntos, guiones y barra baja'); ok = false;
        }
        if (!payload.nombre) { error('err-prod-nombre', 'El nombre es obligatorio'); ok = false; }
        if (Number(payload.precio) < 0) { error('err-prod-precio', 'El precio no puede ser negativo'); ok = false; }
        if (Number(payload.iva_porcentaje) < 0 || Number(payload.iva_porcentaje) > 100) {
            error('err-prod-iva', 'El IVA debe estar entre 0 y 100'); ok = false;
        }
        if (Number(payload.stock) < 0) { error('err-prod-stock', 'El stock no puede ser negativo'); ok = false; }
        return ok;
    }

    function eliminar(id, nombre) {
        mostrarConfirm('Eliminar producto', `Se eliminara ${nombre}. Esta accion no se puede deshacer.`, async () => {
            try {
                const r = await fetchSeguro('/api/productos.php?id=' + encodeURIComponent(id), { method: 'DELETE' });
                const d = await r.json();
                if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                mostrarToast('Producto eliminado', 'success');
                cargar();
            } catch (e) {
                manejarApiError(e, 'Error al eliminar producto');
            }
        });
    }

    function badgeEstado(activo) {
        return String(activo) === '1'
            ? '<span class="producto-badge producto-activo">Activo</span>'
            : '<span class="producto-badge producto-inactivo">Inactivo</span>';
    }

    function actualizarContador() {
        const el = document.getElementById('prod-descripcion');
        const cnt = document.getElementById('prod-desc-count');
        if (cnt) cnt.textContent = String(el?.value.length ?? 0);
    }

    function money(v) {
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(parseFloat(v || 0));
    }
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    }
    function escAttr(v) { return esc(v).replace(/`/g, '&#096;'); }
    function num(v) { return Number.parseFloat(v || 0).toFixed(2); }
    function val(id) { return document.getElementById(id)?.value.trim() ?? ''; }
    function setVal(id, v) { const el = document.getElementById(id); if (el) el.value = v ?? ''; }
    function error(id, msg) { const el = document.getElementById(id); if (el) el.textContent = msg; }
    function limpiarErrores() { document.querySelectorAll('#productos-form .form-error').forEach(el => { el.textContent = ''; }); }

    return { init };
})();
