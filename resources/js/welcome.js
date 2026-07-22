/* 
   welcome.js — Comportamiento interactivo de la página pública
    */

document.addEventListener('DOMContentLoaded', () => {
    initNavbarScroll();
    initMobileMenu();
    initSmoothScrollCTA();
    initScrollReveal();
    initHeroParallax();
    initTiltCards();
    initModulosMarquee();
});

/**
 * Agrega sombra al navbar cuando el usuario hace scroll.
 */
function initNavbarScroll() {
    const navbar = document.getElementById('navbar');
    if (!navbar) return;

    const onScroll = () => {
        if (window.scrollY > 20) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
}

/**
 * Toggle del menú de navegación en móvil.
 */
function initMobileMenu() {
    const toggle = document.getElementById('menu-toggle');
    const menu   = document.getElementById('mobile-menu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', () => {
        const isOpen = menu.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen);
    });

    // Cierra el menú al hacer clic en un enlace
    menu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            menu.classList.remove('open');
            toggle.setAttribute('aria-expanded', false);
        });
    });
}

/**
 * Desplazamiento suave para el botón "Conocer más" y anclas internas.
 * Complementa la clase scroll-smooth del HTML para mayor compatibilidad.
 */
function initSmoothScrollCTA() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            const targetId = anchor.getAttribute('href').slice(1);
            const target   = document.getElementById(targetId);
            if (!target) return;

            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

/**
 * Revela elementos con [data-reveal] mediante fade+slide al entrar
 * en el viewport. Respeta prefers-reduced-motion mostrándolos directo.
 */
function initScrollReveal() {
    const targets = document.querySelectorAll('[data-reveal]');
    if (!targets.length) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        targets.forEach(el => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const delay = entry.target.dataset.revealDelay || 0;
            entry.target.style.transitionDelay = `${delay}ms`;
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

    targets.forEach(el => observer.observe(el));
}

/**
 * Parallax sutil del mockup del hero según el scroll (desktop only).
 */
function initHeroParallax() {
    const mockup = document.querySelector('[data-hero-parallax]');
    if (!mockup || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.matchMedia('(max-width: 768px)').matches) return;

    const onScroll = () => {
        const offset = Math.min(window.scrollY * 0.08, 40);
        mockup.style.transform = `translateY(${offset}px)`;
    };

    window.addEventListener('scroll', onScroll, { passive: true });
}

/**
 * Efecto tilt 3D sutil al mover el mouse sobre tarjetas [data-tilt]
 * (planes, módulos destacados). Se desactiva en touch/reduced-motion.
 */
function initTiltCards() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (window.matchMedia('(pointer: coarse)').matches) return;

    document.querySelectorAll('[data-tilt]').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;
            card.style.transform = `perspective(800px) rotateY(${x * 6}deg) rotateX(${-y * 6}deg) translateY(-4px)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });
}

/**
 * Cinta de módulos: el CSS ya la pausa con :hover (ver .modulos-marquee__track).
 * Acá solo se maneja el clic para desplegar la descripción — un módulo abierto
 * a la vez, y cada tarjeta se cierra sola al hacer clic de nuevo.
 */
function initModulosMarquee() {
    const track = document.querySelector('[data-modulos-track]');
    if (!track) return;

    track.querySelectorAll('[data-modulos-card]').forEach(card => {
        card.addEventListener('click', () => {
            const yaAbierta = card.classList.contains('is-open');
            track.querySelectorAll('.is-open').forEach(abierta => abierta.classList.remove('is-open'));
            if (!yaAbierta) card.classList.add('is-open');
        });
    });
}
