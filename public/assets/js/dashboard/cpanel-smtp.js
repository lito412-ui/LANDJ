/**
 * cpanel-smtp.js — Gestión de configuración SMTP del sistema (solo administradores)
 */
(function () {
    'use strict';

    function initSmtp() {
        const form = document.getElementById('cfg-smtp-form');
        if (!form) return;

        const hostEl     = document.getElementById('cfg-smtp-host');
        const portEl     = document.getElementById('cfg-smtp-port');
        const encEl      = document.getElementById('cfg-smtp-encryption');
        const userEl     = document.getElementById('cfg-smtp-user');
        const passEl     = document.getElementById('cfg-smtp-pass');
        const fromEl     = document.getElementById('cfg-smtp-from');
        const testBtn    = document.getElementById('cfg-smtp-test');
        const guardarBtn = document.getElementById('cfg-smtp-guardar');
        const msgEl      = document.getElementById('cfg-smtp-msg');

        function notify(text, ok) {
            if (typeof mostrarToast === 'function') {
                mostrarToast(text, ok ? 'success' : 'error');
            }
            if (msgEl) {
                msgEl.textContent = text;
                msgEl.style.display = 'block';
                msgEl.className = 'form-alert ' + (ok ? 'form-alert-ok' : 'form-alert-err');
                setTimeout(() => { msgEl.style.display = 'none'; }, 6000);
            }
        }

        async function doFetch(url, options = {}) {
            if (typeof fetchSeguro === 'function') {
                return fetchSeguro(url, options);
            }
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const headers = { ...(options.headers || {}) };
            if (['POST', 'PUT', 'DELETE', 'PATCH'].includes((options.method || 'GET').toUpperCase())) {
                headers['X-CSRF-Token'] = csrf;
            }
            return fetch(url, { ...options, headers, credentials: 'same-origin' });
        }

        // Cargar configuración actual
        async function cargarSmtp() {
            try {
                const res = await doFetch('/api/system_config.php?accion=smtp');
                const data = await res.json();
                if (!data.ok) return;

                const d = data.data;
                if (hostEl) hostEl.value = d.smtp_host || '';
                if (portEl) portEl.value = d.smtp_port || '587';
                if (userEl) userEl.value = d.smtp_user || '';
                if (fromEl) fromEl.value = d.smtp_from || '';
                if (encEl && d.smtp_encryption) encEl.value = d.smtp_encryption;
                if (passEl) {
                    passEl.placeholder = d.has_password
                        ? '•••••••••••••••• (déjalo vacío para mantener la actual)'
                        : 'Introduce la contraseña o contraseña de aplicación';
                }
            } catch (err) {
                console.error('[cpanel-smtp] Error al cargar configuración:', err);
            }
        }

        // Guardar configuración
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!guardarBtn) return;

            const host = hostEl.value.trim();
            const user = userEl.value.trim();
            if (!host) {
                notify('Por favor introduce el servidor SMTP (host)', false);
                hostEl.focus();
                return;
            }
            if (!user) {
                notify('Por favor introduce el usuario SMTP', false);
                userEl.focus();
                return;
            }

            guardarBtn.disabled = true;
            guardarBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

            try {
                const res = await doFetch('/api/system_config.php?accion=smtp', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        smtp_host:       host,
                        smtp_port:       parseInt(portEl.value) || 587,
                        smtp_user:       user,
                        smtp_pass:       passEl.value.trim(),
                        smtp_from:       fromEl.value.trim(),
                        smtp_encryption: encEl ? encEl.value : 'tls'
                    })
                });

                const data = await res.json();
                if (data.ok) {
                    notify('Configuración SMTP guardada con éxito', true);
                    passEl.value = '';
                    cargarSmtp();
                } else {
                    notify(data.error || 'Error al guardar la configuración', false);
                }
            } catch (err) {
                notify('Error de conexión: ' + err.message, false);
            } finally {
                guardarBtn.disabled = false;
                guardarBtn.innerHTML = '<i class="fas fa-save"></i> Guardar configuración SMTP';
            }
        });

        // Probar envío de email
        testBtn?.addEventListener('click', async () => {
            const defaultEmail = userEl.value || (perfilData?.email || '');
            const destino = prompt('Introduce el correo de destino para la prueba:', defaultEmail);
            if (!destino || !destino.trim()) return;

            testBtn.disabled = true;
            testBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando prueba...';

            try {
                const res = await doFetch('/api/system_config.php?accion=smtp-test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ destino: destino.trim() })
                });

                const data = await res.json();
                if (data.ok) {
                    notify(data.data?.message || 'Email de prueba enviado con éxito', true);
                } else {
                    notify(data.error || 'Error en la prueba SMTP', false);
                }
            } catch (err) {
                notify('Error al conectar con el servidor: ' + err.message, false);
            } finally {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar email de prueba';
            }
        });

        cargarSmtp();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSmtp);
    } else {
        initSmtp();
    }
})();
