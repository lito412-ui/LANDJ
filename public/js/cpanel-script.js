let ultimaLectura = null;
let ultimoTiempo = null;

//Cofig de calibración
const FACTOR_AJUSTE = 64; 
const LIMITE_RAM_MB = 2048;

console.log("🚀 Monitor Pro: Sistema de telemetría iniciado...");

document.addEventListener('DOMContentLoaded', () => {
    actualizarMetricas(); 
    setInterval(actualizarMetricas, 3000);
    initSidebarNavigation();
    initUserMenu();
});

async function actualizarMetricas() {
    try {
        const respuesta = await fetch('/api/monitorizacion.php?t=' + Date.now());
        if (!respuesta.ok) throw new Error('Error de conexión con el servidor');
        
        const datos = await respuesta.json();

        //Procesamiento de BBDD
        let ramTotalMB = 0;
        let cpuTotalAcumulada = 0;

        if (datos.contenedores && Array.isArray(datos.contenedores)) {
            datos.contenedores.forEach(c => {
                ramTotalMB += parseFloat(c.memoria_mb) || 0;
                cpuTotalAcumulada += parseFloat(c.cpu_raw) || 0;
            });
        }

        //Actualizar Disco
        const discoTxt = document.getElementById('disco-texto');
        const discoBar = document.getElementById('disco-barra');
        if (discoTxt && discoBar) {
            discoTxt.innerText = datos.disco + "%";
            discoBar.style.width = datos.disco + "%";
        }

        //Actualizar RAM
        const porcentajeRAM = Math.min((ramTotalMB / LIMITE_RAM_MB) * 100, 100).toFixed(1);
        const ramTxt = document.getElementById('ram-texto');
        const ramBar = document.getElementById('ram-barra');
        
        if (ramTxt && ramBar) {
            ramTxt.innerText = porcentajeRAM + "%";
            ramBar.style.width = porcentajeRAM + "%";
        }

        //Lógica CPU
        const ahora = Date.now();

        if (ultimaLectura !== null && ultimoTiempo !== null) {
            const difCPU = cpuTotalAcumulada - ultimaLectura;
            const difTiempoNS = (ahora - ultimoTiempo) * 1000000;

            if (difCPU === 0) {
                console.warn(" Datos estáticos: El servidor envió el mismo cpu_raw.");
            } else {
                let calculoBase = (difCPU / difTiempoNS) * 100;
                let porcentajeReal = calculoBase / FACTOR_AJUSTE;

                const cpuFinal = Math.max(0.1, Math.min(porcentajeReal, 100)).toFixed(1);
                
                const cpuTxt = document.getElementById('cpu-texto');
                const cpuBar = document.getElementById('cpu-barra');
                
                if (cpuTxt && cpuBar) {
                    cpuTxt.innerText = cpuFinal + "%";
                    cpuBar.style.width = cpuFinal + "%";
                    console.log(`[OK] CPU: ${cpuFinal}% | RAM: ${porcentajeRAM}%`);
                }
            }
        }

        ultimaLectura = cpuTotalAcumulada;
        ultimoTiempo = ahora;

    } catch (e) {
        console.error(" Error en actualización:", e.message);
    }
}

function initSidebarNavigation() {
    const links = document.querySelectorAll('.nav-link');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            const sectionId = link.getAttribute('data-section');
            if (!sectionId) return;
            e.preventDefault();
            document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            link.parentElement.classList.add('active');
            document.getElementById(sectionId)?.classList.add('active');
        });
    });
}

function initUserMenu() {
    const btn = document.getElementById('user-menu-btn');
    const menu = document.getElementById('user-dropdown');
    if (btn && menu) {
        btn.onclick = (e) => { e.stopPropagation(); menu.classList.toggle('show'); };
        window.onclick = () => menu.classList.remove('show');
    }
}