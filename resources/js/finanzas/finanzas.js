document.addEventListener('DOMContentLoaded', function () {

    // ── Formulario de movimiento: mostrar campos según tipo/categoría ──
    var tipoSelect      = document.getElementById('tipoMovimiento');
    var categoriaSelect = document.getElementById('categoria');

    var categoriasPorTipo = {
        ingreso: [
            { value: 'ingreso_torneo', label: 'Ingreso por torneo' },
            { value: 'mensualidad',    label: 'Mensualidad (cuota)' },
            { value: 'multa',          label: 'Multa a árbitro' },
            { value: 'otro_ingreso',   label: 'Otro ingreso' },
        ],
        egreso: [
            { value: 'nomina_arbitro',      label: 'Nómina de árbitros' },
            { value: 'arbitro_externo',     label: 'Árbitro externo' },
            { value: 'gasto_fijo',          label: 'Gasto fijo' },
            { value: 'gasto_institucional', label: 'Gasto institucional' },
            { value: 'gasto_vario',         label: 'Gasto vario / extraordinario' },
        ],
    };

    var camposArbitro         = document.querySelectorAll('[data-campo-condicional="arbitro"]');
    var camposArbitroExterno  = document.querySelectorAll('[data-campo-condicional="arbitro-externo"]');
    var camposTorneo          = document.querySelectorAll('[data-campo-condicional="torneo"]');

    function toggle(elements, visible) {
        elements.forEach(function (el) {
            el.classList.toggle('is-visible', visible);
        });
    }

    function sincronizarCategorias() {
        if (!categoriaSelect) return;
        var opciones = categoriasPorTipo[tipoSelect.value] || [];
        var valorPrevio = categoriaSelect.value;

        var choicesData = [{ value: '', label: '— Selecciona —', selected: !valorPrevio }].concat(
            opciones.map(function (opt) {
                return { value: opt.value, label: opt.label, selected: opt.value === valorPrevio };
            })
        );

        // Choices.js ya envolvió el <select> original (initNovaSelects es
        // idempotente y lo ignora en llamadas posteriores) — para refrescar
        // las opciones visibles hay que usar su propia API, no basta con
        // reescribir el innerHTML del <select> escondido.
        var instance = categoriaSelect._choicesInstance;
        if (instance) {
            instance.setChoices(choicesData, 'value', 'label', true);
        } else {
            categoriaSelect.innerHTML = '';
            choicesData.forEach(function (opt) {
                var el = document.createElement('option');
                el.value = opt.value;
                el.textContent = opt.label;
                el.selected = opt.selected;
                categoriaSelect.appendChild(el);
            });
        }

        sincronizarCamposCondicionales();
    }

    function sincronizarCamposCondicionales() {
        if (!categoriaSelect) return;
        var categoria = categoriaSelect.value;

        toggle(camposArbitro, ['nomina_arbitro', 'multa'].includes(categoria));
        toggle(camposArbitroExterno, categoria === 'arbitro_externo');
        toggle(camposTorneo, categoria === 'ingreso_torneo');
    }

    if (tipoSelect && categoriaSelect) {
        // Llegar desde "Registrar ingreso de este torneo" (ficha de torneo)
        // precarga la categoría antes de poblar el select por primera vez.
        var formMovimiento  = categoriaSelect.closest('form');
        var categoriaPreset = formMovimiento ? formMovimiento.dataset.categoriaPreset : null;
        if (categoriaPreset) categoriaSelect.value = categoriaPreset;

        tipoSelect.addEventListener('change', sincronizarCategorias);
        categoriaSelect.addEventListener('change', sincronizarCamposCondicionales);
        sincronizarCategorias();
    }

    // ── Pago acumulado: totales en vivo según checkboxes marcados ──
    var checksNomina = document.querySelectorAll('.check-nomina');
    var checksDeuda   = document.querySelectorAll('.check-deuda');
    var totalNominaEl = document.getElementById('total-nomina');
    var totalDeudasEl = document.getElementById('total-deudas');
    var totalNetoEl    = document.getElementById('total-neto');

    function formatoMoneda(valor) {
        return '$' + valor.toLocaleString('es-CO', { maximumFractionDigits: 0 });
    }

    function sumarChecks(checks) {
        var total = 0;
        checks.forEach(function (chk) {
            if (chk.checked) total += parseFloat(chk.dataset.saldo || '0');
        });
        return total;
    }

    function recalcularPagoAcumulado() {
        if (!totalNominaEl) return;
        var totalNomina = sumarChecks(checksNomina);
        var totalDeudas = sumarChecks(checksDeuda);
        var neto = totalNomina - totalDeudas;

        totalNominaEl.textContent = formatoMoneda(totalNomina);
        totalDeudasEl.textContent = formatoMoneda(totalDeudas);
        totalNetoEl.textContent   = formatoMoneda(neto);
        totalNetoEl.style.color   = neto < 0 ? '#f87171' : '';
    }

    checksNomina.forEach(function (chk) { chk.addEventListener('change', recalcularPagoAcumulado); });
    checksDeuda.forEach(function (chk) { chk.addEventListener('change', recalcularPagoAcumulado); });
    recalcularPagoAcumulado();

    // ── Reportes: atajos de rango de fechas (mes/trimestre/año actual) ──
    document.querySelectorAll('[data-atajo-reporte]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var hoy = new Date();
            var desde, hasta;

            if (btn.dataset.atajoReporte === 'mes') {
                desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
                hasta = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
            } else if (btn.dataset.atajoReporte === 'trimestre') {
                var inicioTrimestre = Math.floor(hoy.getMonth() / 3) * 3;
                desde = new Date(hoy.getFullYear(), inicioTrimestre, 1);
                hasta = new Date(hoy.getFullYear(), inicioTrimestre + 3, 0);
            } else {
                desde = new Date(hoy.getFullYear(), 0, 1);
                hasta = new Date(hoy.getFullYear(), 11, 31);
            }

            function formatoISO(fecha) {
                return fecha.getFullYear() + '-' + String(fecha.getMonth() + 1).padStart(2, '0') + '-' + String(fecha.getDate()).padStart(2, '0');
            }

            var inputDesde = document.getElementById('reporte-desde');
            var inputHasta = document.getElementById('reporte-hasta');
            if (inputDesde && inputHasta) {
                inputDesde.value = formatoISO(desde);
                inputHasta.value = formatoISO(hasta);
                inputDesde.closest('form').submit();
            }
        });
    });

    // ── Modal de abono (mostrar/ocultar) ──
    document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = document.getElementById('modal-' + btn.dataset.openModal);
            if (modal) modal.style.display = 'flex';
        });
    });
    document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = btn.closest('.nova-modal-overlay');
            if (modal) modal.style.display = 'none';
        });
    });

});
