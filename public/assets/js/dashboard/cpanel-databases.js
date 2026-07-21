// ─── Módulo Bases de Datos ────────────────────────────────────────────────────

const Databases = (() => {
    let _initialized = false;

    function init() {
        if (_initialized) return;
        _initialized = true;
        cargar();
    }

    async function cargar() {
        try {
            const r = await fetchSeguro('/api/databases.php');
            const d = await r.json();
            if (d.ok) render(d.data);
            else mostrarToast(d.error, 'error');
        } catch (e) {
            manejarApiError(e, 'Error al cargar información de la base de datos');
        }
    }

    function render(data) {
        // Tarjetas resumen
        const summary = document.getElementById('db-summary');
        if (summary) summary.style.display = '';
        setText('db-nombre',        data.db);
        setText('db-total-tablas',  data.total_tablas);
        setText('db-total-filas',   data.total_filas.toLocaleString('es-ES'));
        setText('db-total-size',    formatBytes(data.total_bytes));

        // Tabla
        const tbody = document.getElementById('db-tbody');
        if (!tbody) return;

        if (!data.tablas.length) {
            tbody.innerHTML = `<tr><td colspan="6" class="crm-empty">
                <i class="fas fa-database"></i><p>No hay tablas en la base de datos</p>
            </td></tr>`;
            return;
        }

        tbody.innerHTML = data.tablas.map(t => `
            <tr>
                <td><strong class="db-tabla-nombre">${esc(t.nombre)}</strong></td>
                <td><span class="db-motor-badge">${esc(t.motor)}</span></td>
                <td class="db-num">${t.filas.toLocaleString('es-ES')}</td>
                <td class="db-num">${formatBytes(t.bytes)}</td>
                <td class="db-colacion">${esc(t.colacion)}</td>
                <td class="db-fecha">${t.actualizada ? formatFecha(t.actualizada) : '<span class="audit-nodiff">—</span>'}</td>
            </tr>`).join('');
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function formatFecha(ts) {
        if (!ts) return '—';
        const d = new Date(ts);
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
             + ' ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val ?? '—';
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    return { init };
})();
