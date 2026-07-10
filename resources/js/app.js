import { createApp } from 'vue';
import { Swal, novaAlert } from './shared/nova-alert.js';
import { Choices, flatpickr, initNovaSelects } from './shared/nova-selects.js';
import { initAutoFilter } from './shared/auto-filter.js';
import { initTheme } from './shared/theme.js';
import { initReloj } from './shared/reloj.js';
import '../css/app.css';

window.initAutoFilter = initAutoFilter;

// Librerías globales
window.Swal      = Swal;
window.Choices   = Choices;
window.flatpickr = flatpickr;

// novaAlert vive en shared/nova-alert.js (compartido con el panel admin)
window.novaAlert = novaAlert;

/*
   initNovaSelects — inicializa Choices.js y Flatpickr de forma global
   en cualquier select[data-nova-select] o input[data-nova-date] del DOM.
   La implementación vive en shared/nova-selects.js (compartida con admin).
    */
window.initNovaSelects = initNovaSelects;

// Inicialización automática
document.addEventListener('DOMContentLoaded', function () {
    window.initNovaSelects();
    window.initAutoFilter();
    initTheme();
    initReloj();
});

/*
   CTA "Actualizar plan" — todavía no existe autoservicio de upgrade,
   así que el botón (data-plan-upgrade-cta) solo informa por ahora.
   Reemplazar este listener por una navegación real cuando exista la ruta.
    */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-plan-upgrade-cta]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (window.novaAlert) {
                novaAlert.success('Muy pronto podrás actualizar tu plan desde aquí. Mientras tanto, contacta a tu ejecutivo de cuenta.');
            }
        });
    });
});

// Sombra en el navbar al hacer scroll
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var nav = document.getElementById('navbar');
        if (!nav) return;
        window.addEventListener('scroll', function () {
            nav.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });
    });
}());
