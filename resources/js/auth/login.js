/* ================================================
   login.js — Lógica del formulario de autenticación
   ================================================ */

document.addEventListener('DOMContentLoaded', () => {
    initPasswordToggle();
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
