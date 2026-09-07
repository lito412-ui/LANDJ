// Contador regresivo de 10 minutos
let segundos = 600;

const el = document.getElementById('timer-countdown');

const intervalo = setInterval(() => {
    segundos--;

    if (segundos <= 0) {
        clearInterval(intervalo);

        if (el) {
            el.textContent = 'expirado';
            el.style.color = '#ef4444';
        }

        const submitBtn = document.querySelector(
            '#verifyForm button[type=submit]'
        );

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        return;
    }

    const m = String(Math.floor(segundos / 60)).padStart(2, '0');
    const s = String(segundos % 60).padStart(2, '0');

    if (el) {
        el.textContent = `${m}:${s}`;

        if (segundos <= 60) {
            el.style.color = '#ef4444';
        }
    }
}, 1000);


// Solo permitir dígitos en el campo
const codigo = document.getElementById('codigo');

if (codigo) {
    codigo.addEventListener('input', function () {
        this.value = this.value
            .replace(/\D/g, '')
            .slice(0, 6);
    });
}
