<?php

namespace App\Services;

use App\Contracts\Services\OtpServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log; // برای لاگ کردن اضافه شد

class OtpService implements OtpServiceInterface
{
    /**
     * یک کد OTP را تولید، ذخیره و برمی‌گرداند.
     *
     * @param string $mobileNumber
     * @return string
     */
    public function generateAndStore(string $mobileNumber): string
    {
        $otp = random_int(100000, 999999); // تولید یک کد 6 رقمی
        $cacheKey = 'otp_' . $mobileNumber;

        // ذخیره OTP در کش به مدت زمان مشخص شده در config/auth.php
        // مقدار به صورت integer ذخیره می‌شود.
        Cache::put($cacheKey, $otp, now()->addMinutes(config('auth.otp.expiry_minutes', 2)));

        Log::info('OTP generated and stored.', [
            'mobile_hash' => hash('sha256', $mobileNumber),
            'otp_cache_key' => $cacheKey,
            'otp_value_for_debug' => $otp // فقط برای اشکال‌زدایی، در پروداکشن حذف شود
        ]);

        return (string) $otp; // برگرداندن به صورت رشته
    }

    /**
     * کد OTP وارد شده را تأیید می‌کند.
     *
     * @param string $mobileNumber
     * @param string $enteredOtp
     * @return bool
     */
    public function verify(string $mobileNumber, string $enteredOtp): bool
    {
        $cacheKey = 'otp_' . $mobileNumber;
        $storedOtp = Cache::get($cacheKey);

        Log::debug('Verifying OTP via OtpService', [
            'mobile_hash' => hash('sha256', $mobileNumber),
            'otp_cache_key' => $cacheKey,
            'stored_otp_exists_before_check' => Cache::has($cacheKey),
            'stored_otp_value_for_debug' => $storedOtp, // فقط برای اشکال‌زدایی، در پروداکشن حذف شود
            'entered_otp_value_for_debug' => $enteredOtp, // فقط برای اشکال‌زدایی، در پروداکشن حذف شود
            'type_of_stored_otp' => gettype($storedOtp), // برای اشکال‌زدایی نوع داده
            'type_of_entered_otp' => gettype($enteredOtp), // برای اشکال‌زدایی نوع داده
        ]);

        // اگر OTP در کش وجود ندارد یا منقضی شده است
        if (!$storedOtp) {
            Log::warning('OTP not found in cache or expired.', ['mobile_hash' => hash('sha256', $mobileNumber)]);
            return false;
        }

        // --- اصلاح مهم: مقایسه با == یا تبدیل نوع ---
        // مقایسه مقادیر پس از اطمینان از یکسان بودن نوع (با تبدیل به رشته)
        $isVerified = (string) $storedOtp === (string) $enteredOtp;
        // یا به سادگی از == استفاده کنید که مقایسه نوع سختگیرانه ندارد:
        // $isVerified = $storedOtp == $enteredOtp;
        // ------------------------------------------

        if ($isVerified) {
            Cache::forget($cacheKey); // حذف OTP از کش پس از تأیید موفقیت‌آمیز
            Log::info('OTP verified successfully and removed from cache.', ['mobile_hash' => hash('sha256', $mobileNumber)]);
        } else {
            Log::warning('OTP verification failed: Mismatch.', [
                'mobile_hash' => hash('sha256', $mobileNumber),
                'stored_otp' => $storedOtp,
                'entered_otp' => $enteredOtp
            ]);
        }

        return $isVerified;
    }
}
