const Configuracion = (() => {
    let _initialized = false;

    function init() {
        if (_initialized) { _cargarDatos(); return; }
        _initialized = true;

        document.getElementById('config-perfil-form')
            ?.addEventListener('submit', _guardarPerfil);
        document.getElementById('config-pass-form')
            ?.addEventListener('submit', _cambiarPassword);

        document.querySelectorAll('.config-pass-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                if (!input) return;
                const oculto = input.type === 'password';
                input.type = oculto ? 'text' : 'password';
                btn.querySelector('i').className = oculto ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        });

        document.getElementById('cfg-pass-nueva')?.addEventListener('input', e => {
            _actualizarFortaleza(e.target.value);
        });

        document.querySelectorAll('.config-tema-opcion').forEach(opt => {
            opt.addEventListener('click', () => _aplicarTema(opt.dataset.tema));
        });

        document.getElementById('cfg-ir-password-btn')?.addEventListener('click', () => {
            document.getElementById('cfg-pass-actual')?.focus();
            document.getElementById('config-pass-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        // Notificaciones
        document.getElementById('cfg-notif-guardar')?.addEventListener('click', _guardarNotif);
        document.getElementById('cfg-notif-test')?.addEventListener('click', _probarNotif);
        document.getElementById('cfg-notif-browser')?.addEventListener('change', _solicitarPermisoBrowser);

        _cargarDatos();
        _cargarPreferencias();
        _sincronizarTema();
    }

    function _cargarDatos() {
        if (!perfilData) return;

        const nombre = document.getElementById('cfg-nombre');
        const email  = document.getElementById('cfg-email');
        if (nombre) nombre.value = perfilData.nombre ?? '';
        if (email)  email.value  = perfilData.email  ?? '';

        _poblarResumen(perfilData);
        _sincronizarTema();
    }

    function _poblarResumen(data) {
        const avatar = document.getElementById('cfg-avatar');
        if (avatar) avatar.textContent = (data.nombre || '?').slice(0, 2).toUpperCase();

        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
        set('cfg-resumen-nombre', data.nombre);
        set('cfg-resumen-email',  data.email || 'Sin correo registrado');
        set('cfg-resumen-correo', data.email || 'Sin correo registrado');
        set('cfg-resumen-rol',    data.rol);

        const fecha = data.created_at
            ? new Date(data.created_at).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' })
            : '—';
        set('cfg-resumen-fecha', fecha);

        const badge = document.getElementById('cfg-resumen-badge');
        if (badge) {
            const esAdmin = data.rol === 'administrador';
            badge.textContent = esAdmin ? 'Admin' : 'Usuario';
            badge.className   = 'config-resumen-badge' + (esAdmin ? '' : ' rol-usuario');
        }
    }

    function _sincronizarTema() {
        const actual = localStorage.getItem('theme') ?? 'light';
        document.querySelectorAll('.config-tema-opcion').forEach(opt => {
            opt.classList.toggle('active', opt.dataset.tema === actual);
        });
    }

    function _aplicarTema(tema) {
        document.documentElement.dataset.theme = tema;
        localStorage.setItem('theme', tema);
        const oscuro   = tema === 'dark';
        const icoClass = oscuro ? 'fas fa-sun' : 'fas fa-moon';
        const titulo   = oscuro ? 'Tema claro' : 'Tema oscuro';
        [document.getElementById('theme-toggle-btn'), document.getElementById('dropdown-theme-toggle')]
            .forEach(btn => {
                if (!btn) return;
                btn.querySelector('i').className = icoClass;
                btn.title = titulo;
            });
        _sincronizarTema();
    }

    async function _guardarPerfil(e) {
        e.preventDefault();
        const nombre = document.getElementById('cfg-nombre')?.value.trim() ?? '';
        const email  = document.getElementById('cfg-email')?.value.trim()  ?? '';

        document.getElementById('cfg-err-nombre').textContent = '';
        document.getElementById('cfg-err-email').textContent  = '';

        if (!nombre) {
            document.getElementById('cfg-err-nombre').textContent = 'El nombre es obligatorio';
            document.getElementById('cfg-nombre')?.classList.add('form-input--error');
            return;
        }

        const btn = document.getElementById('cfg-perfil-btn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; }

        try {
            const r = await fetchSeguro('/api/configuracion.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre, email }),
            });
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);

            if (perfilData) {
                perfilData.nombre = d.data.nombre;
                perfilData.email  = d.data.email;
            }
            const headerUsername = document.getElementById('header-username');
            const userName = document.getElementById('user-name');
            if (headerUsername) headerUsername.textContent = d.data.nombre;
            if (userName)       userName.textContent       = d.data.nombre;

            if (perfilData) {
                _poblarResumen(perfilData);
                if (typeof _poblarDropdownHeader === 'function') _poblarDropdownHeader(perfilData);
            }

            mostrarToast('Perfil actualizado', 'success');
        } catch (err) {
            manejarApiError(err, err.message || 'Error al guardar el perfil');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios'; }
        }
    }

    async function _cambiarPassword(e) {
        e.preventDefault();
        const actual    = document.getElementById('cfg-pass-actual')?.value    ?? '';
        const nueva     = document.getElementById('cfg-pass-nueva')?.value     ?? '';
        const confirmar = document.getElementById('cfg-pass-confirmar')?.value ?? '';

        ['cfg-err-actual', 'cfg-err-nueva', 'cfg-err-confirmar'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '';
        });

        let ok = true;
        if (!actual)   { document.getElementById('cfg-err-actual').textContent   = 'Campo obligatorio'; ok = false; }
        if (!nueva)    { document.getElementById('cfg-err-nueva').textContent    = 'Campo obligatorio'; ok = false; }
        if (nueva && nueva !== confirmar) {
            document.getElementById('cfg-err-confirmar').textContent = 'Las contraseñas no coinciden';
            ok = false;
        }
        if (!ok) return;

        const btn = document.getElementById('cfg-pass-btn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cambiando...'; }

        try {
            const r = await fetchSeguro('/api/configuracion.php?accion=password', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ actual, nueva, confirmar }),
            });
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);

            mostrarToast('Contraseña actualizada', 'success');
            document.getElementById('config-pass-form')?.reset();
            document.getElementById('cfg-pass-strength').style.display = 'none';
        } catch (err) {
            manejarApiError(err, err.message || 'Error al cambiar la contraseña');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-key"></i> Cambiar contraseña'; }
        }
    }

    function _actualizarFortaleza(pass) {
        const wrap  = document.getElementById('cfg-pass-strength');
        const fill  = document.getElementById('cfg-pass-bar-fill');
        const label = document.getElementById('cfg-pass-strength-label');
        if (!wrap || !fill || !label) return;

        if (!pass) { wrap.style.display = 'none'; return; }
        wrap.style.display = '';

        let score = 0;
        if (pass.length >= 8)                          score++;
        if (pass.length >= 12)                         score++;
        if (/[A-Z]/.test(pass))                        score++;
        if (/[0-9]/.test(pass))                        score++;
        if (/[^a-zA-Z0-9]/.test(pass))                score++;

        const niveles = [
            { pct: '20%', color: '#ef4444', texto: 'Muy débil'  },
            { pct: '40%', color: '#f97316', texto: 'Débil'      },
            { pct: '60%', color: '#eab308', texto: 'Aceptable'  },
            { pct: '80%', color: '#22c55e', texto: 'Fuerte'     },
            { pct: '100%',color: '#16a34a', texto: 'Muy fuerte' },
        ];
        const n = niveles[Math.min(score, 4)];
        fill.style.width       = n.pct;
        fill.style.background  = n.color;
        label.textContent      = n.texto;
        label.style.color      = n.color;
    }

    function cargar(data) {
        _poblarResumen(data);
        _sincronizarTema();
    }

    // ─── Preferencias de notificaciones ──────────────────────────────────────

    async function _cargarPreferencias() {
        try {
            const r = await fetchSeguro('/api/preferencias.php?t=' + Date.now());
            const d = await r.json();
            if (!d.ok) return;
            _aplicarPreferenciasAlForm(d.data);
        } catch (_) { /* silencio */ }
    }

    function _aplicarPreferenciasAlForm(p) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.checked = !!parseInt(val); };
        set('cfg-notif-activas',  p.activas);
        set('cfg-notif-sonido',   p.sonido);
        set('cfg-notif-browser',  p.browser_push);

        const ant = document.getElementById('cfg-notif-antelacion');
        if (ant) ant.value = String(p.antelacion_minutos);
        const fre = document.getElementById('cfg-notif-frecuencia');
        if (fre) fre.value = String(p.frecuencia_segundos);

        _refrescarEstadoBrowserPermiso();
    }

    function _refrescarEstadoBrowserPermiso() {
        const span = document.getElementById('cfg-notif-browser-estado');
        if (!span) return;
        if (!('Notification' in window)) {
            span.textContent = '(no soportado en este navegador)';
            span.style.color = 'var(--color-danger, #ef4444)';
            return;
        }
        const perm = Notification.permission;
        if (perm === 'granted') {
            span.textContent = '(permiso concedido)';
            span.style.color = 'var(--color-success, #10b981)';
        } else if (perm === 'denied') {
            span.textContent = '(permiso bloqueado en el navegador)';
            span.style.color = 'var(--color-danger, #ef4444)';
        } else {
            span.textContent = '(pendiente de autorizar)';
            span.style.color = 'var(--color-warning, #f59e0b)';
        }
    }

    async function _solicitarPermisoBrowser(e) {
        if (!e.target.checked) return;
        if (typeof Notificaciones !== 'undefined') {
            const res = await Notificaciones.pedirPermisoBrowser();
            _refrescarEstadoBrowserPermiso();
            if (res !== 'granted') {
                e.target.checked = false;
                mostrarToast('Permiso del navegador denegado', 'error');
            }
        }
    }

    async function _guardarNotif() {
        const payload = {
            activas:             document.getElementById('cfg-notif-activas')?.checked ? 1 : 0,
            sonido:              document.getElementById('cfg-notif-sonido')?.checked ? 1 : 0,
            browser_push:        document.getElementById('cfg-notif-browser')?.checked ? 1 : 0,
            antelacion_minutos:  parseInt(document.getElementById('cfg-notif-antelacion')?.value || 30),
            frecuencia_segundos: parseInt(document.getElementById('cfg-notif-frecuencia')?.value || 60),
        };

        const btn = document.getElementById('cfg-notif-guardar');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...'; }

        try {
            const r = await fetchSeguro('/api/preferencias.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const d = await r.json();
            if (!d.ok) throw new Error(d.error);
            mostrarToast('Preferencias guardadas', 'success');
            // Recargar polling con la nueva frecuencia
            if (typeof Notificaciones !== 'undefined') Notificaciones.cargar();
        } catch (err) {
            manejarApiError(err, err.message || 'Error al guardar preferencias');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Guardar preferencias'; }
        }
    }

    function _probarNotif() {
        const sonido      = document.getElementById('cfg-notif-sonido')?.checked;
        const browserPush = document.getElementById('cfg-notif-browser')?.checked;

        mostrarToast('Esto es un ejemplo de notificación', 'info');

        if (sonido) {
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
            } catch (_) { /* sin audio */ }
        }

        if (browserPush && 'Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification('L&J CRM — prueba', {
                    body: 'Las notificaciones del navegador funcionan correctamente.',
                });
            } catch (_) { /* sandbox */ }
        }
    }

    return { init, cargar };
})();
