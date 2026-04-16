document.addEventListener('DOMContentLoaded', () => {
    // --- 1. LÓGICA DE FORMULARIO (NUEVA/CORREGIDA) ---
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', () => {
            // No ponemos e.preventDefault() porque queremos que PHP reciba el POST
            console.log("Enviando credenciales al servidor...");
        });
    }

    // --- 2. NAVEGACIÓN Y SCROLL ---
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // Solo aplicamos scroll suave si el enlace apunta a una sección (#)
            const targetId = link.getAttribute('href');
            if (targetId.startsWith('#')) {
                e.preventDefault();
                const targetSection = document.querySelector(targetId);
                if (targetSection) {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const targetPosition = targetSection.offsetTop - headerHeight - 20;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // --- 3. EFECTOS VISUALES (TU CÓDIGO ORIGINAL) ---
    
    // Parallax Hero
    const hero = document.querySelector('.hero');
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.3; // Ajustado a -0.3 para que no sea tan brusco
        if (hero) {
            hero.style.transform = `translateY(${rate}px)`;
        }
    });

    // Intersection Observer para animaciones de entrada
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    const animatedElements = document.querySelectorAll('.feature-card, .phase-card, .author-card, .methodology-card');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // Hover efectos en cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-8px) scale(1.02)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Typing Effect en Hero
    const heroTitle = document.querySelector('.hero-title');
    if (heroTitle) {
        const originalText = heroTitle.textContent;
        heroTitle.textContent = '';
        let i = 0;
        const typeWriter = () => {
            if (i < originalText.length) {
                heroTitle.textContent += originalText.charAt(i);
                i++;
                setTimeout(typeWriter, 50);
            }
        };
        setTimeout(typeWriter, 500);
    }

    // --- 4. PARTÍCULAS Y ESTILOS DINÁMICOS ---
    const createParticles = () => {
        const heroSection = document.querySelector('.hero');
        if (!heroSection) return;
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'hero-particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 2 + 's';
            heroSection.appendChild(particle);
        }
    };
    createParticles();
});

// Inyectar estilos necesarios para las partículas y estados activos
const style = document.createElement('style');
style.textContent = `
    .hero-particle {
        position: absolute;
        width: 2px;
        height: 2px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        animation: float 4s ease-in-out infinite;
    }
    @keyframes float {
        0%, 100% { transform: translateY(0px); opacity: 0.3; }
        50% { transform: translateY(-20px); opacity: 0.8; }
    }
    .nav-link.active { color: #667eea !important; }
`;
document.head.appendChild(style);