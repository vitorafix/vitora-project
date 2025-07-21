<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // دامنه‌های مجاز را به صورت صریح مشخص کنید.
    // اگر فرانت‌اند شما روی پورت 8080 اجرا می‌شود، حتماً آن را اضافه کنید.
    // 'http://myshop.test' برای زمانی است که در حالت production یا بدون پورت Vite کار می‌کنید.
    'allowed_origins' => ['http://myshop.test:8080', 'http://myshop.test'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
