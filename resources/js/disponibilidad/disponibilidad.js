/**
 * NovaReef — Módulo Disponibilidad
 * Maneja el submit AJAX del formulario de disponibilidad semanal
 * y el contador de caracteres de la indisponibilidad extraordinaria.
 */

document.addEventListener('DOMContentLoaded', function () {

    window.initNovaSelects?.();

    // ── Submit AJAX del formulario de disponibilidad semanal ──────────────────
    const form = document.getElementById('form-disponibilidad');
    const btn  = document.querySelector('[form="form-disponibilidad"]');

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
            }

            const payload = [];
            const selects = form.querySelectorAll('[name^="disponibilidades["]');

            // Agrupar fecha y franja por índice
            const grupos = {};
            selects.forEach(function (el) {
                const match = el.name.match(/disponibilidades\[(\d+)\]\[(\w+)\]/);
                if (!match) return;
                const idx   = match[1];
                const campo = match[2];
                if (!grupos[idx]) grupos[idx] = {};
                grupos[idx][campo] = el.value;
            });

            Object.values(grupos).forEach(function (g) {
                if (g.fecha) payload.push({ fecha: g.fecha, franja: g.franja ?? '' });
            });

            try {
                const r = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                        ?? window.csrfToken ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ disponibilidades: payload }),
                });

                const data = await r.json();

                if (data.success) {
                    window.novaAlert.success(data.message ?? 'Disponibilidad guardada correctamente.');
                    setTimeout(() => location.reload(), 1400);
                } else {
                    window.novaAlert.error(data.message ?? 'No se pudo guardar la disponibilidad.');
                    resetBtn();
                }
            } catch (err) {
                console.error('disponibilidad submit error', err);
                window.novaAlert.error('Error de red. Intenta de nuevo.');
                resetBtn();
            }
        });
    }

    function resetBtn() {
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar disponibilidad';
    }

    // ── Contador de caracteres — indisponibilidad extraordinaria ──────────────
    const motivo   = document.getElementById('motivo-extraordinaria');
    const contador = document.getElementById('contador-extraordinaria');

    if (motivo && contador) {
        motivo.addEventListener('input', function () {
            contador.textContent = motivo.value.length + '/300';
        });
    }

});
