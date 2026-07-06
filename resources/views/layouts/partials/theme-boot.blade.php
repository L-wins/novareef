{{-- Theme boot — ÚNICA excepción permitida de JS inline en el proyecto.

     Debe ejecutarse ANTES de que el navegador pinte con el CSS cargado
     (render-blocking). Si se moviera a un módulo Vite (type="module",
     deferred) se ejecutaría después del primer paint y el usuario vería
     un "flash" del tema incorrecto (FOUC). Es el mismo patrón que usan
     GitHub, X y YouTube.

     Solo resuelve la preferencia → data-theme. La lógica de interacción
     y persistencia vive en resources/js/shared/theme.js. --}}
<script>
    (function () {
        var raiz = document.documentElement;
        var pref = raiz.getAttribute('data-theme-pref') || 'dark';
        var tema = pref === 'system'
            ? (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark')
            : pref;
        raiz.setAttribute('data-theme', tema);
    })();
</script>
