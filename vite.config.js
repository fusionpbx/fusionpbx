import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@adminlte': path.resolve(__dirname, 'node_modules/admin-lte'),
            '@bootstrap-icons': path.resolve(__dirname, 'node_modules/bootstrap-icons'),
            '@overlayscrollbars': path.resolve(__dirname, 'node_modules/overlayscrollbars'),
            '@popperjs': path.resolve(__dirname, 'node_modules/@popperjs'),
        }
    }
});
