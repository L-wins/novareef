/* ─────────────────────────────────────────────────────────────
   DateDivider — separadores de fecha "en tiempo real" para listas
   cronológicas de partidos (mis-partidos, partidos-torneo).

   Responsabilidades (separación estricta de capas):
   · CSS resuelve el look (sticky, colores) — este módulo nunca toca estilos.
   · Blade solo pone los puentes de datos: data-fecha="Y-m-d" en el
     divisor (.date-divider) o en el chip por tarjeta (.etiqueta-dinamica).
   · Este módulo solo calcula el texto correcto (Hoy/Mañana/fecha) y lo
     recalcula cada minuto, para que una página abierta durante la
     medianoche no se quede mostrando una etiqueta vencida.

   Nota de alcance: esto NO reordena tarjetas entre secciones cuando pasa
   la medianoche (ej. mover una tarjeta de "Mañana" a "Hoy") — solo
   corrige el TEXTO de las etiquetas ya renderizadas. Reubicar tarjetas
   en vivo requeriría recalcular agrupaciones completas en el cliente;
   se prefiere un refresco normal de página para ese caso.
   ───────────────────────────────────────────────────────────── */

function diasDeDiferencia(fechaISO) {
    const hoy    = new Date(); hoy.setHours(0, 0, 0, 0);
    const fecha  = new Date(fechaISO + 'T00:00:00');
    return Math.round((fecha - hoy) / 86400000);
}

/** Chip corto para tarjetas individuales (mis-partidos, partidos-torneo). */
function calcularEtiquetaChip(fechaISO) {
    const diff = diasDeDiferencia(fechaISO);
    if (diff === 0) return { texto: '🔴 HOY',        clase: 'etiqueta-hoy' };
    if (diff === 1) return { texto: '⚡ MAÑANA',     clase: 'etiqueta-manana' };
    if (diff === 2) return { texto: '📅 En 2 días',  clase: 'etiqueta-pronto' };
    if (diff <  0)  return { texto: 'Pasado',        clase: 'etiqueta-pasado' };
    return { texto: `En ${diff} días`, clase: 'etiqueta-futuro' };
}

/** Encabezado del divisor sticky que agrupa varias tarjetas de la misma fecha. */
function calcularEtiquetaDivisor(fechaISO) {
    const diff = diasDeDiferencia(fechaISO);
    if (diff === 0)  return 'Hoy';
    if (diff === 1)  return 'Mañana';
    if (diff === -1) return 'Ayer';

    const fecha     = new Date(fechaISO + 'T00:00:00');
    const formatter = new Intl.DateTimeFormat('es', { weekday: 'long', day: 'numeric', month: 'long' });
    const texto     = formatter.format(fecha);
    return texto.charAt(0).toUpperCase() + texto.slice(1);
}

function actualizarEtiquetasDinamicas() {
    document.querySelectorAll('.etiqueta-dinamica[data-fecha]').forEach(function (el) {
        const fecha = el.dataset.fecha;
        if (!fecha) return;
        const e = calcularEtiquetaChip(fecha);
        el.textContent = e.texto;
        el.className   = 'etiqueta-dinamica ' + e.clase;
    });
}

function actualizarDivisoresFecha() {
    document.querySelectorAll('.date-divider[data-fecha]').forEach(function (el) {
        const fecha = el.dataset.fecha;
        if (!fecha) return;
        const label = el.querySelector('.date-divider__label') ?? el;
        label.textContent = calcularEtiquetaDivisor(fecha);
    });
}

/**
 * Badge HOY/Mañana de partidos-torneo (desi-fecha-badge). Se recalcula con
 * el mismo reloj que el divisor sticky — si no, quedarían contradiciéndose
 * entre sí en una sesión abierta desde antes de medianoche.
 */
function actualizarBadgesFecha() {
    document.querySelectorAll('.desi-fecha-badge[data-fecha]').forEach(function (el) {
        const fecha = el.dataset.fecha;
        if (!fecha) return;
        const diff = diasDeDiferencia(fecha);

        if (diff === 0) {
            el.className = 'desi-fecha-badge desi-hoy-badge';
            el.innerHTML = '<i class="fa-solid fa-circle" style="font-size:.45rem"></i> HOY';
            el.style.display = '';
        } else if (diff === 1) {
            el.className = 'desi-fecha-badge desi-manana-badge';
            el.innerHTML = '<i class="fa-solid fa-sun" style="font-size:.7rem"></i> Mañana';
            el.style.display = '';
        } else {
            el.innerHTML = '';
            el.style.display = 'none';
        }
    });
}

export function initDateDividers() {
    if (!document.querySelector('.etiqueta-dinamica, .date-divider, .desi-fecha-badge')) return;

    actualizarEtiquetasDinamicas();
    actualizarDivisoresFecha();
    actualizarBadgesFecha();

    setInterval(function () {
        actualizarEtiquetasDinamicas();
        actualizarDivisoresFecha();
        actualizarBadgesFecha();
    }, 60000);
}
