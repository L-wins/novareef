/* ─────────────────────────────────────────────────────────────
   Reloj en vivo del navbar — fecha + hora, tick cada segundo.
   Separación de capas: CSS resuelve el look (#nav-reloj en app.css),
   Blade pone el <span id="nav-reloj" data-server-epoch="..."> (valor
   inicial + referencia del servidor), este módulo solo formatea y escribe.

   Por qué se calibra contra el servidor:
   NovaReef es un sistema para colegios de árbitros EN COLOMBIA — si dos
   personas de la misma cuenta (ej. "designador") entran desde equipos
   distintos, cada navegador calcula la hora con SU PROPIO reloj de sistema.
   Un equipo con la hora o el huso horario mal configurados mostraría una
   hora distinta e incorrecta — y esa misma hora del navegador es la que
   usa la lógica de "Hoy/Mañana" del DateDivider, así que un reloj de
   sistema mal configurado podría marcar un partido de hoy como si fuera
   de mañana para esa persona en particular. Dos correcciones resuelven
   esto sin depender de que cada máquina esté bien configurada:
   1. Huso horario fijo a America/Bogota (nunca el que "adivine" el navegador).
   2. Un offset calculado una vez al cargar la página (servidor − cliente)
      que corrige la hora si el reloj del equipo está desincronizado.
   ───────────────────────────────────────────────────────────── */

const ZONA_HORARIA = 'America/Bogota';

let intervalId  = null;
let offsetMs    = 0;

/** Formateo puro — sin tocar el DOM, así es trivial de probar o reusar. */
function formatearAhora(fecha = new Date()) {
    const fechaTexto = fecha.toLocaleDateString('es-CO', {
        weekday: 'short', day: '2-digit', month: 'short',
        timeZone: ZONA_HORARIA,
    });
    const horaTexto = fecha.toLocaleTimeString('es-CO', {
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        timeZone: ZONA_HORARIA,
    });

    return { fechaTexto, horaTexto };
}

/** Hora "real" corregida por el offset del servidor, no la del reloj local. */
function ahoraCorregido() {
    return new Date(Date.now() + offsetMs);
}

function tick() {
    const el = document.getElementById('nav-reloj');
    if (!el) return;

    const { fechaTexto, horaTexto } = formatearAhora(ahoraCorregido());
    const fechaEl = el.querySelector('.nav-reloj__fecha');
    const horaEl  = el.querySelector('.nav-reloj__hora');

    if (fechaEl) fechaEl.textContent = fechaTexto;
    if (horaEl)  horaEl.textContent  = horaTexto;
}

/**
 * Programa el próximo tick alineado al siguiente segundo real (no cada
 * "1000ms desde que arrancó el JS"). Sin esto, setInterval(tick, 1000)
 * va acumulando desfase — el hilo principal ocupado, GC, o el propio
 * scheduler del navegador lo retrasan un poco cada vez, y en sesiones
 * largas el segundo mostrado termina saltándose o quedando atrasado.
 */
function programarSiguienteTick() {
    const demora = 1000 - (ahoraCorregido().getTime() % 1000);

    intervalId = setTimeout(function () {
        tick();
        programarSiguienteTick();
    }, demora);
}

/**
 * offset = hora del servidor (al renderizar la página) − hora del cliente
 * (en ese mismo instante). Si el reloj del equipo está bien, offset ≈ 0.
 * Si está desfasado 20 minutos, offset lo compensa automáticamente sin
 * que nadie tenga que arreglar el reloj de Windows de esa máquina.
 */
function calcularOffset(el) {
    const servidorEpoch = Number(el.dataset.serverEpoch);
    if (!Number.isFinite(servidorEpoch) || servidorEpoch <= 0) return 0;

    return servidorEpoch - Date.now();
}

export function initReloj() {
    const el = document.getElementById('nav-reloj');
    if (!el) return;

    offsetMs = calcularOffset(el);

    // Reinicializable sin duplicar el temporizador (ej. si el layout
    // llegara a reinicializar módulos tras una navegación parcial).
    if (intervalId !== null) {
        clearTimeout(intervalId);
        intervalId = null;
    }

    tick();
    programarSiguienteTick();

    // Los navegadores pausan/ralentizan los temporizadores en pestañas en
    // segundo plano para ahorrar batería. Sin esto, al volver a la pestaña
    // el reloj se queda mostrando la hora de cuando se dejó de ver.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') tick();
    });
}
