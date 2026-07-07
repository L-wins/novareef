/*
   configuracion.js — Módulo de Configuración del colegio
   1. Verificador en vivo de disponibilidad de nombre de usuario (cuentas admin)
   2. Modo lectura/edición de formularios de configuración (data-edit-mode)
    */

document.addEventListener('DOMContentLoaded', () => {
    initUsernameCheck();
    initEditMode();
    initSubmitOnChange();
});

/*
   Inputs de archivo que envían su formulario al seleccionar (subida de logo).
    */
function initSubmitOnChange() {
    document.querySelectorAll('[data-submit-on-change]').forEach((input) => {
        input.addEventListener('change', () => {
            if (input.files && input.files.length > 0) input.form.submit();
        });
    });
}

/*
   1. Disponibilidad de nombre de usuario
   Input marcado con [data-username-check] + data-endpoint + data-ignorar.
   Consulta la BD con debounce y pinta el estado en [data-username-status].
    */
function initUsernameCheck() {
    const input = document.querySelector('[data-username-check]');
    if (!input) return;

    const status   = document.querySelector('[data-username-status]');
    const endpoint = input.dataset.endpoint;
    const ignorar  = input.dataset.ignorar || '';
    const original = input.value.trim();

    let timer      = null;
    let controller = null;

    input.addEventListener('input', () => {
        clearTimeout(timer);

        const valor = input.value.trim();

        // Sin cambios respecto al username actual: no hay nada que verificar.
        if (valor === '' || valor === original) {
            pintar(status, '', '');
            return;
        }

        if (!/^[\wÀ-ɏ-]+$/.test(valor)) {
            pintar(status, 'no', 'Solo letras, números, guiones y guiones bajos.');
            return;
        }

        pintar(status, 'checking', 'Verificando disponibilidad…');

        timer = setTimeout(async () => {
            if (controller) controller.abort();
            controller = new AbortController();

            try {
                const url  = `${endpoint}?username=${encodeURIComponent(valor)}&ignorar=${encodeURIComponent(ignorar)}`;
                const res  = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    signal:  controller.signal,
                });
                const data = await res.json();

                if (input.value.trim() !== valor) return; // el usuario siguió escribiendo

                if (!data.valido) {
                    pintar(status, 'no', 'Nombre de usuario no válido.');
                } else if (data.disponible) {
                    pintar(status, 'ok', 'Disponible');
                } else {
                    pintar(status, 'no', 'No disponible — ya está en uso.');
                }
            } catch (e) {
                if (e.name !== 'AbortError') pintar(status, '', '');
            }
        }, 400);
    });
}

function pintar(el, estado, texto) {
    if (!el) return;
    el.dataset.estado = estado;
    el.innerHTML = '';

    if (!estado) return;

    const icono = document.createElement('i');
    icono.className = {
        checking: 'fa-solid fa-circle-notch fa-spin',
        ok:       'fa-solid fa-circle-check',
        no:       'fa-solid fa-circle-xmark',
    }[estado];

    el.appendChild(icono);
    el.appendChild(document.createTextNode(' ' + texto));
}

/*
   2. Modo lectura/edición
   Formulario marcado con [data-edit-mode]: sus campos arrancan bloqueados.
   [data-edit-btn] los habilita; [data-edit-cancel] restaura y vuelve a bloquear.
   Los selects Choices.js se manejan via su instancia (el disabled nativo
   no aplica al DOM que Choices renderiza encima).
    */
function initEditMode() {
    document.querySelectorAll('form[data-edit-mode]').forEach((form) => {
        const btnEditar  = form.querySelector('[data-edit-btn]');
        const btnGuardar = form.querySelector('[data-edit-save]');
        const btnCancel  = form.querySelector('[data-edit-cancel]');
        // Excluye los hidden (_token, _method): deshabilitarlos rompería el POST.
        const campos     = form.querySelectorAll('input:not([type="hidden"]), select, textarea');

        if (!btnEditar) return;

        const valoresIniciales = new Map();
        campos.forEach((c) => valoresIniciales.set(c, c.type === 'checkbox' || c.type === 'radio' ? c.checked : c.value));

        const setBloqueado = (bloqueado) => {
            campos.forEach((c) => {
                if (c._choicesInstance) {
                    bloqueado ? c._choicesInstance.disable() : c._choicesInstance.enable();
                } else {
                    c.disabled = bloqueado;
                }
            });
            form.classList.toggle('is-readonly', bloqueado);
            btnEditar.hidden  = !bloqueado;
            if (btnGuardar) btnGuardar.hidden = bloqueado;
            if (btnCancel)  btnCancel.hidden  = bloqueado;
        };

        btnEditar.addEventListener('click', () => setBloqueado(false));

        if (btnCancel) {
            btnCancel.addEventListener('click', () => {
                campos.forEach((c) => {
                    const inicial = valoresIniciales.get(c);
                    if (c.type === 'checkbox' || c.type === 'radio') {
                        c.checked = inicial;
                    } else {
                        c.value = inicial;
                    }
                    if (c._choicesInstance) c._choicesInstance.setChoiceByValue(String(inicial));
                });
                setBloqueado(true);
            });
        }

        // Los campos deshabilitados no viajan en el POST: se rehabilitan justo
        // antes de enviar (solo aplica si el submit ocurre en modo edición).
        form.addEventListener('submit', () => {
            campos.forEach((c) => { c.disabled = false; });
        });

        setBloqueado(true);
    });
}
