import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            // اضافه کردن cart.js، search.js و auth.js به عنوان نقاط ورودی جداگانه
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/cart.js',    // اضافه شده
                'resources/js/search.js',  // اضافه شده
                'resources/js/auth.js',    // اضافه شده برای منطق ثبت‌نام و احراز هویت
            ],
            refresh: true,
        }),
    ],
    // بخش server دیگر نیازی به host, port, hmr, proxy ندارد
    // زیرا Vite فقط برای بیلد استفاده می‌شود، نه برای سرو کردن
});
