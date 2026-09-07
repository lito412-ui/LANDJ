const Presupuestos = (() => {
    let editId = null;
    let detalleActual = null;
    let buscarTimer = null;
    let contactosCache = [];
    let productosCache = [];
    let initDone = false;
    let estadoFiltro = { buscar: '', estado: '', desde: '', hasta: '', orden: 'fecha_emision', dir: 'desc', pagina: 1, limite: 20 };

    const estados = {
        borrador: 'Borrador',
        enviado: 'Enviado',
        aceptado: 'Aceptado',
        rechazado: 'Rechazado',
        expirado: 'Expirado',
        convertido: 'Convertido',
    };

    function init() {
        if (initDone) { cargar(); return; }
        initDone = true;

        const overlay = document.getElementById('presupuesto-detalle-overlay');
        if (overlay) document.body.appendChild(overlay);

        document.getElementById('presupuestos-nueva-btn')?.addEventListener('click', () => abrirForm());
        document.getElementById('presupuestos-cancelar-btn')?.addEventListener('click', cerrarForm);
        document.getElementById('presupuestos-form')?.addEventListener('submit', guardar);
        document.getElementById('pre-linea-add')?.addEventListener('click', () => addLinea());
        document.getElementById('pre-det-cerrar')?.addEventListener('click', cerrarDetalle);
        document.getElementById('pre-det-editar')?.addEventListener('click', () => {
            if (detalleActual) abrirForm(detalleActual);
        });
        document.getElementById('pre-det-eliminar')?.addEventListener('click', () => {
            if (detalleActual) confirmarEliminar(detalleActual.id_presupuesto, detalleActual.numero);
        });
        document.getElementById('pre-det-pdf')?.addEventListener('click', () => {
            if (detalleActual) descargarPdf(detalleActual.id_presupuesto);
        });
        document.getElementById('pre-det-email')?.addEventListener('click', (e) => {
            if (detalleActual) enviarEmailPresupuesto(detalleActual.id_presupuesto, e.currentTarget);
        });
        document.getElementById('pre-det-convertir')?.addEventListener('click', () => {
            if (detalleActual) convertirEnFactura(detalleActual.id_presupuesto);
        });
        document.getElementById('pre-det-link-copiar')?.addEventListener('click', copiarLinkConfirmacion);

        document.getElementById('presupuestos-buscar')?.addEventListener('input', (e) => {
            clearTimeout(buscarTimer);
            estadoFiltro.buscar = e.target.value.trim();
            estadoFiltro.pagina = 1;
            buscarTimer = setTimeout(cargar, 400);
        });

        document.getElementById('pre-filtros-toggle')?.addEventListener('click', () => {
            document.getElementById('pre-filtros-avanzados')?.classList.toggle('active');
        });

        filtroChange('estado', 'pre-filtro-estado');
        filtroChange('desde', 'pre-filtro-desde');
        filtroChange('hasta', 'pre-filtro-hasta');
        filtroChange('orden', 'pre-filtro-orden');

        document.getElementById('pre-filtro-dir')?.addEventListener('click', (e) => {
            const btn = e.currentTarget;
            estadoFiltro.dir = estadoFiltro.dir === 'desc' ? 'asc' : 'desc';
            estadoFiltro.pagina = 1;
            btn.dataset.dir = estadoFiltro.dir;
            btn.querySelector('i').className = estadoFiltro.dir === 'asc'
                ? 'fas fa-sort-amount-up' : 'fas fa-sort-amount-down';
            cargar();
        });

        document.getElementById('pre-filtros-clear')?.addEventListener('click', limpiarFiltros);

        document.getElementById('presupuestos-exportar-btn')?.addEventListener('click', () => {
            window.open('/api/presupuestos.php?action=exportar', '_blank');
        });
        document.getElementById('presupuestos-importar-btn')?.addEventListener('click', () => {
            document.getElementById('presupuestos-importar-input')?.click();
        });
        document.getElementById('presupuestos-importar-input')?.addEventListener('change', async (e) => {
            const file = e.target.files?.[0];
            e.target.value = '';
            if (!file) return;
            try {
                const d = await importarCsvArchivo('/api/presupuestos.php?action=importar', file);
                mostrarResultadoImportacion(d);
                cargar();
            } catch (err) {
                manejarApiError(err, 'Error al importar presupuestos');
            }
        });

        cargarContactos();
        cargarProductos();
        cargar();
    }

    function filtroChange(key, id) {
        document.getElementById(id)?.addEventListener('change', (e) => {
            estadoFiltro[key] = e.target.value;
            estadoFiltro.pagina = 1;
            actualizarBadge();
            cargar();
        });
    }

    function actualizarBadge() {
        const n = ['estado', 'desde', 'hasta'].filter(k => estadoFiltro[k] !== '').length;
        const badge = document.getElementById('pre-filtros-badge');
        if (badge) { badge.textContent = n; badge.style.display = n > 0 ? '' : 'none'; }
    }

    function limpiarFiltros() {
        estadoFiltro = { buscar: estadoFiltro.buscar, estado: '', desde: '', hasta: '', orden: 'fecha_emision', dir: 'desc', pagina: 1, limite: 20 };
        ['pre-filtro-estado', 'pre-filtro-desde', 'pre-filtro-hasta'].forEach(id => {
            const el = document.getElementById(id); if (el) el.value = '';
        });
        const ord = document.getElementById('pre-filtro-orden'); if (ord) ord.value = 'fecha_emision';
        const dir = document.getElementById('pre-filtro-dir');
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
        const select = document.getElementById('pre-contacto');
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
        Object.entries(estadoFiltro).forEach(([k, v]) => { if (v !== '' && v !== 0) params.set(k, v); });
        try {
            const r = await fetchSeguro('/api/presupuestos.php' + (params.size ? '?' + params : ''));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            renderTabla(d.data);
            renderPaginacion(d.meta, 'pre-paginacion', (p) => { estadoFiltro.pagina = p; cargar(); });
        } catch (e) {
            manejarApiError(e, 'Error al cargar presupuestos');
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('presupuestos-tbody');
        if (!tbody) return;
        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="7" class="crm-empty">
                    <i class="fas fa-file-signature"></i>
                    <p>No hay presupuestos.
                        <button class="btn-link" id="pre-crear-primero">Crear el primero</button>
                    </p>
                </td></tr>`;
            tbody.querySelector('#pre-crear-primero')?.addEventListener('click', () => abrirForm());
            return;
        }

        tbody.innerHTML = lista.map(p => {
            const contacto = nombreContacto(p);
            return `
                <tr>
                    <td><strong>${esc(p.numero)}</strong></td>
                    <td>
                        <div class="crm-nombre-cell">
                            <span class="crm-avatar factura-av">${esc(contacto).slice(0,2).toUpperCase()}</span>
                            <div>
                                <strong>${esc(contacto)}</strong>
                                <small>${esc(p.contacto_empresa || p.contacto_email || '')}</small>
                            </div>
                        </div>
                    </td>
                    <td>${badgeEstado(p.estado)}</td>
                    <td>${formatFecha(p.fecha_emision)}</td>
                    <td>${formatFecha(p.fecha_validez)}</td>
                    <td><strong>${money(p.total)}</strong></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon" data-ver="${p.id_presupuesto}" title="Ver detalle"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon" data-pdf="${p.id_presupuesto}" title="Descargar PDF"><i class="fas fa-file-pdf"></i></button>
                            <button class="btn-icon" data-email="${p.id_presupuesto}" title="Enviar por email"><i class="fas fa-paper-plane"></i></button>
                            <button class="btn-icon" data-edit="${p.id_presupuesto}" title="Editar" ${p.factura_id ? 'disabled' : ''}><i class="fas fa-edit"></i></button>
                            <button class="btn-icon danger" data-del="${p.id_presupuesto}" data-numero="${escAttr(p.numero)}" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        tbody.querySelectorAll('[data-ver]').forEach(btn => btn.addEventListener('click', () => verDetalle(btn.dataset.ver)));
        tbody.querySelectorAll('[data-pdf]').forEach(btn => btn.addEventListener('click', () => descargarPdf(btn.dataset.pdf)));
        tbody.querySelectorAll('[data-email]').forEach(btn => btn.addEventListener('click', () => enviarEmailPresupuesto(btn.dataset.email, btn)));
        tbody.querySelectorAll('[data-edit]').forEach(btn => btn.addEventListener('click', () => { if (!btn.disabled) editar(btn.dataset.edit); }));
        tbody.querySelectorAll('[data-del]').forEach(btn => btn.addEventListener('click', () => confirmarEliminar(btn.dataset.del, btn.dataset.numero)));
    }

    function abrirForm(data = null) {
        editId = data?.id_presupuesto ?? null;
        document.getElementById('presupuestos-form-titulo').textContent = editId ? `Editar ${data.numero}` : 'Nuevo Presupuesto';
        document.getElementById('presupuestos-form-panel')?.classList.add('active');
        document.getElementById('presupuestos-form')?.reset();
        limpiarErrores();
        renderContactosSelect(data?.contacto_id ?? '');

        const hoy = new Date().toISOString().slice(0, 10);
        setVal('pre-estado', data?.estado ?? 'borrador');
        setVal('pre-fecha-emision', data?.fecha_emision ?? hoy);
        setVal('pre-fecha-validez', data?.fecha_validez ?? '');
        setVal('pre-notas', data?.notas ?? '');

        const lineas = data?.lineas?.length ? data.lineas : [{ concepto: '', cantidad: 1, precio_unitario: 0, iva_porcentaje: 21 }];
        renderLineas(lineas);
        calcularTotales();
        cerrarDetalle();
        document.getElementById('presupuestos-form-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        editId = null;
        document.getElementById('presupuestos-form-panel')?.classList.remove('active');
        document.getElementById('presupuestos-form')?.reset();
        renderLineas([]);
        limpiarErrores();
    }

    async function editar(id) {
        try {
            const r = await fetchSeguro('/api/presupuestos.php?id=' + encodeURIComponent(id));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            if (d.data.factura_id) { mostrarToast('Este presupuesto ya se convirtió en factura y no se puede editar', 'error'); return; }
            abrirForm(d.data);
        } catch (e) {
            manejarApiError(e, 'Error al cargar presupuesto');
        }
    }

    function renderLineas(lineas) {
        const cont = document.getElementById('pre-lineas');
        if (!cont) return;
        cont.innerHTML = '';
        lineas.forEach(addLinea);
    }

    function addLinea(linea = {}) {
        const cont = document.getElementById('pre-lineas');
        if (!cont) return;
        const row = document.createElement('div');
        row.className = 'factura-linea';
        row.innerHTML = `
            <div class="factura-linea-producto">
                <label>Producto / Servicio</label>
                <select class="form-input pre-l-producto">
                    <option value="">Personalizado...</option>
                    ${productosCache.map(p => `<option value="${p.id_producto}">${esc(p.nombre)} (${money(p.precio)})</option>`).join('')}
                </select>
            </div>
            <div class="factura-linea-concepto">
                <label>Concepto</label>
                <input type="text" class="form-input pre-l-concepto" maxlength="255" placeholder="Servicio, producto o concepto" value="${escAttr(linea.concepto ?? '')}">
            </div>
            <div>
                <label>Cant.</label>
                <input type="number" class="form-input pre-l-cantidad" min="0.01" step="0.01" value="${num(linea.cantidad ?? 1)}">
            </div>
            <div>
                <label>Precio</label>
                <input type="number" class="form-input pre-l-precio" min="0" step="0.01" value="${num(linea.precio_unitario ?? 0)}">
            </div>
            <div>
                <label>IVA %</label>
                <input type="number" class="form-input pre-l-iva" min="0" max="100" step="0.01" value="${num(linea.iva_porcentaje ?? 21)}">
            </div>
            <div class="factura-linea-total">
                <label>Total</label>
                <strong>0,00 EUR</strong>
            </div>
            <button type="button" class="btn-icon danger pre-l-del" title="Eliminar linea"><i class="fas fa-trash"></i></button>`;
        cont.appendChild(row);
        row.querySelectorAll('input').forEach(i => i.addEventListener('input', calcularTotales));
        row.querySelector('.pre-l-producto')?.addEventListener('change', (e) => {
            const producto = productosCache.find(p => String(p.id_producto) === e.target.value);
            if (!producto) return;
            const concepto = row.querySelector('.pre-l-concepto');
            const precio = row.querySelector('.pre-l-precio');
            const iva = row.querySelector('.pre-l-iva');
            if (concepto) concepto.value = producto.nombre;
            if (precio) precio.value = num(producto.precio);
            if (iva) iva.value = num(producto.iva_porcentaje);
            calcularTotales();
        });
        row.querySelector('.pre-l-del')?.addEventListener('click', () => {
            row.remove();
            calcularTotales();
        });
        calcularTotales();
    }

    function leerLineas() {
        return [...document.querySelectorAll('#pre-lineas .factura-linea')].map(row => ({
            concepto: row.querySelector('.pre-l-concepto')?.value.trim() ?? '',
            cantidad: parseFloat(row.querySelector('.pre-l-cantidad')?.value || '0'),
            precio_unitario: parseFloat(row.querySelector('.pre-l-precio')?.value || '0'),
            iva_porcentaje: parseFloat(row.querySelector('.pre-l-iva')?.value || '0'),
        }));
    }

    function calcularTotales() {
        let base = 0;
        let iva = 0;
        document.querySelectorAll('#pre-lineas .factura-linea').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.pre-l-cantidad')?.value || '0');
            const precio = parseFloat(row.querySelector('.pre-l-precio')?.value || '0');
            const ivaPct = parseFloat(row.querySelector('.pre-l-iva')?.value || '0');
            const subtotal = Math.max(cantidad, 0) * Math.max(precio, 0);
            const ivaImporte = subtotal * (Math.max(ivaPct, 0) / 100);
            base += subtotal;
            iva += ivaImporte;
            const totalEl = row.querySelector('.factura-linea-total strong');
            if (totalEl) totalEl.textContent = money(subtotal + ivaImporte);
        });
        setText('pre-base', money(base));
        setText('pre-iva', money(iva));
        setText('pre-total', money(base + iva));
    }

    async function guardar(e) {
        e.preventDefault();
        limpiarErrores();
        const payload = {
            contacto_id: document.getElementById('pre-contacto')?.value,
            estado: document.getElementById('pre-estado')?.value,
            fecha_emision: document.getElementById('pre-fecha-emision')?.value,
            fecha_validez: document.getElementById('pre-fecha-validez')?.value,
            notas: document.getElementById('pre-notas')?.value.trim(),
            lineas: leerLineas(),
        };
        if (!validar(payload)) return;

        try {
            const url = '/api/presupuestos.php' + (editId ? '?id=' + encodeURIComponent(editId) : '');
            const r = await fetchSeguro(url, {
                method: editId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast(editId ? 'Presupuesto actualizado' : 'Presupuesto creado', 'success');
            cerrarForm();
            cargar();
        } catch (err) {
            manejarApiError(err, 'Error al guardar presupuesto');
        }
    }

    function validar(payload) {
        let valido = true;
        if (!payload.contacto_id) { error('err-pre-contacto', 'Selecciona un contacto'); valido = false; }
        if (!payload.fecha_emision) { error('err-pre-fecha-emision', 'La fecha de emision es obligatoria'); valido = false; }
        if (payload.fecha_validez && payload.fecha_validez < payload.fecha_emision) {
            error('err-pre-fecha-validez', 'No puede ser anterior a la emision'); valido = false;
        }
        if (!payload.lineas.length) { error('err-pre-lineas', 'Anade al menos una linea'); valido = false; }
        payload.lineas.forEach((l, i) => {
            if (!l.concepto || l.cantidad <= 0 || l.precio_unitario < 0 || l.iva_porcentaje < 0 || l.iva_porcentaje > 100) {
                error('err-pre-lineas', `Revisa la linea ${i + 1}: concepto, cantidad, precio e IVA`);
                valido = false;
            }
        });
        return valido;
    }

    async function verDetalle(id) {
        try {
            const r = await fetchSeguro('/api/presupuestos.php?id=' + encodeURIComponent(id));
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            detalleActual = d.data;
            renderDetalle(d.data);
        } catch (e) {
            manejarApiError(e, 'Error al cargar detalle');
        }
    }

    function renderDetalle(p) {
        setText('pre-det-numero', p.numero);
        setText('pre-det-contacto', nombreContacto(p));
        setText('pre-det-fechas', `${formatFecha(p.fecha_emision)} / ${formatFecha(p.fecha_validez)}`);
        setText('pre-det-total', money(p.total));
        const estadoEl = document.getElementById('pre-det-estado');
        if (estadoEl) estadoEl.innerHTML = badgeEstado(p.estado);
        const lineas = document.getElementById('pre-det-lineas');
        if (lineas) {
            lineas.innerHTML = (p.lineas || []).map(l => `
                <div class="factura-det-linea">
                    <div>
                        <strong>${esc(l.concepto)}</strong>
                        <small>${num(l.cantidad)} x ${money(l.precio_unitario)} - IVA ${num(l.iva_porcentaje)}%</small>
                    </div>
                    <span>${money(l.total_linea)}</span>
                </div>`).join('');
        }
        const notasBloque = document.getElementById('pre-det-notas-bloque');
        setText('pre-det-notas', p.notas || '');
        if (notasBloque) notasBloque.style.display = p.notas ? '' : 'none';

        const btnConvertir = document.getElementById('pre-det-convertir');
        const avisoConvertido = document.getElementById('pre-det-convertido-aviso');
        if (p.factura_id) {
            if (btnConvertir) btnConvertir.style.display = 'none';
            if (avisoConvertido) avisoConvertido.style.display = '';
            setText('pre-det-factura-numero', p.factura_numero || '');
        } else {
            if (btnConvertir) btnConvertir.style.display = '';
            if (avisoConvertido) avisoConvertido.style.display = 'none';
        }

        const linkBloque = document.getElementById('pre-det-link-bloque');
        const linkInput = document.getElementById('pre-det-link-input');
        if (p.token_confirmacion && !p.factura_id) {
            if (linkBloque) linkBloque.style.display = '';
            if (linkInput) linkInput.value = `${location.origin}/presupuesto-confirmar.php?token=${p.token_confirmacion}`;
        } else if (linkBloque) {
            linkBloque.style.display = 'none';
        }

        document.getElementById('presupuesto-detalle-overlay')?.classList.add('active');
        document.body.classList.add('detalle-open');
    }

    async function copiarLinkConfirmacion() {
        const input = document.getElementById('pre-det-link-input');
        if (!input || !input.value) return;
        try {
            await navigator.clipboard.writeText(input.value);
            mostrarToast('Enlace copiado', 'success');
        } catch {
            input.select();
            document.execCommand('copy');
            mostrarToast('Enlace copiado', 'success');
        }
    }

    function cerrarDetalle() {
        document.getElementById('presupuesto-detalle-overlay')?.classList.remove('active');
        document.body.classList.remove('detalle-open');
    }

    function descargarPdf(id) {
        window.open('/api/presupuesto_pdf.php?id=' + encodeURIComponent(id), '_blank');
    }

    async function enviarEmailPresupuesto(id, btn = null) {
        const original = btn?.innerHTML;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        try {
            const r = await fetchSeguro('/api/presupuesto_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
            });
            const d = await r.json();
            if (!d.ok) {
                mostrarToast(d.error || 'No se pudo enviar el presupuesto', 'error');
                return;
            }
            mostrarToast('Presupuesto enviado por email', 'success');
            cargar();
            if (detalleActual && String(detalleActual.id_presupuesto) === String(id)) verDetalle(id);
        } catch (e) {
            manejarApiError(e, 'Error al enviar presupuesto');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }
    }

    async function convertirEnFactura(id) {
        mostrarConfirm(
            'Convertir en factura',
            'Se creara una factura nueva con las mismas lineas y el mismo contacto. Esta accion no se puede deshacer.',
            async () => {
                try {
                    const r = await fetchSeguro('/api/presupuestos.php?id=' + encodeURIComponent(id) + '&action=convertir', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({}),
                    });
                    const d = await r.json();
                    if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                    mostrarToast(`Factura ${d.data.factura_numero} creada`, 'success');
                    cerrarDetalle();
                    cargar();
                } catch (e) {
                    manejarApiError(e, 'Error al convertir el presupuesto');
                }
            }
        );
    }

    function confirmarEliminar(id, numero) {
        mostrarConfirm('Eliminar presupuesto', `Se eliminara ${numero}. Esta accion no se puede deshacer.`, async () => {
            try {
                const r = await fetchSeguro('/api/presupuestos.php?id=' + encodeURIComponent(id), { method: 'DELETE' });
                const d = await r.json();
                if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                mostrarToast('Presupuesto eliminado', 'success');
                cerrarDetalle();
                cargar();
            } catch (e) {
                manejarApiError(e, 'Error al eliminar presupuesto');
            }
        });
    }

    function badgeEstado(estadoVal) {
        return `<span class="factura-badge presupuesto-${escAttr(estadoVal)}">${estados[estadoVal] || estadoVal}</span>`;
    }

    function nombreContacto(p) {
        return `${p.contacto_nombre || ''} ${p.contacto_apellidos || ''}`.trim() || 'Contacto';
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
    function limpiarErrores() { document.querySelectorAll('#presupuestos-form .form-error').forEach(el => { el.textContent = ''; }); }

    return { init };
})();
