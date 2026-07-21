/*
   Preview del importador de partidos desde Word — los selects/fechas de
   cada fila ya se inicializan globalmente via window.initNovaSelects()
   (app.js, en DOMContentLoaded). Este módulo solo agrega la confirmación
   antes de "Confirmar importación" (crea partidos reales) y el toggle
   visual de "Incluir" por fila.
*/

document.addEventListener('DOMContentLoaded', function () {
    const btnConfirmar = document.getElementById('btn-confirmar-importacion');
    const form          = document.getElementById('form-importar-preview');

    if (btnConfirmar && form) {
        btnConfirmar.addEventListener('click', async function (e) {
            if (btnConfirmar.dataset.confirmado === '1') return;
            e.preventDefault();

            const incluidos = form.querySelectorAll('.importar-fila input[type="checkbox"]:checked').length;

            if (incluidos === 0) {
                window.novaAlert?.error('No hay ningún partido marcado para incluir.');
                return;
            }

            const resultado = await window.novaAlert.confirm({
                titulo: '¿Confirmar importación?',
                texto: `Se crearán ${incluidos} partido(s) en estado Borrador. Podrás designar árbitros normalmente después.`,
                icono: 'question',
                confirmColor: '#4f8ef7',
                iconColor: '#4f8ef7',
                confirmarTexto: 'Sí, importar',
            });

            if (resultado.isConfirmed) {
                btnConfirmar.dataset.confirmado = '1';
                form.requestSubmit(btnConfirmar);
            }
        });
    }

    document.querySelectorAll('.importar-fila').forEach(function (fila) {
        const checkbox = fila.querySelector('input[type="checkbox"]');
        if (!checkbox) return;

        const camposEditables = fila.querySelectorAll('input[type="text"], select');

        const aplicarEstado = () => {
            camposEditables.forEach((campo) => {
                campo.classList.toggle('importar-campo-deshabilitado', !checkbox.checked);
            });
        };

        checkbox.addEventListener('change', aplicarEstado);
        aplicarEstado();
    });

    // ── Campo hora del preview: texto plano con auto-formato "HH:MM" ──
    // Se evitó a propósito el widget de hora de Flatpickr aquí — sus dos
    // <input type="number"> con flechas de incremento no caben en una celda
    // de tabla angosta (se ven truncados). Un input de texto simple con
    // auto-inserción de ":" es más robusto en ese espacio.
    document.querySelectorAll('.importar-input-hora').forEach(function (input) {
        input.addEventListener('input', function () {
            let digitos = input.value.replace(/\D/g, '').slice(0, 4);
            if (digitos.length >= 3) {
                input.value = digitos.slice(0, 2) + ':' + digitos.slice(2);
            } else {
                input.value = digitos;
            }
        });
    });
});
