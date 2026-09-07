const Avisos = (() => {
    let initDone = false;
    let usuariosCache = [];

    const tipoInfo = {
        info:    { icono: 'fa-circle-info',            clase: 'aviso-info' },
        exito:   { icono: 'fa-circle-check',            clase: 'aviso-exito' },
        aviso:   { icono: 'fa-triangle-exclamation',    clase: 'aviso-aviso' },
        urgente: { icono: 'fa-bell',                    clase: 'aviso-urgente' },
    };

    function init() {
        if (initDone) { cargarBandeja(); return; }
        initDone = true;

        document.getElementById('avisos-nuevo-btn')?.addEventListener('click', abrirForm);
        document.getElementById('avisos-cancelar-btn')?.addEventListener('click', cerrarForm);
        document.getElementById('avisos-form')?.addEventListener('submit', enviar);
        document.getElementById('avisos-marcar-todas-btn')?.addEventListener('click', marcarTodasLeidas);
        document.getElementById('avisos-ver-enviados-btn')?.addEventListener('click', abrirEnviados);

        document.getElementById('av-destinatarios-modo')?.addEventListener('change', (e) => {
            const grupo = document.getElementById('av-usuarios-grupo');
            if (grupo) grupo.style.display = e.target.value === 'especificos' ? '' : 'none';
        });

        cargarBandeja();
    }

    async function cargarBandeja() {
        const cont = document.getElementById('avisos-bandeja');
        if (!cont) return;
        cont.innerHTML = '<div class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

        try {
            const r = await fetchSeguro('/api/avisos.php');
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            renderBandeja(d.data);
            actualizarBadgeAvisos(d.meta?.no_leidos ?? 0);
        } catch (e) {
            manejarApiError(e, 'Error al cargar los avisos');
        }
    }

    function renderBandeja(lista) {
        const cont = document.getElementById('avisos-bandeja');
        if (!cont) return;

        if (!lista.length) {
            cont.innerHTML = `
                <div class="crm-empty">
                    <i class="fas fa-bullhorn"></i>
                    <p>No tienes avisos todavía.</p>
                </div>`;
            return;
        }

        cont.innerHTML = lista.map(a => {
            const info = tipoInfo[a.tipo] || tipoInfo.info;
            const noLeido = !a.leido_at;
            return `
                <div class="aviso-item ${info.clase} ${noLeido ? 'aviso-no-leido' : ''}">
                    <div class="aviso-icono"><i class="fas ${info.icono}"></i></div>
                    <div class="aviso-cuerpo">
                        <div class="aviso-cabecera">
                            <strong>${esc(a.titulo)}</strong>
                            ${noLeido ? '<span class="aviso-punto-nuevo"></span>' : ''}
                        </div>
                        <p>${esc(a.mensaje)}</p>
                        <div class="aviso-meta">
                            <span>De ${esc(a.remitente_nombre)}</span>
                            <span>${formatFecha(a.created_at)}</span>
                        </div>
                    </div>
                    ${noLeido ? `<button class="btn-link" data-marcar="${a.id_aviso}">Marcar leído</button>` : ''}
                </div>`;
        }).join('');

        cont.querySelectorAll('[data-marcar]').forEach(btn => {
            btn.addEventListener('click', () => marcarLeido(btn.dataset.marcar));
        });
    }

    async function marcarLeido(id) {
        try {
            const r = await fetchSeguro(`/api/avisos.php?id=${id}`, { method: 'PUT' });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            cargarBandeja();
        } catch (e) {
            manejarApiError(e, 'Error al marcar como leído');
        }
    }

    async function marcarTodasLeidas() {
        try {
            const r = await fetchSeguro('/api/avisos.php?action=marcar_todas_leidas', { method: 'POST' });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast('Avisos marcados como leídos', 'success');
            cargarBandeja();
        } catch (e) {
            manejarApiError(e, 'Error al marcar los avisos');
        }
    }

    async function abrirForm() {
        document.getElementById('avisos-enviados-panel')?.classList.remove('active');
        document.getElementById('avisos-form')?.reset();
        document.querySelectorAll('#avisos-form .form-error').forEach(el => { el.textContent = ''; });
        document.getElementById('av-usuarios-grupo').style.display = 'none';

        if (!usuariosCache.length) {
            try {
                const r = await fetchSeguro('/api/avisos.php?vista=usuarios');
                const d = await r.json();
                if (d.ok) usuariosCache = d.data;
            } catch (e) {
                manejarApiError(e, 'Error al cargar usuarios');
            }
        }
        const lista = document.getElementById('av-usuarios-lista');
        if (lista) {
            lista.innerHTML = usuariosCache.map(u => `
                <label class="avisos-usuario-check">
                    <input type="checkbox" value="${u.id_usuario}">
                    <span>${esc(u.nombre)} <small>(${u.rol})</small></span>
                </label>
            `).join('');
        }

        document.getElementById('avisos-form-panel')?.classList.add('active');
        document.getElementById('avisos-form-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cerrarForm() {
        document.getElementById('avisos-form-panel')?.classList.remove('active');
    }

    async function enviar(e) {
        e.preventDefault();
        document.querySelectorAll('#avisos-form .form-error').forEach(el => { el.textContent = ''; });

        const titulo = document.getElementById('av-titulo')?.value.trim();
        const mensaje = document.getElementById('av-mensaje')?.value.trim();
        const tipo = document.getElementById('av-tipo')?.value;
        const modo = document.getElementById('av-destinatarios-modo')?.value;

        let valido = true;
        if (!titulo) { setError('err-av-titulo', 'El título es obligatorio'); valido = false; }
        if (!mensaje) { setError('err-av-mensaje', 'El mensaje es obligatorio'); valido = false; }

        let destinatarios = 'todos';
        if (modo === 'especificos') {
            const ids = [...document.querySelectorAll('#av-usuarios-lista input:checked')].map(i => parseInt(i.value));
            if (!ids.length) { setError('err-av-destinatarios', 'Selecciona al menos un usuario'); valido = false; }
            destinatarios = ids;
        }
        if (!valido) return;

        const btn = document.getElementById('avisos-enviar-btn');
        if (btn) btn.disabled = true;
        try {
            const r = await fetchSeguro('/api/avisos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ titulo, mensaje, tipo, destinatarios }),
            });
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            mostrarToast(`Aviso enviado a ${d.data.destinatarios} usuario(s)`, 'success');
            cerrarForm();
            cargarBandeja();
        } catch (err) {
            manejarApiError(err, 'Error al enviar el aviso');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async function abrirEnviados() {
        document.getElementById('avisos-form-panel')?.classList.remove('active');
        const panel = document.getElementById('avisos-enviados-panel');
        panel?.classList.add('active');
        panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });

        const cont = document.getElementById('avisos-enviados-lista');
        if (cont) cont.innerHTML = '<div class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

        try {
            const r = await fetchSeguro('/api/avisos.php?vista=enviados');
            const d = await r.json();
            if (!d.ok) { mostrarToast(d.error, 'error'); return; }
            renderEnviados(d.data);
        } catch (e) {
            manejarApiError(e, 'Error al cargar los avisos enviados');
        }
    }

    function renderEnviados(lista) {
        const cont = document.getElementById('avisos-enviados-lista');
        if (!cont) return;
        if (!lista.length) {
            cont.innerHTML = '<div class="crm-empty"><i class="fas fa-paper-plane"></i><p>Todavía no se ha enviado ningún aviso.</p></div>';
            return;
        }
        cont.innerHTML = lista.map(a => {
            const info = tipoInfo[a.tipo] || tipoInfo.info;
            return `
                <div class="aviso-item ${info.clase}">
                    <div class="aviso-icono"><i class="fas ${info.icono}"></i></div>
                    <div class="aviso-cuerpo">
                        <div class="aviso-cabecera"><strong>${esc(a.titulo)}</strong></div>
                        <p>${esc(a.mensaje)}</p>
                        <div class="aviso-meta">
                            <span>Enviado por ${esc(a.remitente_nombre)} · ${formatFecha(a.created_at)}</span>
                            <span>${a.total_leidos}/${a.total_destinatarios} leídos</span>
                        </div>
                    </div>
                    <button class="btn-icon danger" data-eliminar="${a.id_aviso}" title="Eliminar"><i class="fas fa-trash"></i></button>
                </div>`;
        }).join('');

        cont.querySelectorAll('[data-eliminar]').forEach(btn => {
            btn.addEventListener('click', () => eliminarAviso(btn.dataset.eliminar));
        });
    }

    function eliminarAviso(id) {
        mostrarConfirm('Eliminar aviso', 'Se eliminará para todos los destinatarios. Esta acción no se puede deshacer.', async () => {
            try {
                const r = await fetchSeguro(`/api/avisos.php?id=${id}`, { method: 'DELETE' });
                const d = await r.json();
                if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                mostrarToast('Aviso eliminado', 'success');
                abrirEnviados();
            } catch (e) {
                manejarApiError(e, 'Error al eliminar el aviso');
            }
        });
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    function setError(id, msg) { const el = document.getElementById(id); if (el) el.textContent = msg; }

    function formatFecha(ts) {
        if (!ts) return '';
        return new Date(ts.replace(' ', 'T')).toLocaleString('es-ES', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
        });
    }

    return { init, cargarBandeja };
})();
