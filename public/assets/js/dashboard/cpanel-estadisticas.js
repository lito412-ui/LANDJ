const Estadisticas = (() => {
    let _initialized = false;

    function init() {
        _initialized = true;
        cargar();
    }

    async function cargar() {
        const cont = document.getElementById('stats-charts');
        if (cont) cont.innerHTML = '<div class="crm-loading"><i class="fas fa-spinner fa-spin"></i> Cargando estadísticas...</div>';
        try {
            const r = await fetchSeguro('/api/estadisticas.php?t=' + Date.now());
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            render(d.data);
        } catch (e) {
            manejarApiError(e, 'Error al cargar estadísticas');
        }
    }

    function render(data) {
        // KPI cards
        setText('stat-crm-contactos',    data.totales.contactos);
        setText('stat-crm-leads',        data.totales.leads);
        setText('stat-crm-oportunidades',data.totales.oportunidades_activas);
        setText('stat-crm-pipeline',     formatEur(data.valor_pipeline));
        setText('stat-crm-conversion',   data.tasa_conversion.toFixed(1) + '%');

        // Barra de conversión
        const barra = document.getElementById('stat-conversion-barra');
        if (barra) barra.style.width = Math.min(data.tasa_conversion, 100) + '%';

        // Gráficas de barras
        const cont = document.getElementById('stats-charts');
        if (!cont) return;

        cont.innerHTML = `
            <div class="charts-row">
                <div class="chart-card">
                    <h4 class="chart-title">Leads por estado</h4>
                    <div id="chart-leads-estado" class="chart-bars"></div>
                </div>
                <div class="chart-card">
                    <h4 class="chart-title">Oportunidades por etapa</h4>
                    <div id="chart-opor-etapa" class="chart-bars"></div>
                </div>
            </div>
            <div class="charts-row">
                <div class="chart-card chart-card--wide">
                    <h4 class="chart-title">Actividades por tipo</h4>
                    <div id="chart-act-tipo" class="chart-bars chart-bars--horizontal"></div>
                </div>
            </div>
            <div class="charts-row">
                <div class="chart-card chart-card--wide">
                    <h4 class="chart-title">Ranking de comerciales</h4>
                    <div id="ranking-comerciales"></div>
                </div>
            </div>`;

        renderBars('chart-leads-estado',  data.leads_por_estado,       'estado', COLORES_ESTADO);
        renderBars('chart-opor-etapa',    data.oportunidades_por_etapa,'etapa',  COLORES_ETAPA);
        renderBars('chart-act-tipo',      data.actividades_por_tipo,   'tipo',   COLORES_TIPO);
        renderRanking(data.ranking_comerciales);
    }

    function renderRanking(lista) {
        const cont = document.getElementById('ranking-comerciales');
        if (!cont) return;
        if (!lista || !lista.length) {
            cont.innerHTML = '<p class="chart-empty">Todavía no hay oportunidades ganadas ni facturas emitidas</p>';
            return;
        }
        const medallas = ['🥇', '🥈', '🥉'];
        cont.innerHTML = `
            <table class="ranking-tabla">
                <thead>
                    <tr>
                        <th></th>
                        <th>Comercial</th>
                        <th>Oportunidades ganadas</th>
                        <th>Valor ganado</th>
                        <th>Facturas emitidas</th>
                        <th>Facturado</th>
                    </tr>
                </thead>
                <tbody>
                    ${lista.map((u, i) => `
                        <tr>
                            <td class="ranking-puesto">${medallas[i] || (i + 1)}</td>
                            <td><strong>${esc(u.nombre)}</strong></td>
                            <td>${u.oportunidades_ganadas}</td>
                            <td>${formatEur(u.valor_ganado)}</td>
                            <td>${u.facturas_emitidas}</td>
                            <td><strong>${formatEur(u.facturado_total)}</strong></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>`;
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = String(str ?? '');
        return d.innerHTML;
    }

    function renderBars(containerId, items, labelKey, colores) {
        const cont = document.getElementById(containerId);
        if (!cont || !items.length) {
            if (cont) cont.innerHTML = '<p class="chart-empty">Sin datos</p>';
            return;
        }
        const max = Math.max(...items.map(i => +i.total), 1);
        cont.innerHTML = items.map(item => {
            const pct  = ((+item.total / max) * 100).toFixed(1);
            const color = colores[item[labelKey]] || '#94a3b8';
            const label = LABELS[item[labelKey]] || item[labelKey];
            return `
                <div class="chart-row">
                    <span class="chart-label">${label}</span>
                    <div class="chart-bar-wrap">
                        <div class="chart-bar-fill" style="width:${pct}%;background:${color}"></div>
                    </div>
                    <span class="chart-count">${item.total}</span>
                </div>`;
        }).join('');
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    function formatEur(val) {
        return new Intl.NumberFormat('es-ES', {
            style: 'currency', currency: 'EUR', maximumFractionDigits: 0
        }).format(val);
    }

    const COLORES_ESTADO = {
        nuevo:      '#3730a3',
        contactado: '#1d4ed8',
        calificado: '#92400e',
        convertido: '#15803d',
        descartado: '#b91c1c',
    };

    const COLORES_ETAPA = {
        prospecto:       '#7c3aed',
        propuesta:       '#1d4ed8',
        negociacion:     '#92400e',
        cerrada_ganada:  '#15803d',
        cerrada_perdida: '#b91c1c',
    };

    const COLORES_TIPO = {
        nota:    '#6366f1',
        llamada: '#0ea5e9',
        reunion: '#8b5cf6',
        tarea:   '#f59e0b',
        email:   '#10b981',
    };

    const LABELS = {
        nuevo: 'Nuevo', contactado: 'Contactado', calificado: 'Calificado',
        convertido: 'Convertido', descartado: 'Descartado',
        prospecto: 'Prospecto', propuesta: 'Propuesta', negociacion: 'Negociación',
        cerrada_ganada: 'Ganada', cerrada_perdida: 'Perdida',
        nota: 'Nota', llamada: 'Llamada', reunion: 'Reunión',
        tarea: 'Tarea', email: 'Email',
    };

    return { init };
})();
