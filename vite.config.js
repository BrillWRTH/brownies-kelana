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
                google('Playfair Display', {
                    alias: 'serif',
                    weights: [600, 700, 800, 900],
                    styles: ['normal', 'italic'],
                    preload: [
                        { weight: 700, style: 'normal' },
                        { weight: 800, style: 'normal' },
                        { weight: 900, style: 'normal' },
                    ],
                    fallbacks: ['Georgia', 'Cambria', "'Times New Roman'", 'Times', 'serif'],
                    optimizedFallbacks: false,
                }),
                google('Plus Jakarta Sans', {
                    alias: 'sans',
                    weights: [400, 500, 600, 700, 800],
                    styles: ['normal', 'italic'],
                    preload: [
                        { weight: 500, style: 'normal' },
                        { weight: 600, style: 'normal' },
                        { weight: 700, style: 'normal' },
                        { weight: 800, style: 'normal' },
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
