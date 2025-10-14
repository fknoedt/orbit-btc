// vite.config.js
import { defineConfig } from 'vite'
import laravel, { refreshPaths } from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament.css',
                'resources/sass/app.scss',
                'resources/css/tobii.min.css',
                'resources/css/tiny-slider.css',
                'resources/css/icons.css',
                'resources/js/landing/gumshoe.polyfills.min.js',
                'resources/js/landing/tobii.min.js',
                'resources/js/landing/tiny-slider.js',
                'resources/js/landing/easy_background.js',
                'resources/js/landing/plugins.init.js',
                'resources/js/landing/app.js',
                'resources/css/landing.css', // Moved to last
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
                'resources/views/landing/**',
            ],
        }),
    ],
    assetsInclude: ['**/*.jpg', '**/*.png', '**/*.jpeg'], // Include image files
    publicDir: 'public', // Ensure public directory is correctly set
    build: {
        sourcemap: false,
        outDir: 'public/build',
    },
});
