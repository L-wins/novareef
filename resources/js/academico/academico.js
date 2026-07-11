/**
 * NovaReef — Módulo Académico — M08
 * Echo + Reverb: asistencia en tiempo real (modo manual y scanner).
 */

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
        authEndpoint: window.broadcastAuthEndpoint ?? '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', function () {
    window.initNovaSelects?.();

    // ── Paneles plegables: [data-toggle-panel="id"] muestra/oculta el panel
    //    y (opcional) enfoca el campo indicado en data-focus al abrir ──
    document.querySelectorAll('[data-toggle-panel]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var panel = document.getElementById(btn.dataset.togglePanel);
            if (!panel) return;

            var abierto = !panel.classList.toggle('is-oculto');
            if (abierto) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                if (btn.dataset.focus) {
                    document.getElementById(btn.dataset.focus)?.focus();
                }
            }
        });
    });

    if (window.colegioId) {
        window.Echo.private(`colegio.${window.colegioId}.academico`)
            .listen('.asistencia.actualizada', (e) => actualizarFilaAsistencia(e))
            .listen('.sesion.cerrada', () => {
                novaAlert.success('La sesión fue cerrada. La lista quedó confirmada.');
                setTimeout(() => location.reload(), 1500);
            });
    }

    // ── Modo scanner ──────────────────────
    const scannerInput = document.getElementById('scanner-input');
    if (scannerInput) {
        scannerInput.focus();

        scannerInput.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            const codigo = scannerInput.value.trim();
            if (!codigo) return;

            registrarPorScanner(codigo);
        });

        // Mantener el foco siempre en el input, aunque el usuario haga clic
        // en otro lado de la página (la pistola lectora escribe donde esté
        // el foco, no donde esté el cursor del mouse).
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.scanner-lista') && !e.target.closest('a, button')) {
                scannerInput.focus();
            }
        });
    }

    // ── Corrección manual (instructor) ────
    document.querySelectorAll('[data-corregir-asistencia]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const idAsistencia = btn.dataset.corregirAsistencia;
            const nuevoEstado  = btn.dataset.estadoNuevo;
            corregirMarca(idAsistencia, nuevoEstado);
        });
    });

    // ── Formulario de sesión: campos condicionales ────
    const selModalidad = document.getElementById('modalidad');
    const wrapUrl      = document.getElementById('wrap-url-virtual');
    function sincronizarModalidad() {
        if (!selModalidad || !wrapUrl) return;
        wrapUrl.style.display = selModalidad.value === 'virtual' ? '' : 'none';
    }
    if (selModalidad) {
        selModalidad.addEventListener('change', sincronizarModalidad);
        sincronizarModalidad();
    }

    const selDirigida  = document.getElementById('dirigidaA');
    const wrapCategoria = document.getElementById('wrap-categoria');
    function sincronizarDirigida() {
        if (!selDirigida || !wrapCategoria) return;
        wrapCategoria.style.display = selDirigida.value === 'categoria' ? '' : 'none';
    }
    if (selDirigida) {
        selDirigida.addEventListener('change', sincronizarDirigida);
        sincronizarDirigida();
    }

    // ── Justificaciones pendientes: mostrar formulario de rechazo ────
    document.querySelectorAll('[data-abrir-rechazo]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = document.getElementById(`form-rechazo-${btn.dataset.abrirRechazo}`);
            if (form) form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });
    });

    // ── Justificación: contador de PDF ────
    const inputPdf  = document.getElementById('documentoPdf');
    const nombrePdf = document.getElementById('documento-pdf-nombre');
    if (inputPdf && nombrePdf) {
        inputPdf.addEventListener('change', function () {
            nombrePdf.textContent = inputPdf.files[0]?.name ?? 'Ningún archivo seleccionado';
        });
    }
});

async function registrarPorScanner(codigoCarnet) {
    const input    = document.getElementById('scanner-input');
    const feedback = document.getElementById('scanner-feedback');

    input.value = '';

    try {
        const r = await fetch(window.scannerUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ idSesion: window.sesionId, codigoCarnet }),
        });
        const data = await r.json();

        if (data.success) {
            if (feedback) {
                feedback.textContent = `✓ ${data.nombre} — ${data.hora}`;
                feedback.className = 'scanner-feedback scanner-feedback--ok';
            }
        } else {
            if (feedback) {
                feedback.textContent = data.message ?? 'Error al registrar.';
                feedback.className = 'scanner-feedback scanner-feedback--error';
            }
        }
    } catch (e) {
        if (feedback) {
            feedback.textContent = 'Error de red.';
            feedback.className = 'scanner-feedback scanner-feedback--error';
        }
    } finally {
        input.focus();
    }
}

async function corregirMarca(idAsistencia, nuevoEstado) {
    try {
        const r = await fetch(`${window.corregirBase}/${idAsistencia}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ estadoAsistencia: nuevoEstado }),
        });

        if (!r.ok) throw new Error('Error del servidor');
    } catch (e) {
        novaAlert.error('Error al corregir la marca.');
    }
}

/** Actualiza la fila de la tabla de asistencia (modo manual) o agrega un
 *  ítem a la lista de escaneados (modo scanner), según cuál esté en el DOM. */
function actualizarFilaAsistencia(asistencia) {
    const fila = document.querySelector(`.asistencia-row[data-asistencia="${asistencia.idAsistencia}"]`);
    if (fila) {
        fila.dataset.estado = asistencia.estadoAsistencia;
        fila.classList.add('recien-actualizada');

        const estadoCell = fila.querySelector('.asistencia-estado');
        if (estadoCell) estadoCell.innerHTML = badgeEstado(asistencia.estadoAsistencia);

        const horaCell = fila.querySelector('.asistencia-hora');
        if (horaCell) horaCell.textContent = asistencia.horaMarca ? new Date(asistencia.horaMarca).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : '—';

        setTimeout(() => fila.classList.remove('recien-actualizada'), 1500);
        return;
    }

    const lista = document.getElementById('scanner-lista');
    if (lista && asistencia.arbitro) {
        const item = document.createElement('div');
        item.className = 'scanner-item';
        item.innerHTML = `
            <span>${asistencia.arbitro.nombre ?? '—'}</span>
            <span class="asistencia-hora">${asistencia.horaMarca ? new Date(asistencia.horaMarca).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : ''}</span>
        `;
        lista.prepend(item);
    }
}

function badgeEstado(estado) {
    const etiquetas = {
        presente:                  ['Presente', 'green'],
        ausente:                   ['Ausente', 'red'],
        justificacion_pendiente:   ['Justificación pendiente', 'amber'],
        justificado:               ['Justificado', 'blue'],
        justificacion_rechazada:   ['Justificación rechazada', 'gray'],
    };
    const [label, color] = etiquetas[estado] ?? ['—', 'gray'];
    return `<span class="badge badge-${color}">${label}</span>`;
}
