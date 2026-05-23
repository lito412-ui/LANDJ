// ─── Notificaciones y recordatorios ──────────────────────────────────────────
//
// Funcionamiento:
// 1. Al cargar el panel, se hace un primer fetch a /api/notificaciones.php (GET).
// 2. La respuesta trae las preferencias del usuario y la lista de pendientes.
// 3. Se actualiza el badge del icono campana, se renderiza el dropdown
//    y se programa un setInterval con la frecuencia indicada (30..300 s).
// 4. Si llegan nuevas pendientes respecto a la última lectura, se reproduce
//    sonido (si sonido=1) y se muestra Notification API (si browser_push=1).
// 5. Acciones: descartar, completar, posponer 15/60 min — actualizan vía POST.

const Notificaciones = (() => {
    let _initialized   = false;
    let _intervalId    = null;
    let _ultimosIds    = new Set();
    let _ultimoTotal   = 0;
    let _preferencias  = null;

    const ICONOS_TIPO = {
        nota:    'fa-sticky-note',
        llamada: 'fa-phone',
        reunion: 'fa-users',
        tarea:   'fa-tasks',
        email:   'fa-envelope',
    };

    function init() {
        if (_initialized) return;
        _initialized = true;

        const btn = document.getElementById('notif-btn');
        const drop = document.getElementById('notif-dropdown');
        if (!btn || !drop) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            drop.classList.toggle('show');
            // Al abrir, refrescar inmediatamente
            if (drop.classList.contains('show')) cargar();
        });

        document.addEventListener('click', (e) => {
            if (!drop.contains(e.target) && !btn.contains(e.target)) {
                drop.classList.remove('show');
            }
        });

        document.getElementById('notif-marcar-todas')?.addEventListener('click', _marcarTodas);
        document.getElementById('notif-config-btn')?.addEventListener('click', () => {
            drop.classList.remove('show');
            if (typeof navegarA === 'function') {
                navegarA('configuracion');
                setTimeout(() => {
                    document.getElementById('cfg-notif-card')?.scrollIntoView({
                        behavior: 'smooth', block: 'center',
                    });
                }, 100);
            }
        });

        cargar();
    }

    async function cargar() {
        try {
            const r = await fetchSeguro('/api/notificaciones.php?t=' + Date.now());
            if (!r.ok) return;
            const d = await r.json();
            if (!d.ok) return;

            _preferencias = d.data.preferencias;
            _reprogramarPolling();

            const pendientes = d.data.pendientes || [];
            _detectarNuevas(pendientes);
            _renderizar(pendientes);
            _actualizarBadge(pendientes.length);
        } catch (e) {
            console.error('[notificaciones]', e);
        }
    }

    function _reprogramarPolling() {
        const seg = parseInt(_preferencias?.frecuencia_segundos ?? 60);
        if (_intervalId) clearInterval(_intervalId);
        if (parseInt(_preferencias?.activas ?? 1)) {
            _intervalId = setInterval(cargar, seg * 1000);
        }
    }

    function _detectarNuevas(pendientes) {
        const nuevosIds = new Set(pendientes.map(p => p.id_actividad));
        const nuevas = pendientes.filter(p => !_ultimosIds.has(p.id_actividad));

        if (nuevas.length && _ultimosIds.size > 0) {
            if (parseInt(_preferencias?.sonido ?? 1)) _reproducirSonido();
            if (parseInt(_preferencias?.browser_push ?? 0)) {
                nuevas.forEach(_pushBrowser);
            }
            if (typeof mostrarToast === 'function') {
                mostrarToast(`Tienes ${nuevas.length} recordatorio${nuevas.length > 1 ? 's' : ''} pendiente${nuevas.length > 1 ? 's' : ''}`, 'info');
            }
        }
        _ultimosIds  = nuevosIds;
        _ultimoTotal = pendientes.length;
    }

    function _renderizar(pendientes) {
        const lista = document.getElementById('notif-lista');
        if (!lista) return;

        if (!pendientes.length) {
            lista.innerHTML = `
                <li class="notif-vacio">
                    <i class="fas fa-bell-slash"></i>
                    <span>Sin notificaciones pendientes</span>
                </li>`;
            return;
        }

        lista.innerHTML = pendientes.map(p => {
            const ico = ICONOS_TIPO[p.tipo] || 'fa-bell';
            const entidad = _resumenEntidad(p);
            const tiempo  = _formatTiempo(p.minutos_restantes);
            return `
                <li class="notif-item" data-id="${p.id_actividad}">
                    <div class="notif-icono notif-icono-${_escAttr(p.tipo)}">
                        <i class="fas ${ico}"></i>
                    </div>
                    <div class="notif-contenido">
                        <div class="notif-desc">${_esc(p.descripcion)}</div>
                        <div class="notif-meta">${_esc(entidad)} · <span class="notif-tiempo">${tiempo}</span></div>
                    </div>
                    <div class="notif-acciones">
                        <button class="notif-accion-btn" data-accion="completar" title="Marcar como hecha">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="notif-accion-btn" data-accion="posponer" title="Posponer 15 min">
                            <i class="fas fa-clock"></i>
                        </button>
                        <button class="notif-accion-btn" data-accion="descartar" title="Descartar">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </li>`;
        }).join('');

        lista.querySelectorAll('.notif-accion-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const li = btn.closest('.notif-item');
                const id = parseInt(li.dataset.id);
                const accion = btn.dataset.accion;
                await _ejecutarAccion(id, accion);
            });
        });
    }

    async function _ejecutarAccion(idActividad, accion) {
        try {
            const body = { id_actividad: idActividad, accion };
            if (accion === 'posponer') body.minutos = 15;
            const r = await fetchSeguro('/api/notificaciones.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const d = await r.json();
            if (!d.ok) {
                if (typeof mostrarToast === 'function') mostrarToast(d.error || 'Error', 'error');
                return;
            }
            const msg = accion === 'completar' ? 'Marcada como hecha'
                      : accion === 'posponer'  ? 'Pospuesta 15 minutos'
                      : 'Descartada';
            if (typeof mostrarToast === 'function') mostrarToast(msg, 'success');
            cargar();
        } catch (e) {
            if (typeof mostrarToast === 'function') mostrarToast('Error al procesar', 'error');
        }
    }

    async function _marcarTodas() {
        const items = document.querySelectorAll('.notif-item');
        if (!items.length) return;
        for (const li of items) {
            const id = parseInt(li.dataset.id);
            try {
                await fetchSeguro('/api/notificaciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_actividad: id, accion: 'descartar' }),
                });
            } catch (_) { /* ignorar */ }
        }
        if (typeof mostrarToast === 'function') mostrarToast('Todas marcadas como leídas', 'success');
        cargar();
    }

    function _actualizarBadge(n) {
        const badge = document.getElementById('notif-badge');
        const btn   = document.getElementById('notif-btn');
        if (!badge) return;
        if (n > 0) {
            badge.textContent = n > 99 ? '99+' : String(n);
            badge.style.display = '';
            btn?.classList.add('has-notif');
        } else {
            badge.style.display = 'none';
            btn?.classList.remove('has-notif');
        }
    }

    function _resumenEntidad(p) {
        if (p.contacto_nombre)     return `Contacto: ${p.contacto_nombre} ${p.contacto_apellidos || ''}`.trim();
        if (p.lead_nombre)         return `Lead: ${p.lead_nombre}`;
        if (p.oportunidad_titulo)  return `Oportunidad: ${p.oportunidad_titulo}`;
        return 'Sin entidad vinculada';
    }

    function _formatTiempo(minutos) {
        const m = parseInt(minutos);
        if (isNaN(m)) return 'ahora';
        if (m >= 0)  return 'ahora';
        const abs = Math.abs(m);
        if (abs < 60)        return `hace ${abs} min`;
        if (abs < 60 * 24)   return `hace ${Math.floor(abs / 60)} h`;
        return `hace ${Math.floor(abs / 60 / 24)} d`;
    }

    function _reproducirSonido() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.12, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            osc.connect(gain); gain.connect(ctx.destination);
            osc.start(); osc.stop(ctx.currentTime + 0.4);
        } catch (_) { /* sin audio context disponible */ }
    }

    function _pushBrowser(p) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        try {
            new Notification('Recordatorio L&J CRM', {
                body: p.descripcion,
                tag:  'crm-act-' + p.id_actividad,
            });
        } catch (_) { /* sandbox/iframe sin permiso */ }
    }

    function pedirPermisoBrowser() {
        if (!('Notification' in window)) return Promise.resolve('unsupported');
        if (Notification.permission === 'granted') return Promise.resolve('granted');
        return Notification.requestPermission();
    }

    function _esc(str) {
        const el = document.createElement('div');
        el.textContent = String(str ?? '');
        return el.innerHTML;
    }
    function _escAttr(str) {
        return String(str ?? '').replace(/[^a-z0-9_-]/gi, '');
    }

    return { init, cargar, pedirPermisoBrowser };
})();
