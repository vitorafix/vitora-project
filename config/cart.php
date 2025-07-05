<?php

return [
    'cache_ttl' => env('CART_CACHE_TTL', 3600), // زمان نگهداری کش سبد خرید (ثانیه)
    'max_items_per_cart' => env('CART_MAX_ITEMS', 100), // حداکثر تعداد آیتم‌های منحصر به فرد در سبد خرید
    'max_quantity_per_item' => env('CART_MAX_QUANTITY_PER_ITEM', 999), // حداکثر تعداد برای یک محصول خاص در سبد
    'stock_check_enabled' => env('CART_STOCK_CHECK', true), // فعال/غیرفعال کردن بررسی موجودی
    'rate_limit_cooldown' => env('CART_RATE_LIMIT_COOLDOWN', 2), // زمان خنک‌کننده برای Rate Limiting (ثانیه)
    'stock_reservation_minutes' => env('CART_STOCK_RESERVATION_MINUTES', 15), // زمان رزرو موجودی (دقیقه)
    'cleanup_days' => env('CART_CLEANUP_DAYS', 30), // تعداد روزها برای پاکسازی سبدهای مهمان منقضی شده
    'enable_metrics' => env('CART_ENABLE_METRICS', true), // فعال/غیرفعال کردن ثبت معیارهای عملکرد
    'keep_cart_on_clear' => env('CART_KEEP_ON_CLEAR', false), // بهبود: آیا سبد خرید پس از پاکسازی آیتم‌ها حفظ شود؟
    'max_bulk_operations' => env('CART_MAX_BULK_OPERATIONS', 100), // بهبود: حداکثر تعداد آیتم در یک عملیات گروهی
];
