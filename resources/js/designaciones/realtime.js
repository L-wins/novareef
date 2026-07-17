/**
 * Laravel Echo + Reverb — conexión en tiempo real y actualización de cards
 * de partido/designación sin recargar la página completa.
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

export function actualizarCardPartido(partido) {
    const card = document.querySelector(`.partido-card[data-partido="${partido.idPartido}"]`);
    if (!card) return;

    // Actualizar clase de estado
    const estadoClasses = ['estado-programado','estado-confirmado','estado-critico','estado-aplazado','estado-finalizado','estado-cancelado'];
    card.classList.remove(...estadoClasses);
    card.classList.add(`estado-${partido.estadoPartido}`);

    if (partido.estadoPartido === 'critico') card.classList.add('es-critico');
    else card.classList.remove('es-critico');
}

export function marcarCardCritico(idPartido) {
    const card = document.querySelector(`.partido-card[data-partido="${idPartido}"]`);
    if (!card) return;
    card.classList.add('es-critico', 'estado-critico');
}

export function actualizarRolCard(designacion) {
    const card = document.getElementById(`rol-card-${designacion.idRol}`);
    if (!card) return;
    // Recarga simplificada — el server-side rendering hace el heavy lifting
    if (designacion.idPartido === window.partidoId) {
        setTimeout(() => location.reload(), 500);
    }
}

/** Suscribe los canales privados del colegio — solo si hay un colegio activo en la página. */
export function suscribirCanalesTiempoReal() {
    if (!window.colegioId) return;

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
