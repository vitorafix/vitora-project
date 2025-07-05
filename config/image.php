<?php

return [
    // تنظیمات اندازه تصویر
    'max_width' => env('IMAGE_MAX_WIDTH', 1200),
    'max_height' => env('IMAGE_MAX_HEIGHT', 800),
    'quality' => env('IMAGE_QUALITY', 85),
    
    // تنظیمات فایل
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    'allowed_mimes' => [
        'image/jpeg',
        'image/png', 
        'image/gif',
        'image/webp',
        'image/svg+xml'
    ],
    'max_size' => env('IMAGE_MAX_SIZE', 5 * 1024 * 1024), // 5MB
    
    // تنظیمات ذخیره‌سازی
    'disk' => env('IMAGE_DISK', 'public'),
    'directory' => env('IMAGE_DIRECTORY', 'images/products'),
    
    // تنظیمات تولید تصاویر مختلف
    'thumbnails' => [
        'small' => [
            'width' => 150,
            'height' => 150,
            'quality' => 80
        ],
        'medium' => [
            'width' => 300,
            'height' => 300,
            'quality' => 85
        ],
        'large' => [
            'width' => 800,
            'height' => 800,
            'quality' => 90
        ]
    ],
    
    // تنظیمات پردازش
    'auto_orient' => true,
    'strip_metadata' => true,
    'progressive_jpeg' => true,
    'optimize' => true,
    
    // تنظیمات watermark (اختیاری)
    'watermark' => [
        'enabled' => env('IMAGE_WATERMARK_ENABLED', false),
        'path' => env('IMAGE_WATERMARK_PATH', 'watermark.png'),
        'position' => env('IMAGE_WATERMARK_POSITION', 'bottom-right'),
        'opacity' => env('IMAGE_WATERMARK_OPACITY', 50)
    ],
    
    // تنظیمات backup
    'backup' => [
        'enabled' => env('IMAGE_BACKUP_ENABLED', false),
        'disk' => env('IMAGE_BACKUP_DISK', 's3'),
        'delete_local_after_backup' => env('IMAGE_DELETE_LOCAL_AFTER_BACKUP', false)
    ],
    
    // تنظیمات امنیتی
    'scan_for_malware' => env('IMAGE_SCAN_MALWARE', false),
    'block_suspicious_files' => true,
    
    // تنظیمات cache
    'cache_duration' => env('IMAGE_CACHE_DURATION', 3600 * 24 * 30), // 30 روز
];