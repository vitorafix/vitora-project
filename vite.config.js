import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // اضافه کردن فایل‌های جاوااسکریپت و CSS به عنوان نقاط ورودی
            input: [
                'resources/css/app.css',
                'resources/js/app.js', // فقط app.js به عنوان نقطه ورودی اصلی
            ],
            refresh: true,
        }),
    ],
    // بخش server حذف شده چون Vite فقط برای بیلد استفاده می‌شود و نیاز به سرور ندارد
});
