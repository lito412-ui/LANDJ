const Backups = (() => {
    let _initialized = false;

    function init() {
        if (_initialized) { cargar(); return; }
        _initialized = true;
        document.getElementById('backup-crear-btn')?.addEventListener('click', confirmarCrear);
        cargar();
    }

    async function cargar() {
        setTabla('<tr><td colspan="4" class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
        try {
            const r = await fetchSeguro('/api/backups.php?t=' + Date.now());
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            renderLista(d.data);
        } catch (e) {
            manejarApiError(e, 'Error al cargar los backups');
            setTabla('<tr><td colspan="4" class="crm-empty">No se pudo cargar la lista</td></tr>');
        }
    }

    function renderLista(lista) {
        // Resumen
        const totalBytes = lista.reduce((s, b) => s + b.bytes, 0);
        setText('backup-total',  lista.length);
        setText('backup-size',   formatBytes(totalBytes));
        setText('backup-ultimo', lista.length ? lista[0].fecha : '—');

        if (!lista.length) {
            setTabla('<tr><td colspan="4" class="crm-empty"><i class="fas fa-archive"></i><p>No hay backups todavía</p></td></tr>');
            return;
        }

        setTabla(lista.map(b => `
            <tr>
                <td><i class="fas fa-file-code" style="color:#6366f1;margin-right:8px"></i>${b.nombre}</td>
                <td>${b.tamano}</td>
                <td>${b.fecha}</td>
                <td>
                    <div class="action-buttons">
                        <a href="/api/backups.php?action=descargar&archivo=${encodeURIComponent(b.nombre)}"
                           class="btn-icon" title="Descargar" download>
                            <i class="fas fa-download"></i>
                        </a>
                        <button class="btn-icon danger" title="Eliminar"
                                onclick="Backups._eliminar('${b.nombre}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join(''));
    }

    function confirmarCrear() {
        mostrarConfirm(
            'Crear Backup',
            'Se generará un dump completo de la base de datos. Puede tardar unos segundos.',
            crear,
            'Crear Backup',
            'info'
        );
    }

    async function crear() {
        const btn = document.getElementById('backup-crear-btn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...'; }
        try {
            const r = await fetchSeguro('/api/backups.php', { method: 'POST' });
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            mostrarToast(`Backup creado: ${d.data.nombre} (${d.data.tamano})`, 'success');
            cargar();
        } catch (e) {
            manejarApiError(e, 'Error al crear el backup');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plus"></i> Crear Backup'; }
        }
    }

    async function eliminar(nombre) {
        mostrarConfirm(
            'Eliminar backup',
            `¿Eliminar <strong>${nombre}</strong>? Esta acción no se puede deshacer.`,
            async () => {
                try {
                    const r = await fetchSeguro('/api/backups.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ archivo: nombre }),
                    });
                    const d = await r.json();
                    if (!d.ok) throw new Error(d.error);
                    mostrarToast('Backup eliminado', 'success');
                    cargar();
                } catch (e) {
                    manejarApiError(e, 'Error al eliminar el backup');
                }
            },
            'Eliminar',
            'danger'
        );
    }

    function setTabla(html) {
        const el = document.getElementById('backup-tbody');
        if (el) el.innerHTML = html;
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    function formatBytes(bytes) {
        if (bytes < 1024)     return bytes + ' B';
        if (bytes < 1048576)  return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    return { init, _eliminar: eliminar };
})();
