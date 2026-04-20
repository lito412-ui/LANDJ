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

        _cargarDatos();
        _sincronizarTema();
    }

    function _cargarDatos() {
        if (!perfilData) return;
        const nombre = document.getElementById('cfg-nombre');
        const email  = document.getElementById('cfg-email');
        if (nombre) nombre.value = perfilData.nombre ?? '';
        if (email)  email.value  = perfilData.email  ?? '';
        _sincronizarTema();
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
        const btn = document.getElementById('theme-toggle-btn');
        if (btn) {
            btn.querySelector('i').className = tema === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            btn.title = tema === 'dark' ? 'Tema claro' : 'Tema oscuro';
        }
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

    return { init };
})();
