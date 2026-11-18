import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                google('Lato', {
                    alias: 'sans',
                    weights: [400, 700, 900],
                    styles: ['normal', 'italic'],
                    preload: [
                        { weight: 400, style: 'normal' },
                        { weight: 700, style: 'normal' },
                    ],
                    fallbacks: [
                        'ui-sans-serif',
                        'system-ui',
                        '-apple-system',
                        'BlinkMacSystemFont',
                        "'Segoe UI'",
                        'Roboto',
                        "'Helvetica Neue'",
                        'Arial',
                        'sans-serif',
                    ],
                    optimizedFallbacks: false,
                }),
                google('Lato', {
                    alias: 'serif',
                    weights: [400, 700, 900],
                    styles: ['normal', 'italic'],
                    preload: [
                        { weight: 700, style: 'normal' },
                    ],
                    fallbacks: [
                        'ui-sans-serif',
                        'system-ui',
                        '-apple-system',
                        'BlinkMacSystemFont',
                        "'Segoe UI'",
                        'Roboto',
                        "'Helvetica Neue'",
                        'Arial',
                        'sans-serif',
                    ],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
