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

    // ── Formulario de sanción: mostrar fechas de suspensión solo si aplica ──
    var checkSuspension = document.getElementById('tieneSuspension');
    var camposSuspension = document.querySelectorAll('[data-campo-condicional="suspension"]');

    function sincronizarSuspension() {
        if (!checkSuspension) return;
        camposSuspension.forEach(function (campo) {
            campo.classList.toggle('is-visible', checkSuspension.checked);
        });
    }

    if (checkSuspension) {
        checkSuspension.addEventListener('change', sincronizarSuspension);
        sincronizarSuspension();
    }

    // ── Formulario de sanción: ayuda contextual del tipo seleccionado ──
    var selectTipo = document.getElementById('idTipoSancion');
    var hintTipo = document.getElementById('hint-tipo-sancion');
    var hintTipoTexto = document.getElementById('hint-tipo-sancion-texto');

    function sincronizarHintTipo() {
        if (!selectTipo || !hintTipo || !hintTipoTexto) return;
        var opcion = selectTipo.options[selectTipo.selectedIndex];
        var articulo = opcion ? opcion.dataset.articulo : '';
        var dias = opcion ? opcion.dataset.dias : '';
        var partes = [];

        if (articulo) partes.push('Fundamento: ' + articulo);
        if (dias && parseInt(dias, 10) > 0) partes.push(dias + ' días de suspensión sugeridos');

        if (partes.length) {
            hintTipoTexto.textContent = partes.join(' · ');
            hintTipo.style.display = 'flex';
        } else {
            hintTipo.style.display = 'none';
        }
    }

    if (selectTipo) {
        selectTipo.addEventListener('change', sincronizarHintTipo);
        // Choices.js reemplaza el <select> nativo — su evento propio también dispara 'change' en el original.
        sincronizarHintTipo();
    }

    // ── Cambiar estado: mostrar campo de motivo/resultado según la acción ──
    var textosMotivo = {
        anular: { label: 'Motivo', placeholder: 'Explica por qué se anula esta sanción.' },
        apelar: { label: 'Tu versión de los hechos', placeholder: 'Explica por qué apelas esta sanción — tu versión de lo ocurrido.' },
        resolver: { label: 'Motivo (opcional)', placeholder: '' },
        cumplir: { label: 'Motivo (opcional)', placeholder: '' },
    };

    document.querySelectorAll('[data-accion-sancion]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var accion = btn.dataset.accionSancion;
            var form = document.getElementById('form-cambiar-estado');
            var inputAccion = document.getElementById('accion-input');
            var wrapMotivo = document.getElementById('wrap-motivo');
            var wrapResultado = document.getElementById('wrap-resultado');
            var tituloModal = document.getElementById('modal-estado-titulo');
            var labelMotivo = document.getElementById('label-motivo');
            var textareaMotivo = document.getElementById('textarea-motivo');

            if (!form || !inputAccion) return;

            inputAccion.value = accion;

            var motivoObligatorio = (accion === 'anular' || accion === 'apelar');
            if (wrapMotivo) wrapMotivo.style.display = (accion === 'anular' || accion === 'apelar') ? '' : 'none';
            if (wrapResultado) wrapResultado.style.display = (accion === 'resolver') ? '' : 'none';

            if (textareaMotivo) {
                textareaMotivo.required = motivoObligatorio;
                textareaMotivo.value = '';
                var textos = textosMotivo[accion] || textosMotivo.cumplir;
                textareaMotivo.placeholder = textos.placeholder;
                if (labelMotivo) labelMotivo.textContent = textos.label;
            }

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
            var wrap = document.getElementById('wrap-rechazo-' + btn.dataset.abrirRechazo);
            if (wrap) wrap.classList.toggle('is-open');
        });
    });

});
