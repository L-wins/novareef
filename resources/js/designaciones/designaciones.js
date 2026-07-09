/**
 * NovaReef — Módulo Designaciones — M04 Bloque 3
 * Echo + Reverb + lógica de asignación, confirmación, rechazo y estado.
 */

// ── Laravel Echo + Reverb ─────────────────
import Echo   from 'laravel-echo';
import Pusher from 'pusher-js';
import { initDateDividers } from '../shared/date-divider.js';

window.Pusher = Pusher;

if (typeof window.Echo === 'undefined') {
    window.Echo = new Echo({
        broadcaster:        'reverb',
        key:                import.meta.env.VITE_REVERB_APP_KEY    ?? 'novareef-key',
        wsHost:             import.meta.env.VITE_REVERB_HOST       ?? 'localhost',
        wsPort:             import.meta.env.VITE_REVERB_PORT       ?? 8080,
        wssPort:            import.meta.env.VITE_REVERB_PORT       ?? 8080,
        forceTLS:           false,
        enabledTransports:  ['ws'],
        // La app vive en un subdirectorio (/novareef/public) — el default de Echo
        // ('/broadcasting/auth' relativo a la raíz del dominio) apunta mal ahí.
        authEndpoint: window.broadcastAuthEndpoint ?? '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        },
    });
}

// ── Suscripción en tiempo real ────────────
document.addEventListener('DOMContentLoaded', function () {

    window.initNovaSelects?.();
    initDateDividers();

    if (window.colegioId) {
        window.Echo.private(`colegio.${window.colegioId}.partidos`)
            .listen('.partido.actualizado', (e) => {
                actualizarCardPartido(e.partido);
            })
            .listen('.partido.critico', (e) => {
                marcarCardCritico(e.idPartido);
            });

        window.Echo.private(`colegio.${window.colegioId}.designaciones`)
            .listen('.designacion.actualizada', (e) => {
                actualizarRolCard(e);
            });
    }

    // ── Búsqueda de árbitros (show.blade)
    document.querySelectorAll('.arbitro-search').forEach(function (wrap) {
        const input        = wrap.querySelector('.arbitro-search__input');
        const results      = wrap.querySelector('.arbitro-search__results');
        const rolId        = wrap.dataset.rol;
        const partidoId    = wrap.dataset.partido;
        const desigReasignar = wrap.dataset.reasignar ?? null;

        let debounceTimer;

        input?.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = input.value.trim();

            if (q.length < 1) {
                results.style.display = 'none';
                results.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => buscarArbitros(partidoId, q, rolId, results, desigReasignar), 300);
        });

        // Cerrar al hacer clic fuera
        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) {
                results.style.display = 'none';
            }
        });
    });

    // ── Select torneo → cargar divisiones y sedes (partido-crear.blade) ──────
    // Escuchar tanto el evento nativo como el de Choices (dispara ambos en v11)
    const selTorneo = document.getElementById('sel-torneo');
    if (selTorneo) {
        selTorneo.addEventListener('change', function () {
            const torneoId = this.value;
            if (!torneoId) return;
            cargarDivisionesYSedes(torneoId);
        });
    }

    // ── Preview de roles según formato ───
    const selFormato = document.getElementById('sel-formato');
    if (selFormato) {
        selFormato.addEventListener('change', mostrarPreviewFormato);
    }

    // Si hay un torneo preseleccionado (old() en validación fallida), cargarlo
    if (selTorneo && selTorneo.value) {
        cargarDivisionesYSedes(selTorneo.value);
    }

    // ── Contadores de textarea ────────────
    configurarContador('obs-textarea', 'obs-counter');
    configurarContador('rechazo-motivo', 'rechazo-counter');

    // ── Botón confirmar rechazo ───────────
    const btnConfRechazo = document.getElementById('btn-confirmar-rechazo');
    if (btnConfRechazo) {
        btnConfRechazo.addEventListener('click', function () {
            const desigId = parseInt(this.dataset.desigId ?? '0');
            const motivo  = document.getElementById('rechazo-motivo')?.value?.trim() ?? '';

            if (!desigId) return;
            if (motivo.length < 10) {
                novaAlert.error('El motivo debe tener al menos 10 caracteres.');
                return;
            }
            rechazarDesignacion(desigId, motivo);
        });
    }
});

// ── Búsqueda de árbitros con debounce ────

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

let buscarArbitrosSecuencia = 0;

async function buscarArbitros(partidoId, query, rolId, resultsEl, desigReasignar = null) {
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

// ── Reasignar árbitro (partido ya publicado) ──
function toggleReasignarBusqueda(desigId) {
    const wrap = document.getElementById(`reasignar-search-${desigId}`);
    if (!wrap) return;

    const abrir = wrap.style.display === 'none';
    wrap.style.display = abrir ? 'block' : 'none';

    if (abrir) {
        wrap.querySelector('.arbitro-search__input')?.focus();
    } else {
        wrap.querySelector('.arbitro-search__results').style.display = 'none';
    }
}
window.toggleReasignarBusqueda = toggleReasignarBusqueda;

async function reasignarArbitro(desigId, arbitro) {
    const advertencias = construirAdvertencias(arbitro);

    const result = await novaAlert.confirm({
        titulo:         `Reasignar a ${arbitro.nombreUsuario}`,
        texto:          advertencias.length ? '' : 'Se notificará solo al árbitro nuevo. Los demás roles no se ven afectados.',
        icono:          advertencias.length ? 'warning' : 'question',
        confirmarTexto: 'Sí, reasignar',
        confirmColor:   '#4f8ef7',
        iconColor:      advertencias.length ? '#f59e0b' : '#4f8ef7',
        html:           renderAdvertenciasHtml(advertencias),
    });
    if (!result.isConfirmed) return;

    try {
        const r = await fetch(`${window.reasignarBase}/${desigId}/reasignar`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ idArbitro: arbitro.idArbitro }),
        });

        const data = await r.json();

        if (data.success) {
            novaAlert.success(`${arbitro.nombreUsuario} reasignado correctamente.`);
            setTimeout(() => location.reload(), 1200);
        } else {
            novaAlert.error(data.message ?? 'Error al reasignar árbitro.');
        }
    } catch (e) {
        novaAlert.error('Error de red al reasignar árbitro.');
    }
}
window.reasignarArbitro = reasignarArbitro;

/**
 * Advertencias de asignación: cada una lleva un "tipo" semántico (no un
 * emoji) para que el confirm y los badges se pinten con los tokens de
 * color del tema (--nv-warning/--nv-danger/--nv-text-3), no un carácter fijo.
 */
function construirAdvertencias(arbitro) {
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

function pluralizarPartidos(cantidad) {
    return `${cantidad} partido${cantidad === 1 ? '' : 's'}`;
}

/** "45 min" si es menos de una hora, "1 h" / "1 h 30 min" en caso contrario. */
function formatearDuracion(minutos) {
    if (minutos < 60) return `${minutos} min`;

    const horas = Math.floor(minutos / 60);
    const resto = minutos % 60;

    return resto === 0 ? `${horas} h` : `${horas} h ${resto} min`;
}

function renderAdvertenciasHtml(advertencias) {
    if (!advertencias.length) return '';

    const items = advertencias
        .map(a => `<li class="nova-swal-advertencias__item nova-swal-advertencias__item--${a.tipo}">${a.texto}</li>`)
        .join('');

    return `<ul class="nova-swal-advertencias">${items}</ul>`;
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

// ── Asignar árbitro con manejo de advertencias ────────
async function asignarArbitro(partidoId, arbitro, rolId) {
    const advertencias = construirAdvertencias(arbitro);

    if (advertencias.length > 0) {
        const result = await novaAlert.confirm({
            titulo:         `Asignar a ${arbitro.nombreUsuario}`,
            texto:          '',
            icono:          'warning',
            confirmarTexto: 'Sí, asignar igual',
            confirmColor:   '#f59e0b',
            iconColor:      '#f59e0b',
            html:           renderAdvertenciasHtml(advertencias),
        });
        if (!result.isConfirmed) return;
    }

    try {
        const r = await fetch(window.asignarUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ idArbitro: arbitro.idArbitro, idRol: rolId }),
        });

        const data = await r.json();

        if (data.success) {
            novaAlert.success(`${arbitro.nombreUsuario} asignado correctamente.`);
            setTimeout(() => location.reload(), 1200);
        } else {
            novaAlert.error(data.message ?? 'Error al asignar árbitro.');
        }
    } catch (e) {
        novaAlert.error('Error de red al asignar árbitro.');
    }
}

// ── Quitar designación ────────────────────
async function quitarDesignacion(desigId, rolId) {
    const result = await novaAlert.confirm({
        titulo:         'Quitar designación',
        texto:          'Se notificará al árbitro. ¿Confirmar?',
        icono:          'warning',
        confirmarTexto: 'Sí, quitar',
        confirmColor:   '#ef4444',
        iconColor:      '#ef4444',
    });

    if (!result.isConfirmed) return;

    try {
        const r = await fetch(`${window.quitarBase}/${desigId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Accept': 'application/json' },
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success('Designación quitada.');
            setTimeout(() => location.reload(), 1000);
        } else {
            novaAlert.error(data.message ?? 'Error al quitar la designación.');
        }
    } catch (e) {
        novaAlert.error('Error de red.');
    }
}
window.quitarDesignacion = quitarDesignacion;

// ── Cambiar estado del partido ────────────
async function cambiarEstado(partidoId, version) {
    const estadoNuevo = document.getElementById('estado-nuevo')?.value;

    if (!estadoNuevo) { novaAlert.error('Selecciona un estado.'); return; }

    const result = await novaAlert.confirm({
        titulo:         'Cambiar estado del partido',
        texto:          `¿Cambiar a "${estadoNuevo}"?`,
        icono:          'question',
        confirmarTexto: 'Sí, cambiar',
        confirmColor:   '#4f8ef7',
        iconColor:      '#4f8ef7',
    });

    if (!result.isConfirmed) return;

    try {
        const r = await fetch(window.estadoUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ estadoNuevo, version }),
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success('Estado actualizado.');
            setTimeout(() => location.reload(), 1000);
        } else {
            novaAlert.error(data.message ?? 'Error al cambiar estado.');
        }
    } catch (e) {
        novaAlert.error('Error de red.');
    }
}
window.cambiarEstado = cambiarEstado;

// ── Confirmar designación (árbitro) ───────
async function confirmarDesignacion(desigId) {
    const result = await novaAlert.confirm({
        titulo:         '¿Confirmar asistencia?',
        texto:          'Se notificará al designador.',
        icono:          'success',
        confirmarTexto: 'Sí, confirmo',
        confirmColor:   '#16a34a',
        iconColor:      '#22c55e',
    });
    if (!result.isConfirmed) return;

    try {
        const r = await fetch(`${window.confirmarBase}/${desigId}/confirmar`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': window.csrfToken, 'Accept': 'application/json' },
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success('Asistencia confirmada.');
            setTimeout(() => location.reload(), 1200);
        } else {
            novaAlert.error(data.message ?? 'Error al confirmar.');
        }
    } catch (e) {
        novaAlert.error('Error de red.');
    }
}
window.confirmarDesignacion = confirmarDesignacion;

// ── Modal de rechazo ─────────────────────
let desigIdParaRechazar = null;

function abrirModalRechazo(desigId) {
    desigIdParaRechazar = desigId;
    const modal = document.getElementById('modal-rechazo');
    const btn   = document.getElementById('btn-confirmar-rechazo');
    const ta    = document.getElementById('rechazo-motivo');

    if (modal) modal.style.display = 'flex';
    if (ta)    ta.value = '';
    if (btn)   btn.dataset.desigId = desigId;

    document.getElementById('rechazo-counter').textContent = '0';
}
window.abrirModalRechazo = abrirModalRechazo;

function cerrarModalRechazo() {
    const modal = document.getElementById('modal-rechazo');
    if (modal) modal.style.display = 'none';
    desigIdParaRechazar = null;
}
window.cerrarModalRechazo = cerrarModalRechazo;

async function rechazarDesignacion(desigId, motivo) {
    cerrarModalRechazo();

    try {
        const r = await fetch(`${window.rechazarBase}/${desigId}/rechazar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ motivo }),
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success('Rechazo registrado. El designador será notificado.');
            setTimeout(() => location.reload(), 1500);
        } else {
            novaAlert.error(data.message ?? 'Error al registrar rechazo.');
        }
    } catch (e) {
        novaAlert.error('Error de red.');
    }
}

// ── Historial de acciones: "Ver más" / "Ver menos" ────
function toggleHistorialCompleto() {
    const timeline = document.getElementById('historial-timeline');
    const btn       = document.getElementById('btn-historial-toggle');
    if (!timeline || !btn) return;

    const expandido = timeline.classList.toggle('mostrar-todo');
    const restantes  = btn.dataset.restantes ?? '0';

    btn.innerHTML = expandido
        ? '<i class="fa-solid fa-chevron-up"></i> Ver menos'
        : `<i class="fa-solid fa-chevron-down"></i> Ver ${restantes} más...`;
}
window.toggleHistorialCompleto = toggleHistorialCompleto;

// ── Carga dinámica torneos → divisiones y sedes ───────
// Destruye la instancia Choices existente, repuebla el <select> nativo y la recrea.
async function cargarDivisionesYSedes(torneoId) {
    const selDiv  = document.getElementById('sel-division');
    const selSede = document.getElementById('sel-sede');

    if (!selDiv || !selSede) return;

    // Destruir instancias Choices previas para poder manipular el <select> nativo
    if (selDiv._choicesInstance) {
        selDiv._choicesInstance.destroy();
        selDiv._choicesInstance = null;
        delete selDiv.dataset.choicesInit;
    }
    if (selSede._choicesInstance) {
        selSede._choicesInstance.destroy();
        selSede._choicesInstance = null;
        delete selSede.dataset.choicesInit;
    }

    // Mostrar estado de carga
    selDiv.innerHTML  = '<option value="">Cargando divisiones...</option>';
    selSede.innerHTML = '<option value="">Cargando sedes...</option>';
    selDiv.disabled  = true;
    selSede.disabled = true;

    try {
        const [rDiv, rSede] = await Promise.all([
            fetch(`${window.urlDivisiones}/${torneoId}/divisiones`),
            fetch(`${window.urlSedes}/${torneoId}/sedes`),
        ]);

        if (!rDiv.ok || !rSede.ok) throw new Error('Error del servidor');

        const divisiones = await rDiv.json();
        const sedes      = await rSede.json();

        selDiv.innerHTML = divisiones.length === 0
            ? '<option value="">Este torneo no tiene divisiones</option>'
            : '<option value="">Selecciona división</option>' +
              divisiones.map(d => `<option value="${d.idDivision}">${d.nombreDivision}</option>`).join('');

        selSede.innerHTML = sedes.length === 0
            ? '<option value="">Este torneo no tiene sedes</option>'
            : '<option value="">Selecciona sede</option>' +
              sedes.map(s => `<option value="${s.idSede}">${s.nombreSede}${s.municipio ? ' — ' + s.municipio : ''}</option>`).join('');

    } catch (e) {
        console.error('cargarDivisionesYSedes error', e);
        selDiv.innerHTML  = '<option value="">Error al cargar divisiones</option>';
        selSede.innerHTML = '<option value="">Error al cargar sedes</option>';
    } finally {
        selDiv.disabled  = false;
        selSede.disabled = false;

        // Recrear instancias Choices con el nuevo contenido
        selDiv._choicesInstance = new window.Choices(selDiv, {
            searchEnabled: false,
            shouldSort: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: selDiv.options[0]?.text || 'Selecciona división',
            allowHTML: false,
            position: 'auto',
        });
        selDiv.dataset.choicesInit = '1';

        selSede._choicesInstance = new window.Choices(selSede, {
            searchEnabled: false,
            shouldSort: false,
            itemSelectText: '',
            placeholder: true,
            placeholderValue: selSede.options[0]?.text || 'Selecciona sede',
            allowHTML: false,
            position: 'auto',
        });
        selSede.dataset.choicesInit = '1';
    }
}

// ── Preview de roles según formato ───────
function mostrarPreviewFormato() {
    const sel     = document.getElementById('sel-formato');
    const preview = document.getElementById('formato-preview');
    const lista   = document.getElementById('formato-roles');

    if (!sel || !preview || !lista) return;

    const opt = sel.options[sel.selectedIndex];
    if (!opt?.value) { preview.style.display = 'none'; return; }

    const nArbitros = parseInt(opt.dataset.arbitros ?? '0');
    const roles     = ['Central', 'Asistente 1', 'Asistente 2', 'Cuarto árbitro', 'VAR'];

    lista.innerHTML = roles.slice(0, nArbitros)
        .map(r => `<span class="formato-rol-item"><i class="fa-solid fa-user-tie"></i> ${r}</span>`)
        .join('');

    preview.style.display = 'block';
}

// ── Actualizar cards en tiempo real (Reverb) 
function actualizarCardPartido(partido) {
    const card = document.querySelector(`.partido-card[data-partido="${partido.idPartido}"]`);
    if (!card) return;

    // Actualizar clase de estado
    const estadoClasses = ['estado-programado','estado-confirmado','estado-critico','estado-aplazado','estado-finalizado','estado-cancelado'];
    card.classList.remove(...estadoClasses);
    card.classList.add(`estado-${partido.estadoPartido}`);

    if (partido.estadoPartido === 'critico') card.classList.add('es-critico');
    else card.classList.remove('es-critico');
}

function marcarCardCritico(idPartido) {
    const card = document.querySelector(`.partido-card[data-partido="${idPartido}"]`);
    if (!card) return;
    card.classList.add('es-critico', 'estado-critico');
}

function actualizarRolCard(designacion) {
    const card = document.getElementById(`rol-card-${designacion.idRol}`);
    if (!card) return;
    // Recarga simplificada — el server-side rendering hace el heavy lifting
    if (designacion.idPartido === window.partidoId) {
        setTimeout(() => location.reload(), 500);
    }
}

/** Resumen del partido + roles asignados, mostrado en el modal de confirmación de publicar. */
function renderResumenPublicarHtml(r) {
    if (!r) return '';

    const filasRoles = (r.roles ?? [])
        .map(x => `<li class="nova-swal-resumen__rol${x.arbitro ? '' : ' nova-swal-resumen__rol--vacio'}">
            <span>${x.rol}</span>
            <strong>${x.arbitro ?? 'Sin asignar'}</strong>
        </li>`)
        .join('');

    return `
        <div class="nova-swal-resumen">
            <div class="nova-swal-resumen__match">${r.equipoLocal} <span>vs</span> ${r.equipoVisitante}</div>
            <div class="nova-swal-resumen__meta">
                <span><i class="fa-regular fa-calendar"></i> ${r.fecha}</span>
                <span><i class="fa-regular fa-clock"></i> ${r.hora}</span>
                <span><i class="fa-solid fa-location-dot"></i> ${r.sede}</span>
            </div>
            <ul class="nova-swal-resumen__roles">${filasRoles}</ul>
        </div>
        <p style="margin:.75rem 0 0;font-size:.82rem;color:var(--nv-text-2)">
            Los árbitros designados serán notificados y podrán confirmar o rechazar.
        </p>
    `;
}

// ── Publicar partido (borrador → programado) ─────────
async function publicarPartido(partidoId) {
    const result = await novaAlert.confirm({
        titulo:         '¿Publicar partido?',
        texto:          '',
        icono:          'question',
        confirmarTexto: 'Sí, publicar',
        confirmColor:   '#16a34a',
        iconColor:      '#22c55e',
        html:           renderResumenPublicarHtml(window.partidoResumen),
    });
    if (!result.isConfirmed) return;

    try {
        const r = await fetch(window.publicarUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success(data.mensaje ?? 'Partido publicado.');
            setTimeout(() => location.reload(), 1200);
        } else {
            novaAlert.error(data.message ?? 'Error al publicar el partido.');
        }
    } catch (e) {
        novaAlert.error('Error de red al publicar el partido.');
    }
}
window.publicarPartido = publicarPartido;

// ── Editar partido (solo borrador) ────────
function abrirModalEditarPartido() {
    const modal = document.getElementById('modal-editar-partido');
    if (!modal) return;

    modal.style.display = 'flex';
    const primero = modal.querySelector('input:not([type="hidden"]), select, textarea');
    if (primero) setTimeout(() => primero.focus(), 50);
}
window.abrirModalEditarPartido = abrirModalEditarPartido;

function cerrarModalEditarPartido() {
    const modal = document.getElementById('modal-editar-partido');
    if (modal) modal.style.display = 'none';
}
window.cerrarModalEditarPartido = cerrarModalEditarPartido;

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modal-editar-partido');
    if (!modal) return;

    // Clic en el overlay (fuera del cuadro) cierra — antes solo se podía
    // cerrar con el botón X o "Cancelar", lo que se sentía "bloqueado".
    modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrarModalEditarPartido();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') cerrarModalEditarPartido();
    });

    const form = document.getElementById('form-editar-partido');
    const btn  = document.getElementById('btn-guardar-editar-partido');
    form?.addEventListener('submit', function () {
        if (!btn) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';
    });
});

// ── Eliminar partido (solo borrador) ──────────────────
async function eliminarPartido(partidoId) {
    const result = await novaAlert.confirm({
        titulo:         '¿Eliminar partido?',
        texto:          'Se borrará el partido y todas sus designaciones. Esta acción no se puede deshacer.',
        icono:          'warning',
        confirmarTexto: 'Sí, eliminar',
        confirmColor:   '#dc2626',
        iconColor:      '#ef4444',
    });
    if (!result.isConfirmed) return;

    try {
        const r = await fetch(`${window.eliminarPartidoBase}/${partidoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success(data.mensaje ?? 'Partido eliminado.');
            setTimeout(() => { location.href = window.designacionesIndexUrl; }, 1000);
        } else {
            novaAlert.error(data.message ?? 'Error al eliminar el partido.');
        }
    } catch (e) {
        novaAlert.error('Error de red al eliminar el partido.');
    }
}
window.eliminarPartido = eliminarPartido;

// ── Finalizar partido (solo árbitro Central, partido confirmado) ────────────
async function finalizarPartido(partidoId) {
    const result = await novaAlert.confirm({
        titulo:         '¿Finalizar partido?',
        texto:          'Confirmas que el partido terminó. Esta acción notificará al colegio.',
        icono:          'warning',
        confirmarTexto: 'Sí, finalizar',
        confirmColor:   '#4f8ef7',
        iconColor:      '#4f8ef7',
    });
    if (!result.isConfirmed) return;

    try {
        const r = await fetch(`${window.finalizarBase}/${partidoId}/finalizar`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success('Partido finalizado. ¡Buen trabajo!');
            setTimeout(() => location.reload(), 1200);
        } else {
            novaAlert.error(data.message ?? 'Error al finalizar el partido.');
        }
    } catch (e) {
        novaAlert.error('Error de red al finalizar el partido.');
    }
}
window.finalizarPartido = finalizarPartido;

// ── Revertir finalizado → programado (solo ejecutivo) 
async function revertirFinalizado(partidoId, version) {
    const result = await novaAlert.confirm({
        titulo:         '¿Revertir a programado?',
        texto:          'El partido volverá al estado programado. Esta acción queda registrada en el historial.',
        icono:          'warning',
        confirmarTexto: 'Sí, revertir',
        confirmColor:   '#f59e0b',
        iconColor:      '#f59e0b',
    });
    if (!result.isConfirmed) return;

    try {
        const r = await fetch(window.estadoUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ estadoNuevo: 'programado', detalle: 'Reversión de partido finalizado', version }),
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success('Partido revertido a programado.');
            setTimeout(() => location.reload(), 1000);
        } else {
            novaAlert.error(data.message ?? 'Error al revertir el partido.');
        }
    } catch (e) {
        novaAlert.error('Error de red.');
    }
}
window.revertirFinalizado = revertirFinalizado;

// ── Asignar veedor al partido ─────────────
async function asignarVeedor(partidoId) {
    const sel = document.getElementById('veedor-select');
    const idVeedor = sel ? (sel._choicesInstance?.getValue(true) ?? sel.value) : null;

    try {
        const r = await fetch(window.veedorUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ idVeedor: idVeedor || null }),
        });
        const data = await r.json();

        if (data.success) {
            novaAlert.success(idVeedor ? 'Veedor asignado correctamente.' : 'Veedor removido.');
            setTimeout(() => location.reload(), 1000);
        } else {
            novaAlert.error(data.message ?? 'Error al asignar veedor.');
        }
    } catch (e) {
        novaAlert.error('Error de red.');
    }
}
window.asignarVeedor = asignarVeedor;

// ── Helpers ──
function configurarContador(textareaId, counterId) {
    const ta    = document.getElementById(textareaId);
    const count = document.getElementById(counterId);
    if (!ta || !count) return;

    ta.addEventListener('input', function () {
        count.textContent = ta.value.length;
    });
}
