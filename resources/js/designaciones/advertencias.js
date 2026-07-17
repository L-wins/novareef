/**
 * Construcción y render de advertencias de asignación (disponibilidad,
 * suspensión, choques de horario) — compartido entre asignar y reasignar.
 */

/** "45 min" si es menos de una hora, "1 h" / "1 h 30 min" en caso contrario. */
export function formatearDuracion(minutos) {
    if (minutos < 60) return `${minutos} min`;

    const horas = Math.floor(minutos / 60);
    const resto = minutos % 60;

    return resto === 0 ? `${horas} h` : `${horas} h ${resto} min`;
}

export function pluralizarPartidos(cantidad) {
    return `${cantidad} partido${cantidad === 1 ? '' : 's'}`;
}

/**
 * Advertencias de asignación: cada una lleva un "tipo" semántico (no un
 * emoji) para que el confirm y los badges se pinten con los tokens de
 * color del tema (--nv-warning/--nv-danger/--nv-text-3), no un carácter fijo.
 */
export function construirAdvertencias(arbitro) {
    const advertencias = [];

    if (arbitro.esSuspendido) {
        advertencias.push({ tipo: 'danger', texto: 'Este árbitro está <strong>suspendido</strong>.' });
    }
    if (arbitro.disponibilidad === 'extraordinaria') {
        advertencias.push({ tipo: 'danger', texto: 'El árbitro reportó <strong>indisponibilidad extraordinaria</strong> para esta fecha.' });
    }
    if (arbitro.disponibilidad === 'no_disponible') {
        advertencias.push({ tipo: 'danger', texto: 'El árbitro se declaró <strong>no disponible</strong> para este día.' });
    }
    if (arbitro.disponibilidad === 'otra_franja') {
        const horaEste = window.partidoHora ? ` y este partido es a las <strong>${window.partidoHora}</strong>` : '';
        advertencias.push({
            tipo:  'warning',
            texto: `El árbitro reportó disponibilidad en <strong>${arbitro.franjaLabel ?? 'otra franja'}</strong>${horaEste} — la franja no coincide.`,
        });
    }
    if (arbitro.disponibilidad === 'sin_reporte') {
        advertencias.push({ tipo: 'info', texto: 'El árbitro <strong>no reportó disponibilidad</strong> para esta semana.' });
    }
    if (arbitro.advertenciaTiempo) {
        const horaEste = window.partidoHora ? ` (este partido es a las <strong>${window.partidoHora}</strong>)` : '';

        advertencias.push({
            tipo:  'warning',
            texto: `Este árbitro ya tiene otro partido asignado a las <strong>${arbitro.horaPartidoCercano}</strong>${horaEste}, con ${formatearDuracion(arbitro.minutosAlPartidoCercano)} de diferencia. ¿Seguro que quieres asignarlo también aquí?`,
        });
    }
    if (arbitro.partidosHoy > 0) {
        advertencias.push({
            tipo:  'info',
            texto: `Este árbitro ya tiene <strong>${pluralizarPartidos(arbitro.partidosHoy)}</strong> asignado${arbitro.partidosHoy === 1 ? '' : 's'} este mismo día. `
                 + `Con esta asignación tendría <strong>${pluralizarPartidos(arbitro.partidosHoy + 1)}</strong> hoy.`,
        });
    }

    return advertencias;
}

export function renderAdvertenciasHtml(advertencias) {
    if (!advertencias.length) return '';

    const items = advertencias
        .map(a => `<li class="nova-swal-advertencias__item nova-swal-advertencias__item--${a.tipo}">${a.texto}</li>`)
        .join('');

    return `<ul class="nova-swal-advertencias">${items}</ul>`;
}
