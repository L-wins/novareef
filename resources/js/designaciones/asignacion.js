/**
 * Asignar/reasignar/quitar árbitro y asignar veedor — las acciones que
 * escriben sobre un partido ya creado.
 */
import { construirAdvertencias, renderAdvertenciasHtml } from './advertencias.js';

export function toggleReasignarBusqueda(desigId) {
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

export async function reasignarArbitro(desigId, arbitro) {
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

export async function asignarArbitro(partidoId, arbitro, rolId) {
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

export async function quitarDesignacion(desigId, rolId) {
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

export async function asignarVeedor(partidoId) {
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
