// ─── Widget de Actividades (compartido) ──────────────────────────────────────

const ActividadesWidget = (() => {
    const ICONOS = { nota: 'fa-sticky-note', llamada: 'fa-phone', reunion: 'fa-users', tarea: 'fa-tasks', email: 'fa-envelope' };
    const TIPOS  = { nota: 'Nota', llamada: 'Llamada', reunion: 'Reunión', tarea: 'Tarea', email: 'Email' };

    function init({ prefix, entityType, entityId }) {
        const listaEl    = document.getElementById(`${prefix}-act-lista`);
        const formEl     = document.getElementById(`${prefix}-act-form`);
        const nuevoBtn   = document.getElementById(`${prefix}-act-nuevo-btn`);
        const guardarBtn = document.getElementById(`${prefix}-act-guardar`);
        const cancelarBtn= document.getElementById(`${prefix}-act-cancelar`);
        const tipoSel    = document.getElementById(`${prefix}-act-tipo`);
        const descEl     = document.getElementById(`${prefix}-act-desc`);
        const fechaEl    = document.getElementById(`${prefix}-act-fecha`);
        if (!listaEl) return;

        let editActId = null;

        function abrirForm(act = null) {
            editActId = act ? act.id_actividad : null;
            if (tipoSel) tipoSel.value = act?.tipo ?? 'nota';
            if (descEl)  descEl.value  = act?.descripcion ?? '';
            if (fechaEl) fechaEl.value = act?.fecha ? act.fecha.slice(0, 10) : '';
            if (formEl)  formEl.style.display = '';
            descEl?.focus();
        }

        function cerrarForm() {
            if (formEl) formEl.style.display = 'none';
            if (descEl) descEl.value = '';
            if (fechaEl) fechaEl.value = '';
            editActId = null;
        }

        async function guardar() {
            const desc = descEl?.value.trim() ?? '';
            if (!desc) { mostrarToast('La descripción es obligatoria', 'error'); descEl?.focus(); return; }
            if (guardarBtn) guardarBtn.disabled = true;
            const payload = {
                tipo:        tipoSel?.value ?? 'nota',
                descripcion: desc,
                fecha:       fechaEl?.value || null,
                [`${entityType}_id`]: entityId,
            };
            const url    = editActId ? `/api/actividades.php?id=${editActId}` : '/api/actividades.php';
            const method = editActId ? 'PUT' : 'POST';
            try {
                const r = await fetchSeguro(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify(payload),
                });
                const d = await r.json();
                if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                mostrarToast(editActId ? 'Actividad actualizada' : 'Actividad registrada', 'success');
                cerrarForm();
                cargar();
            } catch { mostrarToast('Error al guardar', 'error'); }
            finally { if (guardarBtn) guardarBtn.disabled = false; }
        }

        function eliminar(id) {
            mostrarConfirm('Eliminar actividad', 'Esta acción no se puede deshacer.', async () => {
                try {
                    const r = await fetchSeguro(`/api/actividades.php?id=${id}`, { method: 'DELETE' });
                    const d = await r.json();
                    if (!d.ok) { mostrarToast(d.error, 'error'); return; }
                    mostrarToast('Actividad eliminada', 'success');
                    cargar();
                } catch { mostrarToast('Error al eliminar', 'error'); }
            });
        }

        async function cargar() {
            listaEl.innerHTML = '<li class="det-act-vacio"><i class="fas fa-spinner fa-spin"></i></li>';
            try {
                const r = await fetchSeguro(`/api/actividades.php?${entityType}_id=${entityId}`);
                const d = await r.json();
                if (!d.ok || !d.data?.length) {
                    listaEl.innerHTML = '<li class="det-act-vacio">Sin actividades registradas</li>';
                    return;
                }
                listaEl.innerHTML = d.data.map(a => `
                    <li class="det-act-item" data-act-id="${a.id_actividad}">
                        <span class="det-act-icono det-act-${esc(a.tipo)}">
                            <i class="fas ${ICONOS[a.tipo] || 'fa-circle'}"></i>
                        </span>
                        <div class="det-act-info">
                            <span class="det-act-desc">${esc(a.descripcion)}</span>
                            <span class="det-act-fecha">${TIPOS[a.tipo] ?? a.tipo} · ${formatFecha(a.fecha || a.created_at)}</span>
                        </div>
                        <div class="det-act-actions">
                            <button class="btn-icon-xs act-edit-btn" data-id="${a.id_actividad}" title="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon-xs danger act-del-btn" data-id="${a.id_actividad}" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </div>
                    </li>`).join('');

                const actData = d.data;
                listaEl.querySelectorAll('.act-edit-btn').forEach(btn => {
                    btn.onclick = () => {
                        const act = actData.find(a => a.id_actividad == btn.dataset.id);
                        if (act) abrirForm(act);
                    };
                });
                listaEl.querySelectorAll('.act-del-btn').forEach(btn => {
                    btn.onclick = () => eliminar(parseInt(btn.dataset.id));
                });
            } catch {
                listaEl.innerHTML = '<li class="det-act-vacio">No disponible</li>';
            }
        }

        if (nuevoBtn)    nuevoBtn.onclick    = () => { cerrarForm(); abrirForm(); };
        if (cancelarBtn) cancelarBtn.onclick = cerrarForm;
        if (guardarBtn)  guardarBtn.onclick  = guardar;

        cerrarForm();
        cargar();
    }

    function esc(str) {
        const el = document.createElement('div');
        el.textContent = String(str ?? '');
        return el.innerHTML;
    }

    function formatFecha(ts) {
        if (!ts) return '—';
        return new Date(ts).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    return { init };
})();
