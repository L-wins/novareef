import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/welcome.css',
                'resources/js/welcome.js',
                'resources/css/auth/login.css',
                'resources/js/auth/login.js',
                'resources/css/arbitros/arbitros.css',
                'resources/js/arbitros/arbitros.js',
                'resources/css/torneos/torneos.css',
                'resources/js/torneos/torneos.js',
                'resources/css/admin/admin.css',
                'resources/js/admin/admin.js',
                'resources/css/designaciones/designaciones.css',
                'resources/js/designaciones/designaciones.js',
                'resources/js/designaciones/importar-designaciones.js',
                'resources/js/disponibilidad/disponibilidad.js',
                'resources/js/configuracion/configuracion.js',
                'resources/css/configuracion/configuracion.css',
                'resources/css/finanzas/finanzas.css',
                'resources/js/finanzas/finanzas.js',
                'resources/js/finanzas/cobro-masivo.js',
                'resources/js/finanzas/ficha-arbitro.js',
                'resources/js/finanzas/institucional.js',
                'resources/css/sanciones/sanciones.css',
                'resources/js/sanciones/sanciones.js',
                'resources/css/academico/academico.css',
                'resources/js/academico/academico.js',
            ],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    server: {
        host: '127.0.0.1',
        // Fuerza también el host que Laravel inyecta en @vite() — evita que el
        // navegador intente conectar por [::1] (IPv6) cuando localhost resuelve ahí primero.
        hmr: {
            host: '127.0.0.1',
        },
    },
});