/* ─────────────────────────────────────────────────────────────
   Theme — orquestación del tema de la interfaz (light | dark | system).

   Responsabilidades (separación estricta de capas):
   · CSS (tokens.css) resuelve el look — este módulo NUNCA toca estilos.
   · Blade (theme-boot) aplica el tema antes del primer paint.
   · Este módulo solo: cambia data-theme, persiste la preferencia en BD
     y reacciona a prefers-color-scheme cuando la preferencia es "system".

   UI esperada: botones con [data-theme-set="light|dark|system"].
   ───────────────────────────────────────────────────────────── */

const META_COLOR = { dark: '#020617', light: '#eef2f7' };

const raiz = document.documentElement;
const mediaLight = window.matchMedia('(prefers-color-scheme: light)');

function temaEfectivo(pref) {
    return pref === 'system' ? (mediaLight.matches ? 'light' : 'dark') : pref;
}

function aplicar(pref) {
    const tema = temaEfectivo(pref);

    // Suprimir transiciones durante el cambio (evita recoloreado animado)
    raiz.classList.add('theme-switching');
    raiz.setAttribute('data-theme-pref', pref);
    raiz.setAttribute('data-theme', tema);
    requestAnimationFrame(() => raiz.classList.remove('theme-switching'));

    // Chrome del navegador móvil acorde al tema
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', META_COLOR[tema]);

    // Estado visual del selector
    document.querySelectorAll('[data-theme-set]').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.themeSet === pref);
        btn.setAttribute('aria-pressed', String(btn.dataset.themeSet === pref));
    });
}

async function persistir(pref) {
    const csrf     = document.querySelector('meta[name="csrf-token"]')?.content;
    const endpoint = document.querySelector('.theme-switch')?.dataset.themeEndpoint;
    if (!csrf || !endpoint) return;

    try {
        await fetch(endpoint, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ tema: pref }),
        });
    } catch {
        // Silencioso: el tema ya se aplicó en cliente; la persistencia
        // se reintentará la próxima vez que el usuario cambie el tema.
    }
}

export function initTheme() {
    // Estado inicial del selector (el tema ya lo aplicó theme-boot)
    aplicar(raiz.getAttribute('data-theme-pref') || 'dark');

    document.querySelectorAll('[data-theme-set]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const pref = btn.dataset.themeSet;
            aplicar(pref);
            persistir(pref);
        });
    });

    // Con preferencia "system", seguir los cambios del SO en vivo
    mediaLight.addEventListener('change', () => {
        if (raiz.getAttribute('data-theme-pref') === 'system') {
            aplicar('system');
        }
    });
}
