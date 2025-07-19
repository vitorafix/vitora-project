import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '127.0.0.1', // اطمینان حاصل کنید که Vite روی IPv4 localhost اجرا می‌شود
        port: 5173, // پورت فرانت‌اند شما
        hmr: {
            host: '127.0.0.1', // HMR نیز روی IPv4 localhost باشد
        },
        proxy: {
            '/api': { // هر درخواستی که با /api شروع شود
                target: 'http://127.0.0.1:8080', // به بک‌اند لاراول شما پروکسی شود
                changeOrigin: true, // هدر Host را به target تغییر می‌دهد
                rewrite: (path) => path.replace(/^\/api/, ''), // /api را از مسیر درخواست حذف می‌کند
            },
            '/sanctum/csrf-cookie': { // درخواست Sanctum CSRF Cookie را نیز پروکسی می‌کند
                target: 'http://127.0.0.1:8080',
                changeOrigin: true,
            },
        },
    },
});
