import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { glob } from 'glob';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/app.scss',
                'resources/js/app.js',
                'resources/js/client/main.js',
                'resources/js/messages.js',
                ...glob.sync('resources/js/pages/**/*.js'),
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        reportCompressedSize: false,
    },
    resolve: {
        alias: {
            '@': '/resources/js/client',
            '@scss': '/resources/scss',
        },
    },
});
