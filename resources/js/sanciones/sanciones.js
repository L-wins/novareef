document.addEventListener('DOMContentLoaded', function () {

    // ── Formulario de sanción: mostrar el campo de monto solo si tiene multa ──
    var checkMulta  = document.getElementById('tieneMultaEconomica');
    var campoMulta  = document.querySelector('[data-campo-condicional="multa"]');

    function sincronizarMulta() {
        if (!checkMulta || !campoMulta) return;
        campoMulta.classList.toggle('is-visible', checkMulta.checked);
    }

    if (checkMulta) {
        checkMulta.addEventListener('change', sincronizarMulta);
        sincronizarMulta();
    }

    // ── Cambiar estado: mostrar campo de motivo/resultado según la acción ──
    document.querySelectorAll('[data-accion-sancion]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var accion = btn.dataset.accionSancion;
            var form = document.getElementById('form-cambiar-estado');
            var inputAccion = document.getElementById('accion-input');
            var wrapMotivo = document.getElementById('wrap-motivo');
            var wrapResultado = document.getElementById('wrap-resultado');
            var tituloModal = document.getElementById('modal-estado-titulo');

            if (!form || !inputAccion) return;

            inputAccion.value = accion;
            if (wrapMotivo) wrapMotivo.style.display = (accion === 'anular') ? '' : 'none';
            if (wrapResultado) wrapResultado.style.display = (accion === 'resolver') ? '' : 'none';

            var titulos = { cumplir: 'Marcar como cumplida', anular: 'Anular sanción', apelar: 'Apelar sanción', resolver: 'Resolver apelación' };
            if (tituloModal) tituloModal.textContent = titulos[accion] || 'Cambiar estado';

            var modal = document.getElementById('modal-cambiar-estado');
            if (modal) modal.style.display = 'flex';
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = btn.closest('.nova-modal-overlay');
            if (modal) modal.style.display = 'none';
        });
    });

    // ── Justificaciones pendientes: mostrar formulario de rechazo ──
    document.querySelectorAll('[data-abrir-rechazo]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.getElementById('form-rechazo-' + btn.dataset.abrirRechazo);
            if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });
    });

});
