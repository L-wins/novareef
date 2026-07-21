document.addEventListener('DOMContentLoaded', function () {

    // ── Modal "Registrar": la categoría depende del tipo elegido, y el
    //    torneo solo aplica a la categoría "ingreso_torneo" ──
    var tipoSelect      = document.getElementById('inst-tipo');
    var categoriaSelect = document.getElementById('inst-categoria');
    var torneoWrap       = document.getElementById('inst-torneo-wrap');
    var categoriasPorTipo = window.institucionalCategorias || { ingreso: [], egreso: [] };

    if (!tipoSelect || !categoriaSelect) return;

    function sincronizarTorneo() {
        if (!torneoWrap) return;
        torneoWrap.style.display = categoriaSelect.value === 'ingreso_torneo' ? '' : 'none';
    }

    // Con Choices.js activo, el <select> original queda oculto y sus
    // options ya no reflejan nada en pantalla — filtrar por tipo se hace
    // reemplazando las choices de la instancia (setChoices), no tocando
    // option.hidden sobre el select nativo subyacente.
    function sincronizarCategorias(valorPreferido) {
        var tipo     = tipoSelect.value;
        var opciones = categoriasPorTipo[tipo] || [];
        var instancia = categoriaSelect._choicesInstance;

        var hayPreferido = valorPreferido && opciones.some(function (o) { return o.value === valorPreferido; });
        var valorFinal   = hayPreferido ? valorPreferido : (opciones[0] ? opciones[0].value : '');

        var choices = opciones.map(function (o) {
            return { value: o.value, label: o.label, selected: o.value === valorFinal };
        });

        if (instancia) {
            instancia.clearStore();
            instancia.setChoices(choices, 'value', 'label', true);
        } else {
            categoriaSelect.innerHTML = '';
            choices.forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.label;
                opt.selected = o.selected;
                categoriaSelect.appendChild(opt);
            });
        }

        sincronizarTorneo();
    }

    tipoSelect.addEventListener('change', function () { sincronizarCategorias(); });
    categoriaSelect.addEventListener('change', sincronizarTorneo);

    // Primera carga: respeta la categoría precargada por el servidor
    // (old()/request()) — antes de tocar nada, esa es la fuente de verdad.
    sincronizarCategorias(categoriaSelect.value);
});
