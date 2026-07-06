/* ─────────────────────────────────────────────────────────────
   Auto-filter — envío automático de formularios de filtros GET.

   Uso: <form method="GET" data-auto-filter> ... </form>
   · select / date / checkbox / radio → submit inmediato al cambiar
   · input de texto → submit con debounce
   · Los campos vacíos se excluyen del querystring (URLs limpias)
   · El foco del buscador de texto se restaura tras recargar
   · Botones con data-auto-filter-hide se ocultan (fallback sin JS)

   Solo para formularios GET de filtrado/búsqueda. Nunca marcar con
   data-auto-filter formularios que ejecuten acciones (POST/PUT/DELETE).
   ───────────────────────────────────────────────────────────── */

const DEBOUNCE_MS = 450;
const FOCUS_KEY   = 'nova:auto-filter:focus';

function esInputTexto(el) {
    if (el.tagName !== 'INPUT') return false;
    if (el.dataset.novaDate !== undefined || el._flatpickr) return false;
    const tipo = (el.getAttribute('type') || 'text').toLowerCase();
    return tipo === 'text' || tipo === 'search';
}

function enviar(form, trigger) {
    // Excluir campos vacíos del querystring; restaurar si el envío no navega
    const vacios = [...form.querySelectorAll('input[name], select[name]')]
        .filter((el) => el.value === '' && !el.disabled);
    vacios.forEach((el) => { el.disabled = true; });
    setTimeout(() => vacios.forEach((el) => { el.disabled = false; }), 1000);

    if (trigger && esInputTexto(trigger) && trigger.name) {
        sessionStorage.setItem(FOCUS_KEY, trigger.name);
    }

    form.requestSubmit ? form.requestSubmit() : form.submit();
}

export function initAutoFilter(container = document) {
    container.querySelectorAll('form[data-auto-filter]').forEach((form) => {
        if (form.dataset.autoFilterInit === '1') return;
        form.dataset.autoFilterInit = '1';

        if (form.method.toLowerCase() !== 'get') {
            console.warn('[auto-filter] Ignorado: solo se permite en formularios GET.', form);
            return;
        }

        let timer = null;

        // select / date (flatpickr dispara change en el input original) / check / radio
        form.addEventListener('change', (e) => {
            const el = e.target;
            if (!el.name || esInputTexto(el)) return;
            if (el.matches('select, input[data-nova-date], input[type="date"], input[type="checkbox"], input[type="radio"]')) {
                clearTimeout(timer);
                enviar(form, el);
            }
        });

        // texto → debounce
        form.addEventListener('input', (e) => {
            const el = e.target;
            if (!el.name || !esInputTexto(el)) return;
            clearTimeout(timer);
            timer = setTimeout(() => enviar(form, el), DEBOUNCE_MS);
        });

        // Con JS activo el botón "Filtrar" sobra — ocultarlo (queda como fallback sin JS)
        form.querySelectorAll('[data-auto-filter-hide]').forEach((btn) => {
            btn.style.display = 'none';
        });
    });

    // Restaurar foco del buscador tras la recarga por búsqueda de texto
    const focusName = sessionStorage.getItem(FOCUS_KEY);
    if (focusName) {
        sessionStorage.removeItem(FOCUS_KEY);
        const input = document.querySelector(`form[data-auto-filter] [name="${focusName}"]`);
        if (input) {
            input.focus();
            input.setSelectionRange(input.value.length, input.value.length);
        }
    }
}
