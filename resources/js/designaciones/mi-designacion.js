/**
 * El árbitro confirma/rechaza su propia designación.
 */

export async function confirmarDesignacion(desigId) {
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

let desigIdParaRechazar = null;

export function abrirModalRechazo(desigId) {
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

export function cerrarModalRechazo() {
    const modal = document.getElementById('modal-rechazo');
    if (modal) modal.style.display = 'none';
    desigIdParaRechazar = null;
}
window.cerrarModalRechazo = cerrarModalRechazo;

export async function rechazarDesignacion(desigId, motivo) {
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
