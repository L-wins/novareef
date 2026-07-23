/* ─────────────────────────────────────────────────────────────
   Auto-filter — envío automático de formularios de filtros GET.

   Uso: <form method="GET" data-auto-filter> ... </form>
   · select / date / checkbox / radio → submit inmediato al cambiar
   · input de texto → submit con debounce
   · Los campos vacíos se excluyen del querystring (URLs limpias)
   · El foco del buscador de texto se restaura tras recargar
   · Botones con data-auto-filter-hide se ocultan (fallback sin JS)

   Modo AJAX (opcional, opt-in): agregar data-auto-filter-ajax al form.
   En vez de recargar la página completa, hace fetch() a la misma URL del
   form y reemplaza el innerHTML de cada [data-auto-filter-region="NOMBRE"]
   con el fragmento que el backend devuelva en JSON bajo `regions.NOMBRE`.
   El navegador nunca destruye el <input> donde el usuario está escribiendo
   (vive fuera de la región reemplazada), así que el foco/cursor se
   conservan solos — no hace falta el hack de sessionStorage del modo full
   reload. El backend debe responder con JSON cuando la petición trae
   X-Requested-With: XMLHttpRequest (Request::ajax() en Laravel), o con la
   vista completa normal en cualquier otro caso (carga inicial, sin JS).
   Los links de paginación dentro de una región también se interceptan, y
   también el evento submit del form (Enter en un input de texto dispara el
   submit nativo aunque el botón esté oculto por data-auto-filter-hide) —
   sin esto, ese único caso se escapaba del modo AJAX y recargaba la página.

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

async function cargarAjax(form, url) {
    const regiones = form.dataset.autoFilterAjax
        ? document.querySelectorAll('[data-auto-filter-region]')
        : [];
    regiones.forEach((el) => el.classList.add('is-loading'));

    try {
        const r = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const data = await r.json();

        Object.entries(data.regions || {}).forEach(([nombre, html]) => {
            document.querySelectorAll(`[data-auto-filter-region="${nombre}"]`).forEach((el) => {
                el.innerHTML = html;
            });
        });

        history.pushState({}, '', url.toString());
    } catch (e) {
        // Si algo falla (red, backend sin soporte JSON, etc.) se degrada al
        // comportamiento de siempre en vez de dejar la pantalla colgada.
        console.error('[auto-filter] modo AJAX falló, recargando la página', e);
        window.location.href = url.toString();
    } finally {
        regiones.forEach((el) => el.classList.remove('is-loading'));
    }
}

function enviar(form, trigger) {
    // Excluir campos vacíos del querystring; restaurar si el envío no navega
    const vacios = [...form.querySelectorAll('input[name], select[name]')]
        .filter((el) => el.value === '' && !el.disabled);
    vacios.forEach((el) => { el.disabled = true; });

    if (form.hasAttribute('data-auto-filter-ajax')) {
        const url = new URL(form.action, window.location.origin);
        url.search = new URLSearchParams(new FormData(form)).toString();
        vacios.forEach((el) => { el.disabled = false; });
        cargarAjax(form, url);
        return;
    }

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

        // Enter en un input de texto, o click en el botón submit (aunque esté
        // oculto por data-auto-filter-hide, Enter lo dispara igual) — sin
        // esto, el modo AJAX se saltaba justo en el caso más obvio: escribir
        // y darle Enter recargaba la página completa en vez de usar fetch().
        if (form.hasAttribute('data-auto-filter-ajax')) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                clearTimeout(timer);
                enviar(form, null);
            });
        }

        // Con JS activo el botón "Filtrar" sobra — ocultarlo (queda como fallback sin JS)
        form.querySelectorAll('[data-auto-filter-hide]').forEach((btn) => {
            btn.style.display = 'none';
        });

        // Modo AJAX: interceptar los links de paginación dentro de las
        // regiones reemplazables para que tampoco recarguen la página.
        if (form.hasAttribute('data-auto-filter-ajax')) {
            document.addEventListener('click', (e) => {
                const link = e.target.closest('[data-auto-filter-region] a[href]');
                if (!link) return;

                const destino = new URL(link.href, window.location.origin);
                const accion  = new URL(form.action, window.location.origin);
                if (destino.pathname !== accion.pathname) return;

                e.preventDefault();
                cargarAjax(form, destino);
            });
        }
    });

    // Restaurar foco del buscador tras la recarga por búsqueda de texto
    // (solo aplica al modo full-reload — en modo AJAX el input nunca se
    // destruye, así que nunca pierde el foco y esto no hace nada).
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
