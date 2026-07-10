/*
   nova-selects — inicialización compartida de Choices.js y Flatpickr.

   Lo usan el bundle del panel usuario (app.js) y el del panel admin
   (admin/admin.js) para que los selects y date pickers se vean y
   comporten igual en todo el sistema.

   Activación por atributos:
   · select[data-nova-select]  (+ data-searchable, data-placeholder)
   · input[data-nova-date]     (+ data-enable-time, data-no-calendar, etc.)
    */

import Choices from 'choices.js';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';

flatpickr.localize(Spanish);

export { Choices, flatpickr };

/*
   Idempotente: ignora elementos ya inicializados (data-choices-init / _flatpickr).
   Permite re-inicializar en contenedores específicos para contenido dinámico.
*/
export function initNovaSelects(container = document) {

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
            altInputClass: el.dataset.altClass || 'form-input',
            altFormat:     altFormat,
            allowInput:    true,
            time_24hr:     true,
            defaultDate:   el.dataset.defaultDate || el.value || null,
            minDate:       el.dataset.minDate     || null,
        });
    });
}
