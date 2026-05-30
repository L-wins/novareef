import { createApp } from 'vue';
import Swal from 'sweetalert2';
import '../css/app.css';

// SweetAlert2 disponible globalmente
window.Swal = Swal;

/* ════════════════════════════════════════════════════════════════════════
   novaAlert — helpers de notificaciones coherentes con NovaReef
   Paleta: bg #1a1f2e · texto #e2e8f0 · primary #4f8ef7
   ════════════════════════════════════════════════════════════════════════ */
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
