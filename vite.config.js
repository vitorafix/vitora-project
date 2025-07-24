// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/core/app.js', // مسیر اصلی فایل app.js شما (اگر هنوز از آن استفاده می‌کنید)
                'resources/js/app.tsx',     // نقطه ورود اصلی React شما
            ],
            refresh: true,
        }),
        react({
            // تنظیمات برای حل مشکل preamble
            include: "**/*.{jsx,tsx}", // اطمینان از پردازش فایل‌های JSX/TSX
            exclude: "/node_modules/",   // نادیده گرفتن node_modules
            // اگر نیاز به پلاگین‌های Babel خاصی دارید، اینجا اضافه کنید.
            // در اکثر موارد، نیازی به تنظیمات اضافی Babel نیست.
            babel: {
                plugins: []
            }
        }),
    ],
    server: {
        port: 3000,
        host: 'localhost', // تغییر از '0.0.0.0' به 'localhost' برای حل مشکل اتصال مرورگر
        hmr: {
            overlay: false // غیرفعال کردن overlay برای خطاهای HMR
        }
    },
    build: {
        sourcemap: true,
        outDir: 'public/build', // اطمینان از مسیر خروجی
        rollupOptions: {
            output: {
                entryFileNames: `assets/[name]-[hash].js`,
                chunkFileNames: `assets/[name]-[hash].js`,
                assetFileNames: `assets/[name]-[hash].[ext]`,
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('html2canvas')) {
                            return 'vendor-html2canvas';
                        }
                        if (id.includes('jspdf')) {
                            return 'vendor-jspdf';
                        }
                        if (id.includes('axios')) {
                            return 'vendor-axios';
                        }
                        if (id.includes('chart')) {
                            return 'vendor-charts';
                        }
                        if (id.includes('react') || id.includes('react-dom')) {
                            return 'vendor-react';
                        }
                        return 'vendor';
                    }
                    if (id.includes('resources/js/ui')) {
                        return 'ui-components';
                    }
                    if (id.includes('resources/js/auth')) {
                        return 'auth-modules';
                    }
                    if (id.includes('resources/js/admin')) {
                        return 'admin-panel';
                    }
                    if (id.includes('resources/js/cart')) {
                        return 'shopping-cart';
                    }
                    if (id.includes('resources/js/checkout')) {
                        return 'payment-system';
                    }
                },
            },
        },
    },
    resolve: {
        alias: {
            // مطمئن شوید که این aliases به درستی به مسیرهای پروژه شما اشاره می‌کنند
            // با توجه به ساختار Laravel، ممکن است نیاز به تنظیمات دقیق‌تری داشته باشند.
            // مثلاً '@' به 'resources/js' یا 'resources/js/src'
            '@': '/resources/js', // یا مسیر اصلی JS شما
            '@components': '/resources/js/components',
            '@utils': '/resources/js/utils'
        }
    },
    optimizeDeps: {
        include: ['react', 'react-dom', 'axios'],
        exclude: ['@vitejs/plugin-react'] // این خط معمولاً نیازی نیست، اما اگر مشکل دارید نگه دارید
    }
});
