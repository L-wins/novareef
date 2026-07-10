/* 
   welcome.js — Comportamiento interactivo de la página pública
    */

document.addEventListener('DOMContentLoaded', () => {
    initNavbarScroll();
    initMobileMenu();
    initSmoothScrollCTA();
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
