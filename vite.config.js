import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    // اگر از HTTPS در لوکال هاست استفاده می‌کنید، این بخش را فعال کنید
    // server: {
    //     https: true, // برای فعال‌سازی HTTPS
    //     host: 'localhost',
    // },
});
