/* 
   login.js — Lógica del formulario de autenticación
    */

document.addEventListener('DOMContentLoaded', () => {
    initPasswordToggle();
    initPasswordToggles();
    initPasswordStrength();
    initFormLoadingState();
    initShakeOnError();
    autoFocusFirstError();
});

/**
 * Alterna la visibilidad del campo contraseña.
 */
function initPasswordToggle() {
    const toggle = document.getElementById('toggle-password');
    const input  = document.getElementById('password');
    if (!toggle || !input) return;

    const iconShow = toggle.querySelector('[data-icon="show"]');
    const iconHide = toggle.querySelector('[data-icon="hide"]');

    toggle.addEventListener('click', () => {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        iconShow.classList.toggle('hidden', isPassword);
        iconHide.classList.toggle('hidden', !isPassword);

        toggle.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
}

/**
 * Toggles de visibilidad genéricos: cualquier botón con
 * data-password-toggle="<idDelInput>" (vista de cambio de contraseña).
 */
function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        const input = document.getElementById(btn.dataset.passwordToggle);
        if (!input) return;

        const iconShow = btn.querySelector('[data-icon="show"]');
        const iconHide = btn.querySelector('[data-icon="hide"]');

        btn.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            iconShow.classList.toggle('hidden', isPassword);
            iconHide.classList.toggle('hidden', !isPassword);

            btn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });
}

/**
 * Medidor de fortaleza de contraseña.
 * Input [data-password-strength] + contenedor [data-strength-meter].
 * Puntúa 4 criterios: longitud >= 8, mayúscula+minúscula, número, símbolo.
 * Mientras no alcance los 8 caracteres, [data-strength-submit] queda deshabilitado.
 */
function initPasswordStrength() {
    const input = document.querySelector('[data-password-strength]');
    const meter = document.querySelector('[data-strength-meter]');
    if (!input || !meter) return;

    const barras = meter.querySelectorAll('.strength-meter__bars span');
    const label  = meter.querySelector('[data-strength-label]');
    const submit = document.querySelector('[data-strength-submit]');

    const NIVELES = [
        { texto: 'Muy débil — necesita al menos 8 caracteres', clase: 'nivel-1' },
        { texto: 'Débil',                                       clase: 'nivel-1' },
        { texto: 'Regular',                                     clase: 'nivel-2' },
        { texto: 'Buena',                                       clase: 'nivel-3' },
        { texto: 'Fuerte',                                      clase: 'nivel-4' },
    ];

    const evaluar = (valor) => {
        let puntos = 0;
        if (valor.length >= 8) puntos++;
        if (/[a-z]/.test(valor) && /[A-Z]/.test(valor)) puntos++;
        if (/\d/.test(valor)) puntos++;
        if (/[^a-zA-Z0-9]/.test(valor)) puntos++;

        // Sin el mínimo de 8 caracteres nunca pasa de "muy débil".
        return valor.length < 8 ? 0 : puntos;
    };

    const render = () => {
        const valor = input.value;

        if (valor === '') {
            meter.hidden = true;
            if (submit) submit.disabled = true;
            return;
        }

        meter.hidden = false;

        const puntos = evaluar(valor);
        const nivel  = NIVELES[puntos];

        meter.className = 'strength-meter ' + nivel.clase;
        label.textContent = nivel.texto;

        barras.forEach((barra, i) => {
            barra.classList.toggle('activa', i < Math.max(puntos, valor.length > 0 ? 1 : 0));
        });

        if (submit) submit.disabled = valor.length < 8;
    };

    input.addEventListener('input', render);
    render();
}

/**
 * Activa el estado de carga en el botón al enviar el formulario.
 */
function initFormLoadingState() {
    const form = document.getElementById('login-form');
    const btn  = document.getElementById('btn-login');
    if (!form || !btn) return;

    form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) return;

        btn.classList.add('loading');
        btn.disabled = true;
    });
}

/**
 * Aplica animación de sacudida a la tarjeta cuando hay errores del servidor.
 */
function initShakeOnError() {
    const card = document.getElementById('login-card');
    if (!card) return;

    const hasServerError = card.dataset.hasError === 'true';
    if (!hasServerError) return;

    card.classList.add('shake');
    card.addEventListener('animationend', () => card.classList.remove('shake'), { once: true });
}

/**
 * Mueve el foco al primer campo con error tras un intento fallido.
 */
function autoFocusFirstError() {
    const firstError = document.querySelector('.field-input.has-error');
    if (firstError) {
        firstError.focus();
        firstError.select?.();
    }
}
