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
                'resources/css/colegios/colegios.css',
                'resources/js/colegios/colegios.js',
                'resources/css/arbitros/arbitros.css',
                'resources/js/arbitros/arbitros.js',
                'resources/css/torneos/torneos.css',
                'resources/js/torneos/torneos.js',
                'resources/css/admin/admin.css',
                'resources/js/admin/admin.js',
                'resources/css/designaciones/designaciones.css',
                'resources/js/designaciones/designaciones.js',
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
});