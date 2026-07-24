// ─── Módulo Auditoría ─────────────────────────────────────────────────────────

const Auditoria = (() => {
    let _estado = { tabla: '', accion: '', offset: 0, limite: 50 };
    let _total  = 0;
    let _initialized = false;

    function init() {
        if (_initialized) { cargar(); return; }
        _initialized = true;

        document.getElementById('audit-filtro-tabla')
            ?.addEventListener('change', (e) => {
                _estado.tabla  = e.target.value;
                _estado.offset = 0;
                cargar();
            });

        document.getElementById('audit-filtro-accion')
            ?.addEventListener('change', (e) => {
                _estado.accion = e.target.value;
                _estado.offset = 0;
                cargar();
            });

        cargar();
    }

    async function cargar() {
        const tbody = document.getElementById('audit-tbody');
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>`;

        try {
            const params = new URLSearchParams();
            if (_estado.tabla)  params.set('tabla',  _estado.tabla);
            if (_estado.accion) params.set('accion', _estado.accion);
            params.set('limite', _estado.limite);
            params.set('offset', _estado.offset);

            const r = await fetchSeguro('/api/auditoria.php?' + params);
            const d = await r.json();
            if (d.ok) {
                _total = d.total ?? 0;
                renderTabla(d.data);
                renderPaginacion();
            } else {
                mostrarToast(d.error, 'error');
            }
        } catch (e) {
            manejarApiError(e, 'Error al cargar auditoría');
        }
    }

    function renderTabla(lista) {
        const tbody = document.getElementById('audit-tbody');
        if (!tbody) return;
        if (!lista.length) {
            tbody.innerHTML = `
                <tr><td colspan="6" class="crm-empty">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No hay registros de auditoría para los filtros seleccionados</p>
                </td></tr>`;
            return;
        }

        tbody.innerHTML = lista.map((row, idx) => {
            const hasDiff = row.datos_antes || row.datos_despues;
            return `
                <tr class="audit-row">
                    <td class="audit-fecha">${formatFecha(row.created_at)}</td>
                    <td>
                        <div class="crm-nombre-cell">
                            <span class="crm-avatar audit-av">${esc(row.usuario_nombre ?? '?').slice(0,2).toUpperCase()}</span>
                            <span>${esc(row.usuario_nombre ?? '—')}</span>
                        </div>
                    </td>
                    <td><span class="audit-tabla-badge">${esc(row.tabla)}</span></td>
                    <td class="audit-id">#${row.registro_id}</td>
                    <td>${badgeAccion(row.accion)}</td>
                    <td>
                        ${hasDiff
                            ? `<button class="btn-icon audit-toggle" data-idx="${idx}" title="Ver cambios"><i class="fas fa-code-branch"></i></button>`
                            : '<span class="audit-nodiff">—</span>'}
                    </td>
                </tr>
                ${hasDiff ? `
                <tr class="audit-diff-row" id="audit-diff-${idx}" style="display:none">
                    <td colspan="6">
                        <div class="audit-diff-panel">
                            ${renderDiff(row.accion, row.datos_antes, row.datos_despues)}
                        </div>
                    </td>
                </tr>` : ''}`;
        }).join('');

        tbody.querySelectorAll('.audit-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const det = document.getElementById(`audit-diff-${btn.dataset.idx}`);
                if (!det) return;
                const abierto = det.style.display !== 'none';
                det.style.display = abierto ? 'none' : '';
                btn.classList.toggle('active', !abierto);
                btn.querySelector('i').className = abierto ? 'fas fa-code-branch' : 'fas fa-times';
            });
        });
    }

    function badgeAccion(accion) {
        const map = {
            crear:    ['badge badge-success', 'Crear'],
            editar:   ['badge badge-info',    'Editar'],
            eliminar: ['badge badge-danger',  'Eliminar'],
        };
        const [cls, label] = map[accion] ?? ['badge', accion];
        return `<span class="${cls}">${label}</span>`;
    }

    function renderDiff(accion, antes, despues) {
        if (accion === 'crear')    return diffTable(null,  despues);
        if (accion === 'eliminar') return diffTable(antes, null);
        return diffTable(antes, despues);
    }

    function diffTable(antes, despues) {
        const SKIP = new Set(['datos_antes', 'datos_despues', 'id_contacto', 'id_lead',
                              'id_oportunidad', 'id_actividad', 'id_usuario']);
        const keys = [...new Set([...Object.keys(antes ?? {}), ...Object.keys(despues ?? {})])]
            .filter(k => !SKIP.has(k));

        // Para editar, solo mostrar campos que cambiaron
        const filas = keys.filter(k => {
            if (!antes || !despues) return true;
            return JSON.stringify(antes[k]) !== JSON.stringify(despues[k]);
        });

        if (!filas.length) return `<p class="audit-nodiff-msg">Sin diferencias registradas</p>`;

        const encabezado = antes && despues
            ? `<tr><th>Campo</th><th>Antes</th><th>Después</th></tr>`
            : antes
                ? `<tr><th>Campo</th><th>Valor eliminado</th></tr>`
                : `<tr><th>Campo</th><th>Valor creado</th></tr>`;

        const rows = filas.map(k => {
            const a = antes  ? valStr(antes[k])  : null;
            const b = despues ? valStr(despues[k]) : null;
            if (antes && despues) {
                return `<tr>
                    <td class="diff-key">${esc(k)}</td>
                    <td class="diff-antes">${a ?? '<em>—</em>'}</td>
                    <td class="diff-despues">${b ?? '<em>—</em>'}</td>
                </tr>`;
            }
            return `<tr>
                <td class="diff-key">${esc(k)}</td>
                <td>${(a ?? b) ?? '<em>—</em>'}</td>
            </tr>`;
        }).join('');

        return `<table class="audit-diff-table"><thead>${encabezado}</thead><tbody>${rows}</tbody></table>`;
    }

    function valStr(v) {
        if (v === null || v === undefined) return null;
        if (typeof v === 'object') return `<code>${esc(JSON.stringify(v))}</code>`;
        const s = String(v);
        return s === '' ? '<em>(vacío)</em>' : esc(s);
    }

    function renderPaginacion() {
        const cont = document.getElementById('audit-paginacion');
        if (!cont) return;

        const { offset, limite } = _estado;
        const filas   = document.querySelectorAll('#audit-tbody .audit-row').length;
        const desde   = _total === 0 ? 0 : offset + 1;
        const hasta   = offset + filas;
        const hasPrev = offset > 0;
        const hasNext = (offset + limite) < _total;

        if (!hasPrev && !hasNext) {
            cont.innerHTML = _total > 0
                ? `<div class="pag-info">Mostrando ${desde}–${hasta} de ${_total}</div>`
                : '';
            return;
        }

        cont.innerHTML = `
            <div class="pag-info">Mostrando ${desde}–${hasta} de ${_total}</div>
            <div class="pag-controles">
                <button class="pag-btn" id="audit-prev" ${hasPrev ? '' : 'disabled'}>
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <button class="pag-btn" id="audit-next" ${hasNext ? '' : 'disabled'}>
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>`;

        document.getElementById('audit-prev')
            ?.addEventListener('click', () => { _estado.offset = Math.max(0, offset - limite); cargar(); });
        document.getElementById('audit-next')
            ?.addEventListener('click', () => { _estado.offset = offset + limite; cargar(); });
    }

    function formatFecha(ts) {
        if (!ts) return '—';
        const d = new Date(ts);
        return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
             + ' ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    return { init };
})();
