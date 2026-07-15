document.addEventListener('DOMContentLoaded', function () {

    // ── Modal "Registrar": la categoría depende del tipo elegido, y el
    //    torneo solo aplica a la categoría "ingreso_torneo" ──
    var tipoSelect      = document.getElementById('inst-tipo');
    var categoriaSelect = document.getElementById('inst-categoria');
    var torneoWrap       = document.getElementById('inst-torneo-wrap');

    if (!tipoSelect || !categoriaSelect) return;

    function sincronizarTorneo() {
        if (!torneoWrap) return;
        torneoWrap.style.display = categoriaSelect.value === 'ingreso_torneo' ? '' : 'none';
    }

    function sincronizarCategorias() {
        var tipo = tipoSelect.value;
        var tieneSeleccionValida = false;
        var primeraVisible = null;

        Array.prototype.forEach.call(categoriaSelect.options, function (opt) {
            var visible = opt.dataset.tipo === tipo;
            opt.hidden = !visible;
            opt.disabled = !visible;
            if (visible && !primeraVisible) primeraVisible = opt.value;
            if (visible && opt.selected) tieneSeleccionValida = true;
        });

        if (!tieneSeleccionValida && primeraVisible) {
            categoriaSelect.value = primeraVisible;
        }

        sincronizarTorneo();
    }

    tipoSelect.addEventListener('change', sincronizarCategorias);
    categoriaSelect.addEventListener('change', sincronizarTorneo);

    sincronizarCategorias();
});
