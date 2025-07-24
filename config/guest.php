<?php

return [
    // نام کوکی که UUID مهمان در آن ذخیره می‌شود
    'uuid_cookie_name' => 'guest_uuid',
    
    // کلید سشن که UUID مهمان در آن ذخیره می‌شود
    'uuid_session_key' => 'guest_uuid',
    
    // مدت زمان نگهداری کوکی به روز (پیش‌فرض: 30 روز)
    'cookie_lifetime_days' => 30,
    
    // تنظیم امنیت کوکی برای HTTPS
    // null = تشخیص خودکار بر اساس پروتکل درخواست
    // true = همیشه secure (فقط روی HTTPS کار می‌کند)
    // false = هرگز secure نباشد
    'cookie_secure' => null,
    
    // تنظیم httpOnly برای کوکی (جلوگیری از دسترسی JavaScript)
    // true = فقط از طریق HTTP قابل دسترسی (امن‌تر)
    // false = از طریق JavaScript هم قابل دسترسی
    'cookie_http_only' => true,
];