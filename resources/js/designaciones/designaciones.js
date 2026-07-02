/**
 * NovaReef — Módulo Designaciones — M04 Bloque 3
 * Echo + Reverb + lógica de asignación, confirmación, rechazo y estado.
 */

// ── Laravel Echo + Reverb ─────────────────────────────────────────────────────
import Echo   from 'laravel-echo';
import Pusher from 'pusher-js';

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
    });
}

// ── Suscripción en tiempo real ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    window.initNovaSelects?.();

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

    // ── Búsqueda de árbitros (show.blade) ────────────────────────────────────
    document.querySelectorAll('.arbitro-search').forEach(function (wrap) {
        const input      = wrap.querySelector('.arbitro-search__input');
        const results    = wrap.querySelector('.arbitro-search__results');
        const rolId      = wrap.dataset.rol;
        const partidoId  = wrap.dataset.partido;

        let debounceTimer;

        input?.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = input.value.trim();

            if (q.length < 1) {
                results.style.display = 'none';
                results.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => buscarArbitros(partidoId, q, rolId, results), 300);
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

    // ── Preview de roles según formato ───────────────────────────────────────
    const selFormato = document.getElementById('sel-formato');
    if (selFormato) {
        selFormato.addEventListener('change', mostrarPreviewFormato);
    }

    // Si hay un torneo preseleccionado (old() en validación fallida), cargarlo
    if (selTorneo && selTorneo.value) {
        cargarDivisionesYSedes(selTorneo.value);
    }

    // ── Contadores de textarea ────────────────────────────────────────────────
    configurarContador('obs-textarea', 'obs-counter');
    configurarContador('rechazo-motivo', 'rechazo-counter');

    // ── Botón confirmar rechazo ───────────────────────────────────────────────
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

// ── Búsqueda de árbitros con debounce ────────────────────────────────────────
async function buscarArbitros(partidoId, query, rolId, resultsEl) {
    if (!window.buscarUrl) return;

    try {
        const r    = await fetch(`${window.buscarUrl}?q=${encodeURIComponent(query)}`);
        const data = await r.json();

        resultsEl.innerHTML = '';

        const filtrados = data.filter(a =>
            a.nombreUsuario?.toLowerCase().includes(query.toLowerCase()) ||
            a.codigoCarnet?.toLowerCase().includes(query.toLowerCase())
        );

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
                    <div class="arbitro-result__nombre">${a.nombreUsuario ?? '—'}</div>
                    <div class="arbitro-result__meta">${a.codigoCarnet ?? ''} · ${a.nombreCategoria ?? ''}</div>
                    <div class="arbitro-result__badges">${buildBadges(a)}</div>
                </div>
            `;

            if (!a.yaAsignado) {
                item.addEventListener('click', () => asignarArbitro(partidoId, a, rolId));
            }

            resultsEl.appendChild(item);
        });

        resultsEl.style.display = 'block';
    } catch (e) {
        console.error('buscarArbitros error', e);
    }
}

function buildBadges(a) {
    const badges = [];
    if (a.disponibilidad === 'disponible')    badges.push(`<span class="arbitro-result__badge badge-disponible">${a.franjaLabel ?? 'Disponible'}</span>`);
    if (a.disponibilidad === 'extraordinaria') badges.push(`<span class="arbitro-result__badge badge-extraordinaria">Indisponible (extraordinaria)</span>`);
    if (a.disponibilidad === 'sin_reporte')    badges.push(`<span class="arbitro-result__badge badge-sin-reporte">Sin disponibilidad</span>`);
    if (a.advertenciaTiempo)                   badges.push(`<span class="arbitro-result__badge badge-warn-tiempo">⚠️ Partido a ${a.minutosAlPartidoCercano} min</span>`);
    if (a.esSuspendido)                        badges.push(`<span class="arbitro-result__badge badge-suspendido">Suspendido</span>`);
    return badges.join('');
}

// ── Asignar árbitro con manejo de advertencias ────────────────────────────────
async function asignarArbitro(partidoId, arbitro, rolId) {
    const advertencias = [];
    if (arbitro.esSuspendido)           advertencias.push('⚠️ Este árbitro está <strong>suspendido</strong>.');
    if (arbitro.disponibilidad === 'extraordinaria') advertencias.push('⚠️ El árbitro reportó <strong>indisponibilidad extraordinaria</strong> para esta fecha.');
    if (arbitro.disponibilidad === 'sin_reporte') advertencias.push('ℹ️ El árbitro <strong>no reportó disponibilidad</strong> para esta semana.');
    if (arbitro.advertenciaTiempo) advertencias.push(`⚠️ Tiene otro partido a <strong>${arbitro.minutosAlPartidoCercano} minutos</strong> de diferencia.`);

    if (advertencias.length > 0) {
        const result = await novaAlert.confirm({
            titulo:         `Asignar a ${arbitro.nombreUsuario}`,
            texto:          '',
            icono:          'warning',
            confirmarTexto: 'Sí, asignar igual',
            confirmColor:   '#f59e0b',
            iconColor:      '#f59e0b',
            html:           advertencias.join('<br>'),
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

// ── Quitar designación ────────────────────────────────────────────────────────
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

// ── Cambiar estado del partido ────────────────────────────────────────────────
async function cambiarEstado(partidoId, version) {
    const estadoNuevo = document.getElementById('estado-nuevo')?.value;
    const detalle     = document.getElementById('estado-detalle')?.value ?? '';

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
            body: JSON.stringify({ estadoNuevo, detalle, version }),
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

// ── Confirmar designación (árbitro) ───────────────────────────────────────────
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

// ── Modal de rechazo ─────────────────────────────────────────────────────────
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

// ── Carga dinámica torneos → divisiones y sedes ───────────────────────────────
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

// ── Preview de roles según formato ───────────────────────────────────────────
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

// ── Actualizar cards en tiempo real (Reverb) ──────────────────────────────────
function actualizarCardPartido(partido) {
    const card = document.querySelector(`.partido-card[data-partido="${partido.idPartido}"]`);
    if (!card) return;

    // Actualizar clase de estado
    const estadoClasses = ['estado-programado','estado-confirmado','estado-critico','estado-aplazado','estado-en_curso','estado-finalizado','estado-cancelado'];
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

// ── Publicar partido (borrador → programado) ─────────────────────────────────
async function publicarPartido(partidoId) {
    const result = await novaAlert.confirm({
        titulo:         '¿Publicar partido?',
        texto:          'Los árbitros designados serán notificados y podrán confirmar o rechazar.',
        icono:          'question',
        confirmarTexto: 'Sí, publicar',
        confirmColor:   '#16a34a',
        iconColor:      '#22c55e',
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

// ── Finalizar partido (solo árbitro Central, en_curso) ───────────────────────
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

// ── Revertir finalizado → programado (solo ejecutivo) ────────────────────────
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

// ── Asignar veedor al partido ─────────────────────────────────────────────────
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
