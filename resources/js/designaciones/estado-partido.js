/**
 * Ciclo de vida del partido: cambiar estado, publicar, editar (solo
 * borrador), eliminar, finalizar (árbitro Central) y revertir finalizado.
 */

export async function cambiarEstado(partidoId, version) {
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

export async function publicarPartido(partidoId) {
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

export function abrirModalEditarPartido() {
    const modal = document.getElementById('modal-editar-partido');
    if (!modal) return;

    modal.style.display = 'flex';
    const primero = modal.querySelector('input:not([type="hidden"]), select, textarea');
    if (primero) setTimeout(() => primero.focus(), 50);
}
window.abrirModalEditarPartido = abrirModalEditarPartido;

export function cerrarModalEditarPartido() {
    const modal = document.getElementById('modal-editar-partido');
    if (modal) modal.style.display = 'none';
}
window.cerrarModalEditarPartido = cerrarModalEditarPartido;

/** Wiring del modal de editar partido — overlay/Escape para cerrar, spinner al guardar. */
export function inicializarModalEditarPartido() {
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
}

export async function eliminarPartido(partidoId) {
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

export async function finalizarPartido(partidoId) {
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

export async function revertirFinalizado(partidoId, version) {
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
