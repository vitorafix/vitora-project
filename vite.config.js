        import { defineConfig } from 'vite';
        import laravel from 'laravel-vite-plugin';

        export default defineConfig({
            plugins: [
                laravel({
                    input: ['resources/css/app.css', 'resources/js/app.js'],
                    refresh: true,
                }),
            ],
            // بخش server دیگر نیازی به host, port, hmr, proxy ندارد
            // زیرا Vite فقط برای بیلد استفاده می‌شود، نه برای سرو کردن
        });
        