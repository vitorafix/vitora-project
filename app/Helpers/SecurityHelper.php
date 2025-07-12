<?php

// app/Helpers/SecurityHelper.php

if (!function_exists('hashForCache')) {
    /**
     * Hashes data ONLY for cache keys and logs - NOT for database or SMS.
     * Use this when you need to hide sensitive data in cache/logs.
     *
     * @param string $data The data to hash (e.g., phone number, IP address).
     * @param string $type The context type (e.g., 'otp', 'rate_limit', 'cache').
     * @return string The SHA256 hash.
     */
    function hashForCache(string $data, string $type = 'cache'): string
    {
        // داده را تمیز می‌کند (مثلاً کاراکترهای غیر عددی را از شماره تلفن حذف می‌کند، یا فرمت معتبر IP را تضمین می‌کند)
        // برای شماره تلفن، فقط ارقام را نگه می‌دارد. برای IP، ارقام و نقطه‌ها را نگه می‌دارد.
        // این یک تمیز کردن اولیه است؛ بسته به زمینه، اعتبارسنجی قوی‌تری ممکن است لازم باشد.
        $cleanData = preg_replace('/[^0-9.]/', '', $data);
        return hash('sha256', $cleanData . $type . config('app.key'));
    }
}

if (!function_exists('maskForLog')) {
    /**
     * Masks sensitive data for logging (shows partial info).
     *
     * @param string $data The data to mask.
     * @param string $type Type of data: 'phone' or 'ip'.
     * @return string Masked data.
     */
    function maskForLog(string $data, string $type = 'phone'): string
    {
        if ($type === 'phone') {
            $clean = preg_replace('/[^0-9]/', '', $data);
            // بخش میانی شماره تلفن را ماسک می‌کند
            if (strlen($clean) > 8) { // اطمینان از وجود کاراکترهای کافی برای ماسک کردن
                return substr($clean, 0, 4) . '***' . substr($clean, -4);
            }
            return $clean; // اگر خیلی کوتاه باشد، همانطور که هست برمی‌گرداند
        }
        
        if ($type === 'ip') {
            // Check for IPv6
            if (strpos($data, ':') !== false) {
                $parts = explode(':', $data);
                // For IPv6, mask the middle parts, keep first and last
                if (count($parts) > 2) {
                    return $parts[0] . ':' . '***:' . end($parts);
                }
                return $data; // Return as is if not enough parts to mask meaningfully
            }
            
            // Handle IPv4
            $parts = explode('.', $data);
            // اکتت‌های دوم و سوم آدرس IPv4 را ماسک می‌کند
            if (count($parts) === 4) {
                return $parts[0] . '.***.***.' . $parts[3];
            }
            return $data; // اگر فرمت IPv4 معتبر نباشد، همانطور که هست برمی‌گرداند
        }
        
        return $data; // اگر نوع داده شناسایی نشود، داده اصلی را برمی‌گرداند
    }
}
