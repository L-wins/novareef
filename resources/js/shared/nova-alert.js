/*
   novaAlert — helpers de notificaciones coherentes con NovaReef.
   Compartido entre el panel usuario (app.js) y el panel admin (admin/admin.js).

   El fondo/texto del popup los define .nova-swal en CSS con tokens --nv-*
   (se adaptan solos a claro/oscuro). Aquí solo se parametrizan acentos
   puntuales (confirmColor/iconColor) que no dependen del tema.
    */

import Swal from 'sweetalert2';

export { Swal };

export const novaAlert = {
    success: (mensaje) => Swal.fire({
        icon: 'success',
        title: mensaje,
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
        customClass: { popup: 'nova-swal' },
    }),

    error: (mensaje) => Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje,
        confirmButtonColor: '#4f8ef7',
        confirmButtonText: 'Entendido',
        customClass: { popup: 'nova-swal' },
    }),

    confirm: (opciones) => Swal.fire({
        title: opciones.titulo,
        text: opciones.texto,
        html: opciones.html || undefined,
        icon: opciones.icono || 'warning',
        showCancelButton: true,
        confirmButtonColor: opciones.confirmColor || '#ef4444',
        cancelButtonColor: '#374151',
        confirmButtonText: opciones.confirmarTexto || 'Confirmar',
        cancelButtonText: 'Cancelar',
        iconColor: opciones.iconColor || '#f59e0b',
        reverseButtons: true,
        focusCancel: true,
        customClass: { popup: 'nova-swal' },
    }),
};

/*
   Confirmación genérica antes de enviar un formulario destructivo o
   irreversible. Marcar el <form> con data-confirm-submit y opcionalmente:
   data-confirm-title, data-confirm-text, data-confirm-color, data-confirm-btn.
*/
export function initConfirmSubmit(container = document) {
    container.querySelectorAll('[data-confirm-submit]').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            if (form.dataset.confirmed === '1' || !window.novaAlert) return;
            e.preventDefault();

            var titulo = form.dataset.confirmTitle || '¿Confirmar?';
            var texto  = form.dataset.confirmText  || '¿Estás seguro?';
            var color  = form.dataset.confirmColor || '#4f8ef7';
            var btnLbl = form.dataset.confirmBtn   || 'Sí, continuar';

            var result = await novaAlert.confirm({
                titulo:         titulo,
                texto:          texto,
                icono:          'question',
                iconColor:      color,
                confirmarTexto: btnLbl,
                confirmColor:   color,
            });

            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    });
}
