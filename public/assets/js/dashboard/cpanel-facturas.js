const Facturas = (() => {
    let editId = null;
    let detalleActual = null;
    let buscarTimer = null;
    let contactosCache = [];
    let productosCache = [];
    let initDone = false;
    let estado = { buscar: '', estado: '', desde: '', hasta: '', orden: 'fecha_emision', dir: 'desc', pagina: 1, limite: 20 };

    const estados = {
        borrador: 'Borrador',
        emitida: 'Emitida',
        pagada: 'Pagada',
        vencida: 'Vencida',
        cancelada: 'Cancelada',
    };

    function init() {
        if (initDone) { cargar(); return; }
        initDone = true;

        const overlay = document.getElementById('factura-detalle-overlay');
        if (overlay) document.body.appendChild(overlay);

        document.getElementById('facturas-nueva-btn')?.addEventListener('click', () => abrirForm());
        document.getElementById('facturas-cancelar-btn')?.addEventListener('click', cerrarForm);
        document.getElementById('facturas-form')?.addEventListener('submit', guardar);
        document.getElementById('fac-linea-add')?.addEventListener('click', () => addLinea());
        document.getElementById('fac-det-cerrar')?.addEventListener('click', cerrarDetalle);
        document.getElementById('fac-det-editar')?.addEventListener('click', () => {
            if (detalleActual) abrirForm(detalleActual);
        });
        document.getElementById('fac-det-eliminar')?.addEventListener('click', () => {
            if (detalleActual) confirmarEliminar(detalleActual.id_factura, detalleActual.numero);
        });
        document.getElementById('fac-det-pdf')?.addEventListener('click', () => {
            if (detalleActual) descargarPdf(detalleActual.id_factura);
        });
        document.getElementById('fac-det-email')?.addEventListener('click', (e) => {
            if (detalleActual) enviarEmailFactura(detalleActual.id_factura, e.currentTarget);
        });

        document.getElementById('facturas-buscar')?.addEventListener('input', (e) => {
            clearTimeout(buscarTimer);
            estado.buscar = e.target.value.trim();
            estado.pagina = 1;
            buscarTimer = setTimeout(cargar, 400);
        });

        document.getElementById('fac-filtros-toggle')?.addEventListener('click', () => {
            document.getElementById('fac-filtros-avanzados')?.classList.toggle('active');
        });

        filtroChange('estado', 'fac-filtro-estado');
        filtroChange('desde', 'fac-filtro-desde');
        filtroChange('hasta', 'fac-filtro-hasta');
        filtroChange('orden', 'fac-filtro-orden');

        document.getElementById('fac-filtro-dir')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            estado.dir = estado.dir === 'desc' ? 'asc' : 'desc';
            estado.pagina = 1;
            btn.dataset.dir = estado.dir;
            btn.querySelector('i').className = estado.dir === 'asc'
                ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down';
            cargar();
        });

        document.getElementById('fac-filtros-clear')?.addEventListener('click', limpiarFiltros);

        document.getElementById('facturas-exportar-btn')?.addEventListener('click', () => {
            window.open('/api/facturas.php?action=exportar', '_blank');
        });
        document.getElementById('facturas-importar-btn')?.addEventListener('click', () => {
            document.getElementById('facturas-importar-input')?.click();
        });
        document.getElementById('facturas-importar-input')?.addEventListener('change', async (e) => {
            const file = e.target.files?.[0];
            e.target.value = '';
            if (!file) return;
            try {
                const d = await importarCsvArchivo('/api/facturas.php?action=importar', file);
                mostrarResultadoImportacion(d);
                cargar();
            } catch (err) {
                manejarApiError(err, 'Error al importar facturas');
            }
        });

        cargarContactos();
        cargarProductos();
        cargar();
    }

    function filtroChange(key, id) {
        document.getElementById(id)?.addEventListener('change', (e) => {
            estado[key] = e.target.value;
            estado.pagina = 1;
            actualizarBadge();
            cargar();
        });
    }

    function actualizarBadge() {
        const n = ['estado', 'desde', 'hasta'].filter(k => estado[k] !== '').length;
        const badge = document.getElementById('fac-filtros-badge');
        if (badge) { badge.textContent = n; badge.style.display = n > 0 ? '' : 'none'; }
    }

    function limpiarFiltros() {
        estado = { buscar: estado.buscar, estado: '', desde: '', hasta: '', orden: 'fecha_emision', dir: 'desc', pagina: 1, limite: 20 };
        ['fac-filtro-estado', 'fac-filtro-desde', 'fac-filtro-hasta'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const ord = document.getElementById('fac-filtro-orden'); if (ord) ord.value = 'fecha_emision';
        const dir = document.getElementById('fac-filtro-dir');
        if (dir) { dir.dataset.dir = 'desc'; dir.querySelector('i').className = 'fas fa-sort-amount-down'; }
        actualizarBadge();
        cargar();
    }

    async function cargarContactos() {
        try {
            const r = await fetchSeguro('/api/contactos.php?limite=100&orden=nombre&dir=asc');
            const d = await r.json();
            contactosCache = d.ok ? d.data : [];
            renderContactosSelect();
        } catch (e) {
            manejarApiError(e, 'Error al cargar contactos');
        }
    }

    function renderContactosSelect(selected = '') {
        const select = document.getElementById('fac-contacto');
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
        } catch (e) {
            manejarApiError(e, 'Error al cargar productos');
        }
    }

    async function cargar() {
        const params = new URLSearchParams();
        Object.entries(estado).forEach(([k, v]) => { if (v !== '' && v !== 0) params.set(k, v); });
        try {
            const r = await fetchSeguro('/api/facturas.php' + (params.size ? '?' + params : ''));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            renderTabla(d.data);
            renderPaginacion(d.meta, 'fac-paginacion', (p) => { estado.pagina = p; cargar(); });
        } catch (e) {
            manejarApiError(e, 'Error al cargar facturas');
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('facturas-tbody');
        if (!tbody) return;
        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="crm-empty">
                    <i class="fas fa-file-invoice"></i>
                    <p>No hay facturas.
                        <button class="btn-link" id="fac-crear-primera">Crear la primera</button>
                    </p>
                </td></tr>`;
            tbody.querySelector('#fac-crear-primera')?.addEventListener('click', () => abrirForm());
            return;
        }

        tbody.innerHTML = lista.map(f => {
            const contacto = nombreContacto(f);
            return `
                <tr>
                    <td><strong>${esc(f.numero)}</strong></td>
                    <td>
                        <div class="crm-nombre-cell">
                            <span class="crm-avatar factura-av">${esc(contacto).slice(0,2).toUpperCase()}</span>
                            <div>
                                <strong>${esc(contacto)}</strong>
                                <small>${esc(f.contacto_empresa || f.contacto_email || '')}</small>
                            </div>
                        </div>
                    </td>
                    <td>${badgeEstado(f.estado)}</td>
                    <td>${formatFecha(f.fecha_emision)}</td>
                    <td>${formatFecha(f.fecha_vencimiento)}</td>
                    <td><strong>${money(f.total)}</strong></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon" data-ver="${f.id_factura}" title="Ver detalle"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" data-pdf="${f.id_factura}" title="Descargar PDF"><i class="fas fa-file-pdf"></i></button>
                            <button class="btn-icon" data-email="${f.id_factura}" title="Enviar por email"><i class="fas fa-paper-plane"></i></button>
                            <button class="btn-icon" data-edit="${f.id_factura}" title="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon danger" data-del="${f.id_factura}" data-numero="${escAttr(f.numero)}" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        tbody.querySelectorAll('[data-ver]').forEach(btn => btn.addEventListener('click', () => verDetalle(btn.dataset.ver)));
        tbody.querySelectorAll('[data-pdf]').forEach(btn => btn.addEventListener('click', () => descargarPdf(btn.dataset.pdf)));
        tbody.querySelectorAll('[data-email]').forEach(btn => btn.addEventListener('click', () => enviarEmailFactura(btn.dataset.email, btn)));
        tbody.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => editar(btn.dataset.edit)));
        tbody.querySelectorAll('[data-del]').forEach(btn => btn.addEventListener('click', () => confirmarEliminar(btn.dataset.del, btn.dataset.numero)));
    }

    function abrirForm(data = null) {
        editId = data?.id_factura ?? null;
        document.getElementById('facturas-form-titulo').textContent = editId ? `Editar ${data.numero}` : 'Nueva Factura';
        document.getElementById('facturas-form-panel')?.classList.add('active');
        document.getElementById('facturas-form')?.reset();
        limpiarErrores();
        renderContactosSelect(data?.contacto_id ?? '');

        const hoy = new Date().toISOString().slice(0, 10);
        setVal('fac-estado', data?.estado ?? 'borrador');
        setVal('fac-fecha-emision', data?.fecha_emision ?? hoy);
        setVal('fac-fecha-vencimiento', data?.fecha_vencimiento ?? '');
        setVal('fac-notas', data?.notas ?? '');

        const lineas = data?.lineas?.length ? data.lineas : [{ concepto: '', cantidad: 1, precio_unitario: 0, iva_porcentaje: 21 }];
        renderLineas(lineas);
        calcularTotales();
        cerrarDetalle();
        document.getElementById('facturas-form-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        editId = null;
        document.getElementById('facturas-form-panel')?.classList.remove('active');
        document.getElementById('facturas-form')?.reset();
        renderLineas([]);
        limpiarErrores();
    }

    async function editar(id) {
        try {
            const r = await fetchSeguro('/api/facturas.php?id=' + encodeURIComponent(id));
            const d = await r.json();
            d.ok ? abrirForm(d.data) : mostrarToast(d.error, 'error');
        } catch (e) {
            manejarApiError(e, 'Error al cargar factura');
        }
    }

    function renderLineas(lineas) {
        const cont = document.getElementById('fac-lineas');
        if (!cont) return;
        cont.innerHTML = '';
        lineas.forEach(addLinea);
    }

    function addLinea(linea = {}) {
        const cont = document.getElementById('fac-lineas');
        if (!cont) return;
        const row = document.createElement('div');
        row.className = 'factura-linea';
        row.innerHTML = `
            <div class="factura-linea-producto">
                <label>Producto / Servicio</label>
                <select class="form-input fac-l-producto">
                    <option value="">Personalizado...</option>
                    ${productosCache.map(p => `<option value="${p.id_producto}">${esc(p.nombre)} (${money(p.precio)})</option>`).join('')}
                </select>
            </div>
            <div class="factura-linea-concepto">
                <label>Concepto</label>
                <input type="text" class="form-input fac-l-concepto" maxlength="255" placeholder="Servicio, producto o concepto" value="${escAttr(linea.concepto ?? '')}">
            </div>
            <div>
                <label>Cant.</label>
                <input type="number" class="form-input fac-l-cantidad" min="0.01" step="0.01" value="${num(linea.cantidad ?? 1)}">
            </div>
            <div>
                <label>Precio</label>
                <input type="number" class="form-input fac-l-precio" min="0" step="0.01" value="${num(linea.precio_unitario ?? 0)}">
            </div>
            <div>
                <label>IVA %</label>
                <input type="number" class="form-input fac-l-iva" min="0" max="100" step="0.01" value="${num(linea.iva_porcentaje ?? 21)}">
            </div>
            <div class="factura-linea-total">
                <label>Total</label>
                <strong>0,00 EUR</strong>
            </div>
            <button type="button" class="btn-icon danger fac-l-del" title="Eliminar linea"><i class="fas fa-trash"></i></button>`;
        cont.appendChild(row);
        row.querySelectorAll('input').forEach(i => i.addEventListener('input', calcularTotales));
        row.querySelector('.fac-l-producto')?.addEventListener('change', (e) => {
            const producto = productosCache.find(p => String(p.id_producto) === e.target.value);
            if (!producto) return;
            const concepto = row.querySelector('.fac-l-concepto');
            const precio = row.querySelector('.fac-l-precio');
            const iva = row.querySelector('.fac-l-iva');
            if (concepto) concepto.value = producto.nombre;
            if (precio) precio.value = num(producto.precio);
            if (iva) iva.value = num(producto.iva_porcentaje);
            calcularTotales();
        });
        row.querySelector('.fac-l-del')?.addEventListener('click', () => {
            row.remove();
            calcularTotales();
        });
        calcularTotales();
    }

    function leerLineas() {
        return [...document.querySelectorAll('#fac-lineas .factura-linea')].map(row => ({
            concepto: row.querySelector('.fac-l-concepto')?.value.trim() ?? '',
            cantidad: parseFloat(row.querySelector('.fac-l-cantidad')?.value || '0'),
            precio_unitario: parseFloat(row.querySelector('.fac-l-precio')?.value || '0'),
            iva_porcentaje: parseFloat(row.querySelector('.fac-l-iva')?.value || '0'),
        }));
    }

    function calcularTotales() {
        let base = 0;
        let iva = 0;
        document.querySelectorAll('#fac-lineas .factura-linea').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.fac-l-cantidad')?.value || '0');
            const precio = parseFloat(row.querySelector('.fac-l-precio')?.value || '0');
            const ivaPct = parseFloat(row.querySelector('.fac-l-iva')?.value || '0');
            const subtotal = Math.max(cantidad, 0) * Math.max(precio, 0);
            const ivaImporte = subtotal * (Math.max(ivaPct, 0) / 100);
            base += subtotal;
            iva += ivaImporte;
            const totalEl = row.querySelector('.factura-linea-total strong');
            if (totalEl) totalEl.textContent = money(subtotal + ivaImporte);
        });
        setText('fac-base', money(base));
        setText('fac-iva', money(iva));
        setText('fac-total', money(base + iva));
    }

    async function guardar(e) {
        e.preventDefault();
        limpiarErrores();
        const payload = {
            contacto_id: document.getElementById('fac-contacto')?.value,
            estado: document.getElementById('fac-estado')?.value,
            fecha_emision: document.getElementById('fac-fecha-emision')?.value,
            fecha_vencimiento: document.getElementById('fac-fecha-vencimiento')?.value,
            notas: document.getElementById('fac-notas')?.value.trim(),
            lineas: leerLineas(),
        };
        if (!validar(payload)) return;

        try {
            const url = '/api/facturas.php' + (editId ? '?id=' + encodeURIComponent(editId) : '');
            const r = await fetchSeguro(url, {
                method: editId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast(editId ? 'Factura actualizada' : 'Factura creada', 'success');
            cerrarForm();
            cargar();
        } catch (err) {
            manejarApiError(err, 'Error al guardar factura');
        }
    }

    function validar(payload) {
        let ok = true;
        if (!payload.contacto_id) { error('err-fac-contacto', 'Selecciona un contacto'); ok = false; }
        if (!payload.fecha_emision) { error('err-fac-fecha-emision', 'La fecha de emision es obligatoria'); ok = false; }
        if (payload.fecha_vencimiento && payload.fecha_vencimiento < payload.fecha_emision) {
            error('err-fac-fecha-vencimiento', 'No puede ser anterior a la emision'); ok = false;
        }
        if (!payload.lineas.length) { error('err-fac-lineas', 'Anade al menos una linea'); ok = false; }
        payload.lineas.forEach((l, i) => {
            if (!l.concepto || l.cantidad <= 0 || l.precio_unitario < 0 || l.iva_porcentaje < 0 || l.iva_porcentaje > 100) {
                error('err-fac-lineas', `Revisa la linea ${i + 1}: concepto, cantidad, precio e IVA`);
                ok = false;
            }
        });
        return ok;
    }

    async function verDetalle(id) {
        try {
            const r = await fetchSeguro('/api/facturas.php?id=' + encodeURIComponent(id));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            detalleActual = d.data;
            renderDetalle(d.data);
        } catch (e) {
            manejarApiError(e, 'Error al cargar detalle');
        }
    }

    function renderDetalle(f) {
        setText('fac-det-numero', f.numero);
        setText('fac-det-contacto', nombreContacto(f));
        setText('fac-det-fechas', `${formatFecha(f.fecha_emision)} / ${formatFecha(f.fecha_vencimiento)}`);
        setText('fac-det-total', money(f.total));
        const estadoEl = document.getElementById('fac-det-estado');
        if (estadoEl) estadoEl.innerHTML = badgeEstado(f.estado);
        const lineas = document.getElementById('fac-det-lineas');
        if (lineas) {
            lineas.innerHTML = (f.lineas || []).map(l => `
                <div class="factura-det-linea">
                    <div>
                        <strong>${esc(l.concepto)}</strong>
                        <small>${num(l.cantidad)} x ${money(l.precio_unitario)} - IVA ${num(l.iva_porcentaje)}%</small>
                    </div>
                    <span>${money(l.total_linea)}</span>
                </div>`).join('');
        }
        const notasBloque = document.getElementById('fac-det-notas-bloque');
        setText('fac-det-notas', f.notas || '');
        if (notasBloque) notasBloque.style.display = f.notas ? '' : 'none';
        document.getElementById('factura-detalle-overlay')?.classList.add('active');
        document.body.classList.add('detalle-open');
    }

    function cerrarDetalle() {
        document.getElementById('factura-detalle-overlay')?.classList.remove('active');
        document.body.classList.remove('detalle-open');
    }

    function descargarPdf(id) {
        window.open('/api/factura_pdf.php?id=' + encodeURIComponent(id), '_blank');
    }

    async function enviarEmailFactura(id, btn = null) {
        const original = btn?.innerHTML;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        try {
            const r = await fetchSeguro('/api/factura_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
            });
            const d = await r.json();
            if (!d.ok) {
                mostrarToast(d.error || 'No se pudo enviar la factura', 'error');
                return;
            }
            mostrarToast('Factura enviada por email', 'success');
        } catch (e) {
            manejarApiError(e, 'Error al enviar factura');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }
    }

    function confirmarEliminar(id, numero) {
        mostrarConfirm('Eliminar factura', `Se eliminara ${numero}. Esta accion no se puede deshacer.`, async () => {
            try {
                const r = await fetchSeguro('/api/facturas.php?id=' + encodeURIComponent(id), { method: 'DELETE' });
                const d = await r.json();
                if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                mostrarToast('Factura eliminada', 'success');
                cerrarDetalle();
                cargar();
            } catch (e) {
                manejarApiError(e, 'Error al eliminar factura');
            }
        });
    }

    function badgeEstado(estado) {
        return `<span class="factura-badge factura-${escAttr(estado)}">${estados[estado] || estado}</span>`;
    }

    function nombreContacto(f) {
        return `${f.contacto_nombre || ''} ${f.contacto_apellidos || ''}`.trim() || 'Contacto';
    }

    function money(v) {
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(parseFloat(v || 0));
    }

    function formatFecha(v) {
        if (!v) return '-';
        return new Date(v + 'T00:00:00').toLocaleDateString('es-ES');
    }

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    }

    function escAttr(v) { return esc(v).replace(/`/g, '&#096;'); }
    function num(v) { return Number.parseFloat(v || 0).toFixed(2); }
    function setVal(id, v) { const el = document.getElementById(id); if (el) el.value = v ?? ''; }
    function setText(id, v) { const el = document.getElementById(id); if (el) el.textContent = v ?? ''; }
    function error(id, msg) { const el = document.getElementById(id); if (el) el.textContent = msg; }
    function limpiarErrores() { document.querySelectorAll('#facturas-form .form-error').forEach(el => { el.textContent = ''; }); }

    return { init };
})();
