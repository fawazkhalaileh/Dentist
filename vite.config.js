import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { fontsource } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
                'resources/js/site.js',
            ],
            refresh: true,
            fonts: [
                // Served from the @fontsource npm package, so builds do not download fonts.
                fontsource('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // Public website: serif headings, plus Arabic faces for /ar (not preloaded on English pages).
                fontsource('Cormorant Garamond', {
                    weights: [500, 600],
                    styles: ['normal', 'italic'],
                    preload: [{ weight: 500 }],
                }),
                fontsource('IBM Plex Sans Arabic', {
                    weights: [400, 500, 600],
                    subsets: ['arabic'],
                    preload: false,
                }),
                fontsource('Noto Naskh Arabic', {
                    weights: [500, 600],
                    subsets: ['arabic'],
                    preload: false,
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
