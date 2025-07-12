// sw.js - نسخه بهبود یافته
const CACHE_NAME = 'my-pwa-cache-v1';
const OFFLINE_URL = '/offline.html'; // مسیر صفحه آفلاین

// فایل‌های اصلی که باید cache شوند
const urlsToCache = [
    '/', // صفحه اصلی
    '/css/app.css', // فایل CSS اصلی شما
    '/js/app.js',   // فایل JavaScript اصلی شما
    OFFLINE_URL,    // صفحه آفلاین
    // اضافه کردن مسیر آیکون‌های PWA (مطابق با manifest.json)
    '/images/icon-192x192.png',
    '/images/icon-512x512.png',
    // می‌توانید فونت‌ها و سایر منابع حیاتی را نیز اینجا اضافه کنید
    'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'
];

// نصب Service Worker
self.addEventListener('install', (event) => {
    console.log('Service Worker نصب شد');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('Cache باز شد');
                return cache.addAll(urlsToCache);
            })
            .then(() => {
                // فعال‌سازی فوری Service Worker جدید
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('کش کردن فایل‌ها در هنگام نصب با خطا مواجه شد:', error);
            })
    );
});

// فعال‌سازی Service Worker
self.addEventListener('activate', (event) => {
    console.log('Service Worker فعال شد');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    // پاک کردن cache های قدیمی
                    if (cacheName !== CACHE_NAME) {
                        console.log('Cache قدیمی پاک شد:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // کنترل همه صفحات باز
            return self.clients.claim();
        })
    );
});

// مدیریت درخواست‌های شبکه
self.addEventListener('fetch', (event) => {
    // فقط درخواست‌های HTTP/HTTPS را پردازش کن
    if (event.request.url.startsWith('http')) {
        event.respondWith(
            caches.match(event.request)
                .then((response) => {
                    // اگر در cache موجود بود، برگردان
                    if (response) {
                        return response;
                    }
                    
                    // درخواست جدید به شبکه
                    return fetch(event.request)
                        .then((response) => {
                            // بررسی معتبر بودن پاسخ
                            if (!response || response.status !== 200 || response.type !== 'basic') {
                                return response;
                            }
                            
                            // کپی کردن پاسخ برای cache
                            const responseToCache = response.clone();
                            
                            caches.open(CACHE_NAME)
                                .then((cache) => {
                                    cache.put(event.request, responseToCache);
                                });
                            
                            return response;
                        })
                        .catch(() => {
                            // اگر شبکه در دسترس نبود و درخواست برای یک سند HTML بود، صفحه آفلاین نمایش بده
                            if (event.request.destination === 'document') {
                                return caches.match(OFFLINE_URL);
                            }
                            // برای سایر درخواست‌ها (مثلاً تصاویر، JS، CSS) که در کش نیستند و شبکه قطع است،
                            // می‌توان یک پاسخ fallback پیش‌فرض (مثلاً تصویر placeholder) برگرداند.
                            // در این مثال، صرفاً undefined برگردانده می‌شود که منجر به خطای مرورگر می‌شود.
                            // برای بهبود، می‌توانید اینجا یک fallback برای انواع مختلف منابع اضافه کنید.
                            return null; // یا یک پاسخ fallback مناسب
                        });
                })
        );
    }
});

// مدیریت پیام‌ها از صفحه اصلی (برای مواردی مانند skipWaiting دستی)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// آپدیت cache (اختیاری - برای همگام‌سازی پس‌زمینه)
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(
            // عملیات همگام‌سازی در پس‌زمینه
            console.log('همگام‌سازی پس‌زمینه انجام شد')
        );
    }
});
