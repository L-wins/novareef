document.addEventListener('DOMContentLoaded', function () {

    var form = document.getElementById('form-cobro-masivo');
    if (!form) return;

    // ── Resumen del cargo en el <summary> cuando el bloque está colapsado ──
    var detallesCargo   = document.getElementById('cm-datos-cargo');
    var resumenCargoEl   = form.querySelector('[data-cm-resumen-cargo]');
    var categoriaSelect  = document.getElementById('categoria');
    var conceptoInput    = document.getElementById('concepto');
    var montoDefaultEl   = document.getElementById('montoTotal');

    function actualizarResumenCargo() {
        if (!resumenCargoEl) return;

        var partes = [];
        var etiquetaCategoria = categoriaSelect && categoriaSelect.selectedIndex > 0
            ? categoriaSelect.options[categoriaSelect.selectedIndex].text
            : null;

        if (etiquetaCategoria) partes.push(etiquetaCategoria);
        if (conceptoInput && conceptoInput.value.trim()) partes.push(conceptoInput.value.trim());
        if (montoDefaultEl && montoDefaultEl.value) {
            partes.push('$' + parseFloat(montoDefaultEl.value).toLocaleString('es-CO', { maximumFractionDigits: 0 }));
        }

        resumenCargoEl.textContent = partes.length ? partes.join(' · ') : 'Sin definir aún';
    }

    if (categoriaSelect) categoriaSelect.addEventListener('change', actualizarResumenCargo);
    if (conceptoInput) conceptoInput.addEventListener('input', actualizarResumenCargo);
    if (montoDefaultEl) montoDefaultEl.addEventListener('input', actualizarResumenCargo);
    actualizarResumenCargo();

    // Se abre solo al primer clic sobre el resumen colapsado — si el usuario
    // ya lo cerró después de configurarlo, no se le vuelve a forzar abierto.
    if (detallesCargo && !detallesCargo.open) {
        detallesCargo.addEventListener('toggle', actualizarResumenCargo);
    }

    var filas                  = Array.prototype.slice.call(form.querySelectorAll('[data-cm-fila]'));
    var filtroInput            = form.querySelector('[data-cm-filtro]');
    var contador               = form.querySelector('[data-cm-contador]');
    var btnSeleccionarVisibles = form.querySelector('[data-cm-seleccionar-visibles]');
    var btnQuitarSeleccion     = form.querySelector('[data-cm-quitar-seleccion]');
    var montoDefaultInput      = document.getElementById('montoTotal');

    function filaVisible(fila) {
        return !fila.classList.contains('is-oculta');
    }

    function actualizarContador() {
        var total = filas.filter(function (fila) {
            var chk = fila.querySelector('[data-cm-incluir]');
            return chk && chk.checked;
        }).length;

        if (contador) contador.textContent = total + ' de ' + filas.length + ' seleccionados';
    }

    // ── Filtro por nombre ──
    if (filtroInput) {
        filtroInput.addEventListener('input', function () {
            var termino = filtroInput.value.trim().toLowerCase();
            filas.forEach(function (fila) {
                var coincide = !termino || fila.dataset.nombre.indexOf(termino) !== -1;
                fila.classList.toggle('is-oculta', !coincide);
            });
        });
    }

    // ── Seleccionar / quitar selección (solo filas visibles) ──
    if (btnSeleccionarVisibles) {
        btnSeleccionarVisibles.addEventListener('click', function () {
            filas.filter(filaVisible).forEach(function (fila) {
                var chk = fila.querySelector('[data-cm-incluir]');
                if (chk) chk.checked = true;
            });
            actualizarContador();
        });
    }

    if (btnQuitarSeleccion) {
        btnQuitarSeleccion.addEventListener('click', function () {
            filas.filter(filaVisible).forEach(function (fila) {
                var chk = fila.querySelector('[data-cm-incluir]');
                if (chk) chk.checked = false;
            });
            actualizarContador();
        });
    }

    // ── Toggle de campos de pago por fila ──
    filas.forEach(function (fila) {
        var chkIncluir = fila.querySelector('[data-cm-incluir]');
        var chkYaPago  = fila.querySelector('[data-cm-yapago]');
        var camposPago = fila.querySelector('[data-cm-pago-fields]');
        var montoInput = fila.querySelector('[data-cm-monto]');

        if (chkIncluir) chkIncluir.addEventListener('change', actualizarContador);

        if (chkYaPago && camposPago) {
            chkYaPago.addEventListener('change', function () {
                camposPago.classList.toggle('is-visible', chkYaPago.checked);
            });
        }

        // Un monto editado a mano ya no se sobreescribe con "aplicar a todos".
        if (montoInput) {
            montoInput.addEventListener('input', function () {
                montoInput.dataset.editado = '1';
            });
        }
    });

    // ── Monto por defecto: precarga inicial + sincroniza filas no editadas ──
    function aplicarMontoDefault() {
        if (!montoDefaultInput) return;
        filas.forEach(function (fila) {
            var montoInput = fila.querySelector('[data-cm-monto]');
            if (montoInput && montoInput.dataset.editado !== '1') {
                montoInput.value = montoDefaultInput.value;
            }
        });
    }

    if (montoDefaultInput) {
        montoDefaultInput.addEventListener('input', aplicarMontoDefault);
    }

    actualizarContador();
});
