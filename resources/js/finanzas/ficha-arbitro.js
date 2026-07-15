document.addEventListener('DOMContentLoaded', function () {

    // ── Modal de abono compartido: cada fila lo apunta a su propio movimiento ──
    var form   = document.querySelector('[data-abono-form]');
    var monto  = document.querySelector('[data-abono-monto]');
    var hint   = document.querySelector('[data-abono-hint]');

    document.querySelectorAll('[data-abono-url]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var saldo = parseFloat(btn.dataset.abonoSaldo || '0');

            if (form) form.action = btn.dataset.abonoUrl;
            if (monto) monto.max = saldo;
            if (hint) hint.textContent = 'Saldo pendiente: $' + saldo.toLocaleString('es-CO', { maximumFractionDigits: 0 });
        });
    });

    // ── Pagar nómina en lote: uno o varios partidos marcados en la tabla ──
    var checksNominaFicha = document.querySelectorAll('.check-nomina-ficha');
    var barraPago  = document.getElementById('ficha-pago-bar');
    var totalPagoEl = document.getElementById('ficha-pago-total');
    var resumenPagoEl = document.getElementById('ficha-pago-resumen');
    var hiddenInputsWrap = document.getElementById('ficha-pago-hidden-inputs');

    function formatoMonedaFicha(valor) {
        return '$' + valor.toLocaleString('es-CO', { maximumFractionDigits: 0 });
    }

    function seleccionadosNomina() {
        return Array.prototype.filter.call(checksNominaFicha, function (c) { return c.checked; });
    }

    function actualizarBarraPago() {
        if (!barraPago) return;
        var seleccionados = seleccionadosNomina();
        var total = seleccionados.reduce(function (acc, c) { return acc + parseFloat(c.dataset.saldo || '0'); }, 0);
        barraPago.style.display = seleccionados.length ? 'flex' : 'none';
        if (totalPagoEl) totalPagoEl.textContent = formatoMonedaFicha(total);
    }

    checksNominaFicha.forEach(function (chk) { chk.addEventListener('change', actualizarBarraPago); });

    document.querySelectorAll('[data-open-modal="pagar-nomina"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var seleccionados = seleccionadosNomina();
            var total = seleccionados.reduce(function (acc, c) { return acc + parseFloat(c.dataset.saldo || '0'); }, 0);

            if (resumenPagoEl) {
                resumenPagoEl.textContent = seleccionados.length + ' partido(s) seleccionados — total ' + formatoMonedaFicha(total);
            }
            if (hiddenInputsWrap) {
                hiddenInputsWrap.innerHTML = '';
                seleccionados.forEach(function (c) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'idsMovimientos[]';
                    input.value = c.value;
                    hiddenInputsWrap.appendChild(input);
                });
            }
        });
    });

});
