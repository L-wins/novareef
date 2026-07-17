/**
 * Búsqueda de árbitros con debounce — dropdown de resultados usado tanto
 * para asignar (partido en borrador) como para reasignar (ya publicado).
 */
import { asignarArbitro, reasignarArbitro } from './asignacion.js';
import { pluralizarPartidos } from './advertencias.js';

/** Minúsculas y sin tildes/diacríticos, para comparar como escribe la gente. */
function normalizarTexto(s) {
    return (s ?? '').toString().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
}

/**
 * Qué tan bien coincide un candidato con lo escrito — menor es mejor.
 * Sin esto, el orden que llega del backend (por disponibilidad, no por
 * texto) decidía qué 10 quedaban visibles: un nombre casi exacto podía
 * quedar fuera del top 10 detrás de coincidencias sueltas con mejor
 * disponibilidad (ej. buscar "Arbitro Asocafa 1" mostraba "...11", "...21",
 * "...41" antes que el "1" exacto, porque el token "1" también matchea
 * cualquier carné/documento que lo contenga en cualquier posición).
 */
function relevancia(a, queryNorm) {
    const nombre = normalizarTexto(a.nombreUsuario ?? '');
    const carnet = normalizarTexto(a.codigoCarnet ?? '');
    const doc    = normalizarTexto(a.numeroDocumento ?? '');

    if (nombre === queryNorm) return 0;
    if (nombre.startsWith(queryNorm)) return 1;
    if (nombre.includes(queryNorm)) return 2;
    if (carnet === queryNorm || doc === queryNorm) return 3;
    if (carnet.startsWith(queryNorm) || doc.startsWith(queryNorm)) return 4;
    return 5; // coincide por tokens sueltos en algún campo, nada más específico
}

/**
 * Píldora de disponibilidad: va junto al nombre (no en la fila de badges
 * secundarios) porque es el dato más importante para decidir a quién asignar.
 */
function buildBadgeDisponibilidad(a) {
    if (a.disponibilidad === 'disponible') {
        return `<span class="arbitro-result__badge badge-disponible arbitro-result__badge--disponibilidad"><i class="fa-solid fa-circle-check"></i>${a.franjaLabel ?? 'Disponible'}</span>`;
    }
    if (a.disponibilidad === 'otra_franja') {
        return `<span class="arbitro-result__badge badge-otra-franja arbitro-result__badge--disponibilidad"><i class="fa-solid fa-triangle-exclamation"></i>Disponible en ${a.franjaLabel ?? 'otra franja'}</span>`;
    }
    if (a.disponibilidad === 'extraordinaria') {
        return `<span class="arbitro-result__badge badge-extraordinaria arbitro-result__badge--disponibilidad"><i class="fa-solid fa-circle-xmark"></i>Indisponible (extraordinaria)</span>`;
    }
    if (a.disponibilidad === 'no_disponible') {
        return `<span class="arbitro-result__badge badge-extraordinaria arbitro-result__badge--disponibilidad"><i class="fa-solid fa-circle-xmark"></i>No disponible</span>`;
    }
    if (a.disponibilidad === 'sin_reporte') {
        return `<span class="arbitro-result__badge badge-sin-reporte arbitro-result__badge--disponibilidad"><i class="fa-regular fa-circle-question"></i>Sin disponibilidad</span>`;
    }
    return '';
}

function buildBadgesSecundarios(a) {
    const badges = [];

    if (a.advertenciaTiempo)  badges.push(`<span class="arbitro-result__badge badge-warn-tiempo">Ya asignado a las ${a.horaPartidoCercano}</span>`);
    if (a.partidosHoy > 0)    badges.push(`<span class="arbitro-result__badge badge-sobrecarga">Tendría ${pluralizarPartidos(a.partidosHoy + 1)} este día</span>`);
    if (a.esSuspendido)       badges.push(`<span class="arbitro-result__badge badge-suspendido">Suspendido</span>`);

    return badges.join('');
}

let buscarArbitrosSecuencia = 0;

export async function buscarArbitros(partidoId, query, rolId, resultsEl, desigReasignar = null) {
    if (!window.buscarUrl) return;

    // Si esta respuesta llega después de una búsqueda más reciente (el
    // usuario siguió escribiendo mientras la petición estaba en vuelo), se
    // descarta — evita que resultados desactualizados "parpadeen" encima de
    // lo que el usuario ya escribió.
    const miSecuencia = ++buscarArbitrosSecuencia;

    try {
        const r    = await fetch(`${window.buscarUrl}?q=${encodeURIComponent(query)}`);
        const data = await r.json();

        if (miSecuencia !== buscarArbitrosSecuencia) return;

        resultsEl.innerHTML = '';

        // Cada palabra del query debe aparecer en algún campo del árbitro
        // (nombre, carnet, documento o categoría), sin distinguir tildes ni
        // mayúsculas — "jose ramirez" encuentra a "José RAMÍREZ".
        const queryNorm = normalizarTexto(query.trim());
        const tokens = queryNorm.split(/\s+/).filter(Boolean);

        const filtrados = data
            .filter(a => {
                const haystack = normalizarTexto(
                    `${a.nombreUsuario ?? ''} ${a.codigoCarnet ?? ''} ${a.numeroDocumento ?? ''} ${a.nombreCategoria ?? ''}`
                );
                return tokens.every(t => haystack.includes(t));
            })
            // Sort estable: a igual relevancia, se conserva el orden de
            // disponibilidad que ya trae el backend.
            .sort((a, b) => relevancia(a, queryNorm) - relevancia(b, queryNorm));

        if (filtrados.length === 0) {
            resultsEl.innerHTML = '<div style="padding:.75rem 1rem;font-size:.82rem;color:var(--disp-text-2)">Sin resultados</div>';
            resultsEl.style.display = 'block';
            return;
        }

        filtrados.slice(0, 10).forEach(a => {
            const item = document.createElement('div');
            item.className = `arbitro-result${a.yaAsignado ? ' ya-asignado' : ''}`;
            item.innerHTML = `
                <div class="arbitro-result__avatar">${(a.nombreUsuario ?? '?')[0].toUpperCase()}</div>
                <div class="arbitro-result__info">
                    <div class="arbitro-result__nombre-row">
                        <span class="arbitro-result__nombre">${a.nombreUsuario ?? '—'}</span>
                        ${buildBadgeDisponibilidad(a)}
                    </div>
                    <div class="arbitro-result__meta">${a.codigoCarnet ?? ''} · ${a.nombreCategoria ?? ''}</div>
                    <div class="arbitro-result__badges">${buildBadgesSecundarios(a)}</div>
                </div>
            `;

            if (!a.yaAsignado) {
                item.addEventListener('click', () => {
                    if (desigReasignar) {
                        reasignarArbitro(desigReasignar, a);
                    } else {
                        asignarArbitro(partidoId, a, rolId);
                    }
                });
            }

            resultsEl.appendChild(item);
        });

        if (filtrados.length > 10) {
            const nota = document.createElement('div');
            nota.style.cssText = 'padding:.5rem 1rem;font-size:.76rem;color:var(--disp-text-2);text-align:center';
            nota.textContent = `+${filtrados.length - 10} más — afina la búsqueda para verlos`;
            resultsEl.appendChild(nota);
        }

        resultsEl.style.display = 'block';
    } catch (e) {
        console.error('buscarArbitros error', e);
    }
}
