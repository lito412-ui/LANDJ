const Recurrentes = (() => {
    let editId = null;
    let detalleActual = null;
    let buscarTimer = null;
    let contactosCache = [];
    let productosCache = [];
    let initDone = false;
    let estadoFiltro = { buscar: '', activa: '', pagina: 1, limite: 20 };

    const periodicidadLabel = { mensual: 'Mensual', trimestral: 'Trimestral', anual: 'Anual' };

    function init() {
        if (initDone) { cargar(); return; }
        initDone = true;

        const overlay = document.getElementById('recurrente-detalle-overlay');
        if (overlay) document.body.appendChild(overlay);

        document.getElementById('recurrentes-nueva-btn')?.addEventListener('click', () => abrirForm());
        document.getElementById('recurrentes-cancelar-btn')?.addEventListener('click', cerrarForm);
        document.getElementById('recurrentes-form')?.addEventListener('submit', guardar);
        document.getElementById('rec-linea-add')?.addEventListener('click', () => addLinea());

        document.getElementById('rec-det-cerrar')?.addEventListener('click', cerrarDetalle);
        document.getElementById('rec-det-editar')?.addEventListener('click', () => {
            if (detalleActual) abrirForm(detalleActual);
        });
        document.getElementById('rec-det-eliminar')?.addEventListener('click', () => {
            if (detalleActual) confirmarEliminar(detalleActual.id_recurrente, detalleActual.nombre);
        });
        document.getElementById('rec-det-generar')?.addEventListener('click', () => {
            if (detalleActual) generarAhora(detalleActual.id_recurrente);
        });

        document.getElementById('recurrentes-buscar')?.addEventListener('input', (e) => {
            clearTimeout(buscarTimer);
            estadoFiltro.buscar = e.target.value.trim();
            estadoFiltro.pagina = 1;
            buscarTimer = setTimeout(cargar, 400);
        });
        document.getElementById('rec-filtro-activa')?.addEventListener('change', (e) => {
            estadoFiltro.activa = e.target.value;
            estadoFiltro.pagina = 1;
            cargar();
        });

        cargarContactos();
        cargarProductos();
        cargar();
    }

    async function cargarContactos() {
        try {
            const r = await fetchSeguro('/api/contactos.php?limite=100&orden=nombre&dir=asc');
            const d = await r.json();
            contactosCache = d.ok ? d.data : [];
            renderContactosSelect();
        } catch (e) { manejarApiError(e, 'Error al cargar contactos'); }
    }

    function renderContactosSelect(selected = '') {
        const select = document.getElementById('rec-contacto');
        if (!select) return;
        select.innerHTML = '<option value="">Selecciona un contacto...</option>' + contactosCache.map(c => {
            const nombre = `${c.nombre || ''} ${c.apellidos || ''}`.trim();
            const extra = c.empresa ? ` - ${c.empresa}` : '';
            return `<option value="${c.id_contacto}" ${String(selected) === String(c.id_contacto) ? 'selected' : ''}>${esc(nombre + extra)}</option>`;
        }).join('');
    }

    async function cargarProductos() {
        try {
            const r = await fetchSeguro('/api/productos.php?activo=1&limite=100&orden=nombre&dir=asc');
            const d = await r.json();
            productosCache = d.ok ? d.data : [];
        } catch (e) { manejarApiError(e, 'Error al cargar productos'); }
    }

    async function cargar() {
        const params = new URLSearchParams();
        Object.entries(estadoFiltro).forEach(([k, v]) => { if (v !== '' && v !== 0) params.set(k, v); });
        try {
            const r = await fetchSeguro('/api/facturas_recurrentes.php' + (params.size ? '?' + params : ''));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            renderTabla(d.data);
            renderPaginacion(d.meta, 'rec-paginacion', (p) => { estadoFiltro.pagina = p; cargar(); });
        } catch (e) { manejarApiError(e, 'Error al cargar plantillas recurrentes'); }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('recurrentes-tbody');
        if (!tbody) return;
        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="crm-empty">
                    <i class="fas fa-rotate"></i>
                    <p>No hay plantillas recurrentes. <button class="btn-link" id="rec-crear-primero">Crear la primera</button></p>
                </td></tr>`;
            tbody.querySelector('#rec-crear-primero')?.addEventListener('click', () => abrirForm());
            return;
        }

        tbody.innerHTML = lista.map(r => {
            const contacto = `${r.contacto_nombre || ''} ${r.contacto_apellidos || ''}`.trim() || 'Contacto';
            return `
                <tr>
                    <td><strong>${esc(r.nombre)}</strong></td>
                    <td>${esc(contacto)}${r.contacto_empresa ? ` <small>(${esc(r.contacto_empresa)})</small>` : ''}</td>
                    <td>${periodicidadLabel[r.periodicidad] || r.periodicidad}</td>
                    <td>${formatFecha(r.proxima_generacion)}</td>
                    <td>${money(r.importe_estimado)}</td>
                    <td><span class="factura-badge ${r.activa ? 'factura-pagada' : 'factura-cancelada'}">${r.activa ? 'Activa' : 'Pausada'}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon" data-ver="${r.id_recurrente}" title="Ver detalle"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" data-generar="${r.id_recurrente}" title="Generar factura ahora"><i class="fas fa-bolt"></i></button>
                            <button class="btn-icon" data-edit="${r.id_recurrente}" title="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon danger" data-del="${r.id_recurrente}" data-nombre="${escAttr(r.nombre)}" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        tbody.querySelectorAll('[data-ver]').forEach(btn => btn.addEventListener('click', () => verDetalle(btn.dataset.ver)));
        tbody.querySelectorAll('[data-generar]').forEach(btn => btn.addEventListener('click', () => generarAhora(btn.dataset.generar)));
        tbody.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => editar(btn.dataset.edit)));
        tbody.querySelectorAll('[data-del]').forEach(btn => btn.addEventListener('click', () => confirmarEliminar(btn.dataset.del, btn.dataset.nombre)));
    }

    function abrirForm(data = null) {
        editId = data?.id_recurrente ?? null;
        document.getElementById('recurrentes-form-titulo').textContent = editId ? `Editar ${data.nombre}` : 'Nueva Plantilla';
        document.getElementById('recurrentes-form-panel')?.classList.add('active');
        document.getElementById('recurrentes-form')?.reset();
        limpiarErrores();
        renderContactosSelect(data?.contacto_id ?? '');

        setVal('rec-nombre', data?.nombre ?? '');
        setVal('rec-periodicidad', data?.periodicidad ?? 'mensual');
        setVal('rec-dia-generacion', data?.dia_generacion ?? 1);
        setVal('rec-dias-vencimiento', data?.dias_vencimiento ?? 30);
        setVal('rec-fecha-inicio', data?.fecha_inicio ?? new Date().toISOString().slice(0, 10));
        setVal('rec-fecha-fin', data?.fecha_fin ?? '');
        setVal('rec-notas', data?.notas ?? '');
        const activaEl = document.getElementById('rec-activa');
        if (activaEl) activaEl.checked = data ? !!data.activa : true;
        const emailEl = document.getElementById('rec-enviar-email');
        if (emailEl) emailEl.checked = !!data?.enviar_email;

        const lineas = data?.lineas?.length ? data.lineas : [{ concepto: '', cantidad: 1, precio_unitario: 0, iva_porcentaje: 21 }];
        renderLineas(lineas);
        calcularTotales();
        cerrarDetalle();
        document.getElementById('recurrentes-form-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        editId = null;
        document.getElementById('recurrentes-form-panel')?.classList.remove('active');
        document.getElementById('recurrentes-form')?.reset();
        renderLineas([]);
        limpiarErrores();
    }

    async function editar(id) {
        try {
            const r = await fetchSeguro('/api/facturas_recurrentes.php?id=' + encodeURIComponent(id));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            abrirForm(d.data);
        } catch (e) { manejarApiError(e, 'Error al cargar la plantilla'); }
    }

    function renderLineas(lineas) {
        const cont = document.getElementById('rec-lineas');
        if (!cont) return;
        cont.innerHTML = '';
        lineas.forEach(addLinea);
    }

    function addLinea(linea = {}) {
        const cont = document.getElementById('rec-lineas');
        if (!cont) return;
        const row = document.createElement('div');
        row.className = 'factura-linea';
        row.innerHTML = `
            <div class="factura-linea-producto">
                <label>Producto / Servicio</label>
                <select class="form-input rec-l-producto">
                    <option value="">Personalizado...</option>
                    ${productosCache.map(p => `<option value="${p.id_producto}">${esc(p.nombre)} (${money(p.precio)})</option>`).join('')}
                </select>
            </div>
            <div class="factura-linea-concepto">
                <label>Concepto</label>
                <input type="text" class="form-input rec-l-concepto" maxlength="255" value="${escAttr(linea.concepto ?? '')}">
            </div>
            <div>
                <label>Cant.</label>
                <input type="number" class="form-input rec-l-cantidad" min="0.01" step="0.01" value="${num(linea.cantidad ?? 1)}">
            </div>
            <div>
                <label>Precio</label>
                <input type="number" class="form-input rec-l-precio" min="0" step="0.01" value="${num(linea.precio_unitario ?? 0)}">
            </div>
            <div>
                <label>IVA %</label>
                <input type="number" class="form-input rec-l-iva" min="0" max="100" step="0.01" value="${num(linea.iva_porcentaje ?? 21)}">
            </div>
            <div class="factura-linea-total">
                <label>Total</label>
                <strong>0,00 EUR</strong>
            </div>
            <button type="button" class="btn-icon danger rec-l-del" title="Eliminar linea"><i class="fas fa-trash"></i></button>`;
        cont.appendChild(row);
        row.querySelectorAll('input').forEach(i => i.addEventListener('input', calcularTotales));
        row.querySelector('.rec-l-producto')?.addEventListener('change', (e) => {
            const producto = productosCache.find(p => String(p.id_producto) === e.target.value);
            if (!producto) return;
            row.querySelector('.rec-l-concepto').value = producto.nombre;
            row.querySelector('.rec-l-precio').value = num(producto.precio);
            row.querySelector('.rec-l-iva').value = num(producto.iva_porcentaje);
            calcularTotales();
        });
        row.querySelector('.rec-l-del')?.addEventListener('click', () => { row.remove(); calcularTotales(); });
        calcularTotales();
    }

    function leerLineas() {
        return [...document.querySelectorAll('#rec-lineas .factura-linea')].map(row => ({
            concepto: row.querySelector('.rec-l-concepto')?.value.trim() ?? '',
            cantidad: parseFloat(row.querySelector('.rec-l-cantidad')?.value || '0'),
            precio_unitario: parseFloat(row.querySelector('.rec-l-precio')?.value || '0'),
            iva_porcentaje: parseFloat(row.querySelector('.rec-l-iva')?.value || '0'),
        }));
    }

    function calcularTotales() {
        let base = 0; let iva = 0;
        document.querySelectorAll('#rec-lineas .factura-linea').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.rec-l-cantidad')?.value || '0');
            const precio = parseFloat(row.querySelector('.rec-l-precio')?.value || '0');
            const ivaPct = parseFloat(row.querySelector('.rec-l-iva')?.value || '0');
            const subtotal = Math.max(cantidad, 0) * Math.max(precio, 0);
            const ivaImporte = subtotal * (Math.max(ivaPct, 0) / 100);
            base += subtotal; iva += ivaImporte;
            const totalEl = row.querySelector('.factura-linea-total strong');
            if (totalEl) totalEl.textContent = money(subtotal + ivaImporte);
        });
        setText('rec-base', money(base));
        setText('rec-iva', money(iva));
        setText('rec-total', money(base + iva));
    }

    async function guardar(e) {
        e.preventDefault();
        limpiarErrores();
        const payload = {
            contacto_id: document.getElementById('rec-contacto')?.value,
            nombre: document.getElementById('rec-nombre')?.value.trim(),
            periodicidad: document.getElementById('rec-periodicidad')?.value,
            dia_generacion: document.getElementById('rec-dia-generacion')?.value,
            dias_vencimiento: document.getElementById('rec-dias-vencimiento')?.value,
            fecha_inicio: document.getElementById('rec-fecha-inicio')?.value,
            fecha_fin: document.getElementById('rec-fecha-fin')?.value,
            notas: document.getElementById('rec-notas')?.value.trim(),
            activa: document.getElementById('rec-activa')?.checked,
            enviar_email: document.getElementById('rec-enviar-email')?.checked,
            lineas: leerLineas(),
        };
        if (!validar(payload)) return;

        try {
            const url = '/api/facturas_recurrentes.php' + (editId ? '?id=' + encodeURIComponent(editId) : '');
            const r = await fetchSeguro(url, {
                method: editId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast(editId ? 'Plantilla actualizada' : 'Plantilla creada', 'success');
            cerrarForm();
            cargar();
        } catch (err) { manejarApiError(err, 'Error al guardar la plantilla'); }
    }

    function validar(payload) {
        let valido = true;
        if (!payload.contacto_id) { error('err-rec-contacto', 'Selecciona un contacto'); valido = false; }
        if (!payload.nombre) { error('err-rec-nombre', 'El nombre es obligatorio'); valido = false; }
        if (!payload.fecha_inicio) { error('err-rec-fecha-inicio', 'La fecha de inicio es obligatoria'); valido = false; }
        if (payload.fecha_fin && payload.fecha_fin < payload.fecha_inicio) {
            error('err-rec-fecha-fin', 'No puede ser anterior al inicio'); valido = false;
        }
        if (!payload.lineas.length) { error('err-rec-lineas', 'Añade al menos una línea'); valido = false; }
        return valido;
    }

    async function verDetalle(id) {
        try {
            const r = await fetchSeguro('/api/facturas_recurrentes.php?id=' + encodeURIComponent(id));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            detalleActual = d.data;
            renderDetalle(d.data);
        } catch (e) { manejarApiError(e, 'Error al cargar detalle'); }
    }

    function renderDetalle(r) {
        const contacto = `${r.contacto_nombre || ''} ${r.contacto_apellidos || ''}`.trim();
        setText('rec-det-nombre', r.nombre);
        setText('rec-det-contacto', contacto);
        setText('rec-det-periodicidad', periodicidadLabel[r.periodicidad] || r.periodicidad);
        setText('rec-det-proxima', formatFecha(r.proxima_generacion));
        setText('rec-det-estado', r.activa ? 'Activa' : 'Pausada');

        const total = (r.lineas || []).reduce((acc, l) => {
            const sub = l.cantidad * l.precio_unitario;
            return acc + sub + sub * (l.iva_porcentaje / 100);
        }, 0);
        setText('rec-det-total', money(total));

        const lineasEl = document.getElementById('rec-det-lineas');
        if (lineasEl) {
            lineasEl.innerHTML = (r.lineas || []).map(l => `
                <div class="factura-det-linea">
                    <div>
                        <strong>${esc(l.concepto)}</strong>
                        <small>${num(l.cantidad)} x ${money(l.precio_unitario)} - IVA ${num(l.iva_porcentaje)}%</small>
                    </div>
                    <span>${money(l.cantidad * l.precio_unitario * (1 + l.iva_porcentaje / 100))}</span>
                </div>`).join('');
        }

        const histEl = document.getElementById('rec-det-historial');
        if (histEl) {
            histEl.innerHTML = (r.historial || []).length
                ? r.historial.map(f => `
                    <div class="factura-det-linea">
                        <div><strong>${esc(f.numero)}</strong><small>${formatFecha(f.fecha_emision)}</small></div>
                        <span>${money(f.total)}</span>
                    </div>`).join('')
                : '<p class="chart-empty">Todavía no se ha generado ninguna factura desde esta plantilla</p>';
        }

        document.getElementById('recurrente-detalle-overlay')?.classList.add('active');
        document.body.classList.add('detalle-open');
    }

    function cerrarDetalle() {
        document.getElementById('recurrente-detalle-overlay')?.classList.remove('active');
        document.body.classList.remove('detalle-open');
    }

    function generarAhora(id) {
        mostrarConfirm(
            'Generar factura ahora',
            'Se creará una factura real inmediatamente con las líneas actuales de la plantilla, adelantando el ciclo.',
            async () => {
                try {
                    const r = await fetchSeguro('/api/facturas_recurrentes.php?id=' + encodeURIComponent(id) + '&action=generar', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}',
                    });
                    const d = await r.json();
                    if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                    mostrarToast(`Factura ${d.data.numero} generada (${money(d.data.total)})`, 'success');
                    cerrarDetalle();
                    cargar();
                } catch (e) { manejarApiError(e, 'Error al generar la factura'); }
            }
        );
    }

    function confirmarEliminar(id, nombre) {
        mostrarConfirm('Eliminar plantilla', `Se eliminará "${nombre}". Las facturas ya generadas no se verán afectadas.`, async () => {
            try {
                const r = await fetchSeguro('/api/facturas_recurrentes.php?id=' + encodeURIComponent(id), { method: 'DELETE' });
                const d = await r.json();
                if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                mostrarToast('Plantilla eliminada', 'success');
                cerrarDetalle();
                cargar();
            } catch (e) { manejarApiError(e, 'Error al eliminar la plantilla'); }
        });
    }

    function money(v) { return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(parseFloat(v || 0)); }
    function formatFecha(v) { if (!v) return '-'; return new Date(v + 'T00:00:00').toLocaleDateString('es-ES'); }
    function esc(v) { return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c])); }
    function escAttr(v) { return esc(v).replace(/`/g, '&#096;'); }
    function num(v) { return Number.parseFloat(v || 0).toFixed(2); }
    function setVal(id, v) { const el = document.getElementById(id); if (el) el.value = v ?? ''; }
    function setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v ?? ''; }
    function error(id, msg) { const el = document.getElementById(id); if (el) el.textContent = msg; }
    function limpiarErrores() { document.querySelectorAll('#recurrentes-form .form-error').forEach(el => { el.textContent = ''; }); }

    return { init };
})();
