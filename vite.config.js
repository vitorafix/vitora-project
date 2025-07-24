// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/core/app.js', // مسیر اصلی فایل app.js شما
            ],
            refresh: true,
        }),
    ],
    build: {
        // این گزینه به Vite می‌گوید که فایل‌های خروجی را در چه مسیری قرار دهد
        outDir: 'public/build',
        // فعال کردن Code Splitting به صورت خودکار برای dynamic imports
        // Vite به صورت پیش‌فرض این کار را برای `import()` انجام می‌دهد
        // اما می‌توانید تنظیمات پیشرفته‌تری را اینجا اضافه کنید.
        rollupOptions: {
            output: {
                // این الگو به Vite می‌گوید که چانک‌های جاوااسکریپت را چگونه نام‌گذاری کند.
                // [name] نام اصلی چانک را حفظ می‌کند (مثلاً 'cart', 'admin')
                // [hash] یک هش منحصر به فرد اضافه می‌کند برای کشینگ بهتر
                entryFileNames: `assets/[name]-[hash].js`,
                chunkFileNames: `assets/[name]-[hash].js`,
                assetFileNames: `assets/[name]-[hash].[ext]`,

                // می‌توانید چانک‌های خاصی را به صورت دستی گروه‌بندی کنید (اختیاری)
                // این کار برای کتابخانه‌های بزرگ یا ماژول‌های پرکاربرد مفید است.
                manualChunks(id) {
                    // مثال: جدا کردن کتابخانه‌های خارجی بزرگ مثل html2canvas یا jspdf
                    if (id.includes('node_modules')) {
                        // می‌توانید کتابخانه‌های خاصی را به چانک‌های جداگانه تقسیم کنید
                        // مثلاً 'vendor-html2canvas' یا 'vendor-jspdf'
                        if (id.includes('html2canvas')) {
                            return 'vendor-html2canvas';
                        }
                        if (id.includes('jspdf')) {
                            return 'vendor-jspdf';
                        }
                        // اضافه کردن axios و charts به چانک‌های جداگانه
                        if (id.includes('axios')) {
                            return 'vendor-axios';
                        }
                        if (id.includes('chart')) { // برای کتابخانه‌های نمودار مثل Chart.js
                            return 'vendor-charts';
                        }
                        // در غیر این صورت، همه node_modules را در یک چانک vendor قرار دهید
                        return 'vendor';
                    }
                    // مثال: گروه‌بندی ماژول‌های UI در یک چانک جداگانه
                    if (id.includes('resources/js/ui')) {
                        return 'ui-components';
                    }
                    // مثال: گروه‌بندی ماژول‌های auth در یک چانک جداگانه
                    if (id.includes('resources/js/auth')) {
                        return 'auth-modules';
                    }
                    // گروه‌بندی ماژول‌های پنل ادمین در یک چانک جداگانه
                    if (id.includes('resources/js/admin')) {
                        return 'admin-panel';
                    }
                    // گروه‌بندی ماژول‌های سبد خرید در یک چانک جداگانه
                    if (id.includes('resources/js/cart')) {
                        return 'shopping-cart';
                    }
                    // گروه‌بندی ماژول‌های پرداخت در یک چانک جداگانه
                    if (id.includes('resources/js/checkout')) {
                        return 'payment-system';
                    }
                    // اگر هیچ یک از شرط‌ها مطابقت نداشت، Vite خودش تصمیم می‌گیرد
                },
            },
        },
    },
});
