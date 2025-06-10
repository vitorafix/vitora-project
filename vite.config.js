import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', // Your custom CSS file
                'resources/js/app.js',    // Your custom JavaScript file
            ],
            refresh: true, // Auto-reload browser on file changes
        }),
    ],
});
