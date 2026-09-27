import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/tailwind.css',
                'resources/assets/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // no public .map files: nothing consumes them, and they publish the
        // unminified source next to the bundle (#2175)
        sourcemap: false,
    },
});
