import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/app.scss',
                'resources/js/app.js',
                'resources/js/members-form-scripts.js',
                'resources/js/select2-contacts-form.js',
                'node_modules/bootstrap4-duallistbox/dist/jquery.bootstrap-duallistbox.min.js',
                'node_modules/bootstrap4-duallistbox/dist/bootstrap-duallistbox.min.css',
                'node_modules/select2/dist/js/select2.min.js',
                'node_modules/select2/dist/css/select2.min.css',
                'node_modules/jquery/dist/jquery.min.js',
            ],
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

