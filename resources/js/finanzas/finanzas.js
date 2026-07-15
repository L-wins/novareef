document.addEventListener('DOMContentLoaded', function () {

    // ── Balance: filtro de búsqueda por árbitro ──
    var balanceFiltro = document.querySelector('[data-balance-filtro]');
    var balanceFilas   = document.querySelectorAll('[data-balance-fila]');

    if (balanceFiltro) {
        balanceFiltro.addEventListener('input', function () {
            var termino = balanceFiltro.value.trim().toLowerCase();
            balanceFilas.forEach(function (fila) {
                var coincide = !termino || fila.dataset.nombre.indexOf(termino) !== -1;
                fila.classList.toggle('is-oculta', !coincide);
            });
        });
    }

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

    // ── Modal de historial: cada botón trae su propio <template> con el
    //    contenido ya renderizado por el servidor, solo se copia al modal ──
    document.querySelectorAll('[data-historial-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tpl  = document.getElementById(btn.dataset.historialTarget);
            var body = document.getElementById('historial-modal-body');
            if (tpl && body) body.innerHTML = tpl.innerHTML;
        });
    });

});
