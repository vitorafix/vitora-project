<?php

namespace App\Services;

use App\Contracts\Services\OtpServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\DB; // حذف شد: مسئولیت لاگ به AuditService واگذار شد
// use Illuminate\Support\Facades\Request; // حذف شد: مسئولیت لاگ به AuditService واگذار شد
// use Illuminate\Support\Facades\Session; // حذف شد: مسئولیت لاگ به AuditService واگذار شد
use Illuminate\Support\Str; // این همچنان می‌تواند برای تولید کد استفاده شود اگرچه در حال حاضر مستقیماً استفاده نمی‌شود.

class OtpService implements OtpServiceInterface
{
    // پیشوند کلیدهای کش برای ذخیره OTP
    const OTP_CACHE_PREFIX = 'otp_';
    // زمان انقضای OTP (بر حسب دقیقه) - این مقدار باید با config/auth.php هماهنگ باشد
    protected $otpExpiryMinutes;

    public function __construct()
    {
        // خواندن زمان انقضای OTP از فایل پیکربندی auth.php
        $this->otpExpiryMinutes = config('auth.otp.expiry_minutes', 2);
    }

    /**
     * Generates a new OTP, stores it in cache, and returns it.
     * نام متد به generateAndStoreOtp تغییر یافت تا با MobileAuthController هماهنگ باشد.
     *
     * @param string $mobileNumber
     * @return string The generated OTP
     */
    public function generateAndStoreOtp(string $mobileNumber): string
    {
        // تولید یک کد OTP 6 رقمی تصادفی
        $otp = (string) random_int(100000, 999999);
        $cacheKey = self::OTP_CACHE_PREFIX . $mobileNumber;

        // ذخیره OTP در کش با زمان انقضا
        Cache::put($cacheKey, $otp, now()->addMinutes($this->otpExpiryMinutes));

        Log::info("Generated and stored OTP for mobile: {$mobileNumber} - OTP: {$otp}");

        return $otp;
    }

    /**
     * Verifies the provided OTP against the stored one for the given mobile number.
     * نام متد به verifyOtp تغییر یافت تا با MobileAuthController هماهنگ باشد.
     *
     * @param string $mobileNumber
     * @param string $otp The OTP provided by the user
     * @return bool True if OTP is valid, false otherwise
     */
    public function verifyOtp(string $mobileNumber, string $otp): bool
    {
        $cacheKey = self::OTP_CACHE_PREFIX . $mobileNumber;
        $storedOtp = Cache::get($cacheKey);

        // لاگ کردن برای دیباگ: کد ذخیره شده و کد وارد شده
        Log::debug("Verifying OTP for mobile: {$mobileNumber}. Stored: {$storedOtp}, Provided: {$otp}");

        // بررسی اینکه آیا OTP ذخیره شده وجود دارد و با OTP وارد شده مطابقت دارد
        if ($storedOtp && (string)$storedOtp === (string)$otp) {
            Log::info("OTP successfully verified for mobile: {$mobileNumber}");
            return true;
        }

        Log::warning("OTP verification failed for mobile: {$mobileNumber}. Provided OTP: {$otp}");
        return false;
    }

    /**
     * Clears the stored OTP for a given mobile number after successful verification.
     * این متد اضافه شد زیرا در MobileAuthController فراخوانی می‌شود.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function clearOtp(string $mobileNumber): void
    {
        $cacheKey = self::OTP_CACHE_PREFIX . $mobileNumber;
        Cache::forget($cacheKey);
        Log::info("Cleared OTP from cache for mobile: {$mobileNumber}");
    }

    // متدهای writeAuditLog, hashMobile, getGeoLocation از اینجا حذف شدند.
    // این مسئولیت‌ها به AuditService واگذار شده‌اند.
}
