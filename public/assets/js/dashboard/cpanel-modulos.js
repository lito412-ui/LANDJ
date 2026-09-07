const Modulos = (() => {
    let initDone = false;

    const iconos = {
        statistics: 'fa-chart-bar',
        contactos: 'fa-address-book',
        leads: 'fa-funnel-dollar',
        oportunidades: 'fa-handshake',
        presupuestos: 'fa-file-signature',
        productos: 'fa-boxes',
        proveedores: 'fa-truck',
        facturas: 'fa-file-invoice-dollar',
        'file-manager': 'fa-folder',
        ftp: 'fa-server',
        ssl: 'fa-lock',
        security: 'fa-shield-virus',
        firewall: 'fa-fire',
        email: 'fa-envelope',
        domains: 'fa-globe',
    };

    function init() {
        if (initDone) { cargar(); return; }
        initDone = true;
        cargar();
    }

    async function cargar() {
        const cont = document.getElementById('modulos-categorias');
        if (!cont) return;
        cont.innerHTML = '<div class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

        try {
            const r = await fetchSeguro('/api/modulos_visibilidad.php');
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            render(d.data);
        } catch (e) {
            manejarApiError(e, 'Error al cargar los módulos');
        }
    }

    function render(lista) {
        const cont = document.getElementById('modulos-categorias');
        if (!cont) return;

        const categorias = {};
        lista.forEach(m => {
            (categorias[m.categoria] ??= []).push(m);
        });

        cont.innerHTML = Object.entries(categorias).map(([categoria, modulos]) => {
            const todosVisibles = modulos.every(m => m.visible);
            const algunoVisible = modulos.some(m => m.visible);
            return `
            <div class="content-card modulos-categoria-card">
                <div class="card-header modulos-categoria-header">
                    <h3 class="card-title">${esc(categoria)}</h3>
                    <label class="toggle-switch modulos-toggle-categoria"
                           title="Mostrar/ocultar toda la categoría de una vez">
                        <input type="checkbox" data-categoria="${esc(categoria)}" ${todosVisibles ? 'checked' : ''}
                               data-indeterminado="${algunoVisible && !todosVisibles ? '1' : '0'}">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="modulos-lista">
                    ${modulos.map(m => `
                        <div class="modulos-fila">
                            <div class="modulos-fila-info">
                                <span class="modulos-icono"><i class="fas ${iconos[m.modulo] || 'fa-square'}"></i></span>
                                <span class="modulos-etiqueta">${esc(m.etiqueta)}</span>
                            </div>
                            <label class="toggle-switch" title="${m.visible ? 'Visible para usuarios' : 'Oculto para usuarios'}">
                                <input type="checkbox" data-modulo="${esc(m.modulo)}" data-categoria="${esc(categoria)}" ${m.visible ? 'checked' : ''}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        }).join('');

        cont.querySelectorAll('input[data-modulo]').forEach(input => {
            input.addEventListener('change', () => cambiarVisibilidad(input));
        });

        cont.querySelectorAll('input[data-categoria]:not([data-modulo])').forEach(input => {
            input.indeterminate = input.dataset.indeterminado === '1';
            input.addEventListener('change', () => cambiarCategoriaCompleta(input));
        });
    }

    async function cambiarVisibilidad(input) {
        const modulo = input.dataset.modulo;
        const visible = input.checked;
        input.disabled = true;

        try {
            const r = await fetchSeguro(`/api/modulos_visibilidad.php?modulo=${encodeURIComponent(modulo)}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ visible }),
            });
            const d = await r.json();
            if (!d.ok) {
                mostrarToast(d.error, 'error');
                input.checked = !visible; // revertir
                return;
            }
            mostrarToast(visible ? 'Módulo visible para tus usuarios' : 'Módulo oculto para tus usuarios', 'success');
            actualizarToggleCategoria(input.dataset.categoria);
        } catch (e) {
            manejarApiError(e, 'Error al guardar el cambio');
            input.checked = !visible;
        } finally {
            input.disabled = false;
        }
    }

    /** Muestra/oculta TODOS los módulos de una categoría de una sola vez. */
    async function cambiarCategoriaCompleta(input) {
        const categoria = input.dataset.categoria;
        const visible = input.checked;
        const cont = document.getElementById('modulos-categorias');
        const filas = [...cont.querySelectorAll(`input[data-modulo][data-categoria="${CSS.escape(categoria)}"]`)];
        const aCambiar = filas.filter(f => f.checked !== visible);

        input.indeterminate = false;
        input.disabled = true;
        filas.forEach(f => { f.disabled = true; });

        try {
            const resultados = await Promise.all(aCambiar.map(f =>
                fetchSeguro(`/api/modulos_visibilidad.php?modulo=${encodeURIComponent(f.dataset.modulo)}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ visible }),
                }).then(r => r.json())
            ));

            const fallos = resultados.filter(d => !d.ok);
            if (fallos.length) {
                mostrarToast(`No se pudieron actualizar ${fallos.length} módulo(s)`, 'error');
                cargar(); // recarga desde el servidor para reflejar el estado real
                return;
            }

            filas.forEach(f => { f.checked = visible; });
            mostrarToast(
                visible ? 'Categoría completa visible para tus usuarios' : 'Categoría completa oculta para tus usuarios',
                'success'
            );
        } catch (e) {
            manejarApiError(e, 'Error al guardar los cambios');
            cargar();
        } finally {
            input.disabled = false;
            filas.forEach(f => { f.disabled = false; });
        }
    }

    /** Recalcula si el interruptor maestro de una categoría debe estar en on/off/mixto. */
    function actualizarToggleCategoria(categoria) {
        const cont = document.getElementById('modulos-categorias');
        const filas = [...cont.querySelectorAll(`input[data-modulo][data-categoria="${CSS.escape(categoria)}"]`)];
        const master = cont.querySelector(`input[data-categoria="${CSS.escape(categoria)}"]:not([data-modulo])`);
        if (!master || !filas.length) return;

        const todos = filas.every(f => f.checked);
        const alguno = filas.some(f => f.checked);
        master.checked = todos;
        master.indeterminate = alguno && !todos;
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    return { init };
})();
