/**
 * NovaReef — Designaciones / Disponibilidad
 * Controla el estado visual de los cards de disponibilidad
 * y la validación del formulario de indisponibilidad extraordinaria.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── Colores por franja horaria ───────────────────────────────────────────
    var FRANJA_COLORES = {
        'am':          '#3b82f6',
        'pm':          '#8b5cf6',
        'noche':       '#60a5fa',
        'am_pm':       '#06b6d4',
        'am_noche':    '#10b981',
        'pm_noche':    '#a78bfa',
        'todo_el_dia': '#4f8ef7',
    };

    var FRANJA_LABELS = {
        'am':          'AM',
        'pm':          'PM',
        'noche':       'Noche',
        'am_pm':       'AM - PM',
        'am_noche':    'AM - Noche',
        'pm_noche':    'PM - Noche',
        'todo_el_dia': 'Todo el día',
    };

    // ── Actualizar estado visual del card al cambiar el select ───────────────
    document.querySelectorAll('.disp-day-card select[data-nova-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            var card   = select.closest('.disp-day-card');
            var badge  = card ? card.querySelector('[data-state-badge]') : null;
            var franja = select.value;
            if (!card) return;

            if (franja && franja !== '') {
                var color = FRANJA_COLORES[franja] || '#22c55e';
                card.style.borderColor = color;
                card.classList.add('is-available');

                if (badge) {
                    badge.innerHTML =
                        '<i class="fa-solid fa-circle-check" style="color:' + color + ';font-size:0.8rem;"></i>' +
                        '<span style="color:' + color + ';">' + (FRANJA_LABELS[franja] || franja) + '</span>';
                }
            } else {
                card.style.borderColor = '';
                card.classList.remove('is-available');

                if (badge) {
                    badge.innerHTML =
                        '<i class="fa-solid fa-circle-xmark" style="color:#4a5568;font-size:0.8rem;"></i>' +
                        '<span style="color:#8892a4;">No disponible</span>';
                }
            }
        });
    });

    // ── Aplicar color de franja a los cards que ya tienen valor guardado ─────
    document.querySelectorAll('.disp-day-card[data-franja]').forEach(function (card) {
        var franja = card.dataset.franja;
        if (franja && franja !== '' && !card.classList.contains('is-past')) {
            var color = FRANJA_COLORES[franja];
            if (color) card.style.borderColor = color;
        }
    });

    // ── AJAX submit — formulario semanal ─────────────────────────────────────
    var formDisponibilidad = document.getElementById('form-disponibilidad');

    if (formDisponibilidad) {
        formDisponibilidad.addEventListener('submit', async function (e) {
            e.preventDefault();

            var btn   = document.querySelector('button[form="form-disponibilidad"]');
            var label = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled  = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>&nbsp;Guardando…';
            }

            var token = (document.querySelector('input[name="_token"]') || {}).value || '';

            try {
                var res  = await fetch(formDisponibilidad.action, {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body:    new FormData(formDisponibilidad),
                });
                var json = await res.json();

                if (json.success) {
                    if (window.novaAlert) novaAlert.success(json.message || 'Disponibilidad guardada');
                    // Recargar para mostrar el estado bloqueado
                    setTimeout(function () { window.location.reload(); }, 1500);
                } else {
                    if (window.novaAlert) novaAlert.error(json.message || 'Error al guardar');
                }
            } catch (_err) {
                if (window.novaAlert) novaAlert.error('Error de red. Intenta de nuevo.');
            } finally {
                if (btn) { btn.disabled = false; btn.innerHTML = label; }
            }
        });
    }

    // ── Contador caracteres — motivo extraordinaria ──────────────────────────
    var notasExtra = document.getElementById('motivo-extraordinaria');
    var contExtra  = document.getElementById('contador-extraordinaria');
    if (notasExtra && contExtra) {
        function syncContExtra() {
            var len = notasExtra.value.length;
            contExtra.textContent = len + '/300';
            contExtra.style.color = len >= 270 ? '#ef4444' : '#8892a4';
        }
        notasExtra.addEventListener('input', syncContExtra);
        syncContExtra();
    }

    // ── Validación mínimo 10 chars en el motivo ──────────────────────────────
    var btnExtra  = document.getElementById('btn-extraordinaria');
    var formExtra = btnExtra ? btnExtra.closest('form') : null;

    if (formExtra && notasExtra) {
        formExtra.addEventListener('submit', function (e) {
            if (notasExtra.value.trim().length < 10) {
                e.preventDefault();
                if (window.novaAlert) {
                    novaAlert.error('El motivo debe tener al menos 10 caracteres.');
                }
                notasExtra.focus();
            }
        });
    }

});
