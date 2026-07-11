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

    // ── Marcar / corregir asistencia (instructor) ────
    document.querySelectorAll('[data-corregir-asistencia]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.disabled) return; // evita doble envío por doble clic
            const idAsistencia = btn.dataset.corregirAsistencia;
            const nuevoEstado  = btn.dataset.estadoNuevo;
            const fila = btn.closest('.asistencia-row');
            const yaMarcado = fila && fila.dataset.sinMarcar === '0';

            // Ya está explícitamente en ese estado — no reenviar nada.
            if (yaMarcado && fila.dataset.estado === nuevoEstado) return;

            corregirMarca(idAsistencia, nuevoEstado, fila);
        });
    });

    // ── Editar una marca ya puesta: desbloquea los botones de esa fila ────
    document.querySelectorAll('[data-editar-asistencia]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const acciones = btn.closest('.asistencia-acciones');
            if (acciones) acciones.dataset.locked = '0';
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

    // ── Evitar doble envío en formularios sin confirmación previa (ej.
    //    "Abrir sesión") — los que sí usan data-confirm-submit ya quedan
    //    protegidos por su propio flujo (form.dataset.confirmed). ────
    document.querySelectorAll('form:not([data-confirm-submit])').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
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

    // ── Buscador en vivo de la tabla de asistencia ────
    const buscador = document.getElementById('aca-buscador');
    if (buscador) {
        buscador.addEventListener('input', function () {
            const q = buscador.value.trim().toLowerCase();
            let visibles = 0;

            document.querySelectorAll('#aca-tabla-body .asistencia-row').forEach(function (fila) {
                const coincide = !q || (fila.dataset.nombre ?? '').includes(q);
                fila.classList.toggle('is-filtrado', !coincide);
                if (coincide) visibles++;
            });

            const contador = document.getElementById('aca-count-visible');
            if (contador) contador.textContent = visibles;
        });
    }
});

async function registrarPorScanner(codigoCarnet) {
    const input    = document.getElementById('scanner-input');
    const feedback = document.getElementById('scanner-feedback');

    if (input.disabled) return; // ya hay un escaneo en curso — evita duplicados
    input.value = '';
    input.disabled = true;

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

        const data = await leerRespuestaJson(r, 'registrarPorScanner');

        if (data.success) {
            if (feedback) {
                feedback.textContent = `✓ ${data.nombre} — ${data.hora}`;
                feedback.className = 'scanner-feedback scanner-feedback--ok';
            }
            mostrarUltimoEscaneado(data.nombre, data.hora);
            if (data.asistencia) actualizarFilaAsistencia(data.asistencia);
        } else {
            if (feedback) {
                feedback.textContent = data.message ?? data.mensaje ?? 'Error al registrar.';
                feedback.className = 'scanner-feedback scanner-feedback--error';
            }
            marcarErrorScanner(input);
        }
    } catch (e) {
        console.error('registrarPorScanner error:', e);
        if (feedback) {
            feedback.textContent = e.message || 'Error de red — intenta de nuevo.';
            feedback.className = 'scanner-feedback scanner-feedback--error';
        }
        marcarErrorScanner(input);
    } finally {
        input.disabled = false;
        input.focus();
    }
}

function marcarErrorScanner(input) {
    input.classList.remove('is-error');
    // Forzar reflow para poder re-disparar la animación en escaneos seguidos.
    void input.offsetWidth;
    input.classList.add('is-error');
    setTimeout(() => input.classList.remove('is-error'), 400);
}

function mostrarUltimoEscaneado(nombre, hora) {
    const wrap   = document.getElementById('scanner-last');
    const avatar = document.getElementById('scanner-last-avatar');
    const nombreEl = document.getElementById('scanner-last-nombre');
    const horaEl    = document.getElementById('scanner-last-hora');
    if (!wrap) return;

    if (avatar) avatar.textContent = (nombre ?? '?').charAt(0).toUpperCase();
    if (nombreEl) nombreEl.textContent = nombre ?? '—';
    if (horaEl) horaEl.textContent = hora ?? '';

    wrap.classList.remove('is-visible');
    void wrap.offsetWidth;
    wrap.classList.add('is-visible');
}

async function corregirMarca(idAsistencia, nuevoEstado, fila = null) {
    const botones = document.querySelectorAll(`[data-corregir-asistencia="${idAsistencia}"]`);
    botones.forEach((b) => { b.disabled = true; });

    // Estado previo completo (para poder revertir si el servidor falla, o
    // para ofrecer "Deshacer" si tuvo éxito).
    const anterior = fila ? {
        estado:     fila.dataset.estado,
        sinMarcar:  fila.dataset.sinMarcar,
        horaTexto:  fila.querySelector('.asistencia-hora')?.textContent ?? '—',
    } : null;
    const habiaMarcaPrevia = anterior !== null && anterior.sinMarcar === '0';

    // Actualización optimista: se ve al instante, sin esperar la red — la
    // respuesta del servidor solo reconcilia (o revierte si algo falló).
    actualizarFilaAsistencia({
        idAsistencia:     Number(idAsistencia),
        estadoAsistencia: nuevoEstado,
        horaMarca:        nuevoEstado === 'presente' ? new Date().toISOString() : null,
        registradoPor:    'instructor',
    });

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

        const data = await leerRespuestaJson(r, 'corregirMarca');

        if (data.success) {
            // Reconcilia con la hora real del servidor (la optimista usa el
            // reloj del navegador, puede diferir por segundos).
            if (data.asistencia) actualizarFilaAsistencia(data.asistencia);

            // No hay diálogo de confirmación por cada clic (frenaría marcar
            // decenas de árbitros seguidos) — en su lugar, un "Deshacer"
            // rápido, mismo patrón que Gmail/Slack para acciones de un click.
            if (habiaMarcaPrevia) {
                const etiqueta = nuevoEstado === 'presente' ? 'Presente' : 'Ausente';
                mostrarDeshacer(`Marcado como ${etiqueta}`, function () {
                    corregirMarca(idAsistencia, anterior.estado, fila);
                });
            }
        } else {
            revertirFila(fila, anterior);
            novaAlert.error(data.message ?? data.mensaje ?? 'Error al corregir la marca.');
        }
    } catch (e) {
        console.error('corregirMarca error:', e);
        revertirFila(fila, anterior);
        novaAlert.error(e.message || 'Error al corregir la marca. Intenta de nuevo.');
    } finally {
        botones.forEach((b) => { b.disabled = false; });
    }
}

/** Deshace la actualización optimista cuando el servidor termina fallando
 *  (falla de red, error de validación, sesión expirada, etc.). */
function revertirFila(fila, anterior) {
    if (!fila || !anterior) return;

    fila.dataset.estado = anterior.estado;
    fila.dataset.sinMarcar = anterior.sinMarcar;

    const estadoCell = fila.querySelector('.asistencia-estado');
    if (estadoCell) {
        estadoCell.innerHTML = anterior.sinMarcar === '1'
            ? '<span class="badge badge-pendiente">Sin marcar</span>'
            : badgeEstado(anterior.estado, 'instructor');
    }

    const horaCell = fila.querySelector('.asistencia-hora');
    if (horaCell) horaCell.textContent = anterior.horaTexto;

    sincronizarBotonesCorregir(fila, anterior.sinMarcar === '1' ? null : anterior.estado);

    const acciones = fila.querySelector('.asistencia-acciones');
    if (acciones) acciones.dataset.locked = anterior.sinMarcar === '1' ? '0' : '1';

    recalcularEstadisticas();
}

/**
 * Lee la respuesta de un fetch() como JSON, distinguiendo el caso más común
 * de "parece error de red pero no lo es": la sesión expiró y Laravel
 * redirigió al login, devolviendo HTML (200 OK) en vez de JSON — r.json()
 * revienta con un SyntaxError que antes se mostraba como "error de red"
 * genérico, sin decir la causa real.
 */
async function leerRespuestaJson(r, contexto) {
    const contentType = r.headers.get('content-type') ?? '';

    if (r.status === 401 || r.status === 419) {
        throw new Error('Tu sesión expiró. Recarga la página e inicia sesión de nuevo.');
    }

    if (!contentType.includes('application/json')) {
        console.error(`${contexto}: respuesta no-JSON`, { status: r.status, contentType, url: r.url });
        throw new Error('Tu sesión pudo haber expirado, o el servidor no respondió correctamente. Recarga la página.');
    }

    try {
        return await r.json();
    } catch (parseError) {
        console.error(`${contexto}: JSON inválido`, { status: r.status, parseError });
        throw new Error('El servidor devolvió una respuesta inválida. Intenta de nuevo.');
    }
}

/** Toast con acción de deshacer — no bloquea, se autodescarta a los 5s. */
function mostrarDeshacer(mensaje, onDeshacer) {
    if (!window.Swal) return;

    window.Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: 'success',
        title: mensaje,
        showConfirmButton: true,
        confirmButtonText: 'Deshacer',
        confirmButtonColor: '#4f8ef7',
        timer: 5000,
        timerProgressBar: true,
        customClass: { popup: 'nova-swal' },
    }).then(function (result) {
        if (result.isConfirmed && onDeshacer) onDeshacer();
    });
}

/** Actualiza la fila de la tabla de asistencia, la lista de escaneados
 *  recientes (si aplica) y recalcula las estadísticas de la sesión. */
function actualizarFilaAsistencia(asistencia) {
    const fila = document.querySelector(`.asistencia-row[data-asistencia="${asistencia.idAsistencia}"]`);
    // hour12:false explícito — sin esto, toLocaleTimeString('es-CO', ...) usa
    // formato 12h con "a. m./p. m." según el navegador, distinto del H:i de
    // 24h que renderiza el servidor, y la tabla mostraba horas inconsistentes.
    const horaTexto = asistencia.horaMarca
        ? new Date(asistencia.horaMarca).toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', hour12: false })
        : '—';

    if (fila) {
        const eraPresente = fila.dataset.estado === 'presente';
        fila.dataset.estado = asistencia.estadoAsistencia;
        // Cualquier respuesta del servidor implica una acción explícita —
        // nunca vuelve a quedar "sin marcar" una vez que se confirmó algo.
        fila.dataset.sinMarcar = '0';
        fila.classList.add('recien-actualizada');

        const estadoCell = fila.querySelector('.asistencia-estado');
        if (estadoCell) estadoCell.innerHTML = badgeEstado(asistencia.estadoAsistencia, asistencia.registradoPor);

        const horaCell = fila.querySelector('.asistencia-hora');
        if (horaCell) horaCell.textContent = horaTexto;

        sincronizarBotonesCorregir(fila, asistencia.estadoAsistencia);

        // Se acaba de confirmar una marca — vuelve a quedar bloqueada; para
        // cambiarla de nuevo hay que pedir "Editar" otra vez a propósito.
        const acciones = fila.querySelector('.asistencia-acciones');
        if (acciones) acciones.dataset.locked = '1';

        setTimeout(() => fila.classList.remove('recien-actualizada'), 1500);

        // Recién marcado presente (no lo estaba antes) — sumarlo al feed de
        // "registrados recientemente" del modo scanner, si existe en la página.
        if (!eraPresente && asistencia.estadoAsistencia === 'presente') {
            agregarAListaEscaneados(asistencia.arbitro?.nombre ?? fila.querySelector('.td-primary')?.textContent, horaTexto);
        }
    } else if (asistencia.arbitro) {
        agregarAListaEscaneados(asistencia.arbitro.nombre, horaTexto);
    }

    recalcularEstadisticas();
}

/** Resalta en la columna "Acciones" cuál de los dos botones representa el
 *  estado actual — para que se vea de un vistazo sin depender solo del
 *  badge de la columna Estado. */
function sincronizarBotonesCorregir(fila, estadoActual) {
    fila.querySelectorAll('[data-estado-nuevo]').forEach(function (btn) {
        const esActivo = btn.dataset.estadoNuevo === estadoActual;
        btn.classList.toggle('is-activo', esActivo);
        btn.classList.toggle('btn-secondary', !esActivo);
    });
}

function agregarAListaEscaneados(nombre, horaTexto) {
    const lista = document.getElementById('scanner-lista');
    if (!lista || !nombre) return;

    const inicial = nombre.charAt(0).toUpperCase();
    const item = document.createElement('div');
    item.className = 'scanner-item';
    item.innerHTML = `
        <div class="aca-arbitro-cell">
            <div class="aca-avatar aca-avatar--sm">${inicial}</div>
            <span>${nombre}</span>
        </div>
        <span class="asistencia-hora">${horaTexto}</span>
    `;
    lista.prepend(item);
}

/** Recalcula presentes/ausentes/% a partir de las filas ya en el DOM —
 *  evita depender de un round-trip al servidor para reflejar el cambio. */
function recalcularEstadisticas() {
    const filas = document.querySelectorAll('#aca-tabla-body .asistencia-row');
    if (filas.length === 0) return;

    let presentes   = 0;
    let ausentes    = 0;
    let justificados = 0;

    filas.forEach(function (fila) {
        if (fila.dataset.sinMarcar === '1') return; // aún no decidido, no cuenta en ningún bucket

        const estado = fila.dataset.estado;
        if (estado === 'presente') presentes++;
        if (estado === 'ausente' || estado === 'justificacion_rechazada') ausentes++;
        if (estado === 'justificado') justificados++;
    });

    const total = filas.length;
    const pct   = total > 0 ? Math.round((presentes / total) * 100) : 0;

    const elPresentes   = document.getElementById('stat-presentes');
    const elAusentes    = document.getElementById('stat-ausentes');
    const elJustificados = document.getElementById('stat-justificados');
    const elPct         = document.getElementById('stat-pct');
    const elPctBar      = document.getElementById('stat-pct-bar');

    if (elPresentes)    elPresentes.textContent = presentes;
    if (elAusentes)     elAusentes.textContent = ausentes;
    if (elJustificados) elJustificados.textContent = justificados;
    if (elPct)          elPct.textContent = `${pct}%`;
    if (elPctBar)        elPctBar.style.width = `${pct}%`;
}

function badgeEstado(estado, registradoPor = null) {
    if (registradoPor === 'sistema') {
        return '<span class="badge badge-pendiente">Sin marcar</span>';
    }

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
