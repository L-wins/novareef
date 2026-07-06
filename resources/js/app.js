import { createApp } from 'vue';
import Swal from 'sweetalert2';
import Choices from 'choices.js';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import { initAutoFilter } from './shared/auto-filter.js';
import { initTheme } from './shared/theme.js';
import '../css/app.css';

window.initAutoFilter = initAutoFilter;

// Librerías globales
window.Swal      = Swal;
window.Choices   = Choices;
window.flatpickr = flatpickr;

flatpickr.localize(Spanish);

/* 
   novaAlert — helpers de notificaciones coherentes con NovaReef
   Paleta: bg #1a1f2e · texto #e2e8f0 · primary #4f8ef7
    */
window.novaAlert = {
    success: (mensaje) => Swal.fire({
        icon: 'success',
        title: mensaje,
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
        background: '#1a1f2e',
        color: '#e2e8f0',
        iconColor: '#22c55e',
        customClass: { popup: 'nova-swal' },
    }),

    error: (mensaje) => Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje,
        background: '#1a1f2e',
        color: '#e2e8f0',
        iconColor: '#ef4444',
        confirmButtonColor: '#4f8ef7',
        confirmButtonText: 'Entendido',
        customClass: { popup: 'nova-swal' },
    }),

    confirm: (opciones) => Swal.fire({
        title: opciones.titulo,
        text: opciones.texto,
        icon: opciones.icono || 'warning',
        showCancelButton: true,
        confirmButtonColor: opciones.confirmColor || '#ef4444',
        cancelButtonColor: '#374151',
        confirmButtonText: opciones.confirmarTexto || 'Confirmar',
        cancelButtonText: 'Cancelar',
        background: '#1a1f2e',
        color: '#e2e8f0',
        iconColor: opciones.iconColor || '#f59e0b',
        reverseButtons: true,
        focusCancel: true,
        customClass: { popup: 'nova-swal' },
    }),
};

/* 
   initNovaSelects — inicializa Choices.js y Flatpickr de forma global
   en cualquier select[data-nova-select] o input[data-nova-date] del DOM.

   Idempotente: ignora elementos ya inicializados (data-choices-init / _flatpickr).
   Permite re-inicializar en contenedores específicos para contenido dinámico.
    */
window.initNovaSelects = function (container = document) {

    // ── Choices.js ───────────────────
    container.querySelectorAll('select[data-nova-select]').forEach(function (el) {
        if (el.dataset.choicesInit === '1') return;

        var instance = new Choices(el, {
            searchEnabled:    el.dataset.searchable === 'true',
            shouldSort:       false,
            itemSelectText:   '',
            noChoicesText:    'No hay opciones disponibles',
            noResultsText:    'No se encontraron resultados',
            loadingText:      'Cargando...',
            placeholder:      true,
            placeholderValue: el.dataset.placeholder || 'Selecciona una opción',
            allowHTML:        false,
            position:         'auto',
        });

        el._choicesInstance = instance;
        el.dataset.choicesInit = '1';

    });

    // ── Flatpickr ────────────────────
    container.querySelectorAll('input[data-nova-date]').forEach(function (el) {
        if (el._flatpickr) return;

        var esHora       = el.dataset.noCalendar === 'true';
        var enableTime   = el.dataset.enableTime === 'true' || esHora;
        var dateFormat   = el.dataset.dateFormat  || (esHora ? 'H:i' : 'Y-m-d');
        var altFormat    = el.dataset.altFormat   || (esHora ? 'H:i' : 'd/m/Y');

        flatpickr(el, {
            locale:        'es',
            noCalendar:    esHora,
            enableTime:    enableTime,
            dateFormat:    dateFormat,
            altInput:      !esHora,         // el campo hora no usa altInput (no hay formato alternativo)
            altInputClass: 'form-input',
            altFormat:     altFormat,
            allowInput:    true,
            time_24hr:     true,
            defaultDate:   el.dataset.defaultDate || el.value || null,
            minDate:       el.dataset.minDate     || null,
        });
    });
};

// Inicialización automática
document.addEventListener('DOMContentLoaded', function () {
    window.initNovaSelects();
    window.initAutoFilter();
    initTheme();
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
