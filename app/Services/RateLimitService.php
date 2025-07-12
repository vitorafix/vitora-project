<?php

namespace App\Services;

use App\Contracts\Services\RateLimitServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitService implements RateLimitServiceInterface
{
    protected $sendMaxAttempts;
    protected $sendCooldownMinutes;
    protected $verifyMaxAttempts;
    protected $verifyCooldownMinutes;
    protected $ipMaxAttempts;
    protected $ipCooldownMinutes;

    public function __construct()
    {
        $this->sendMaxAttempts = config('auth.otp.send_attempts.max_attempts', 3);
        $this->sendCooldownMinutes = config('auth.otp.send_attempts.cooldown_minutes', 15);
        $this->verifyMaxAttempts = config('auth.otp.verify_attempts.max_attempts', 5);
        $this->verifyCooldownMinutes = config('auth.otp.verify_attempts.cooldown_minutes', 30);
        $this->ipMaxAttempts = config('auth.otp.ip_attempts.max_attempts', 10);
        $this->ipCooldownMinutes = config('auth.otp.ip_attempts.cooldown_minutes', 60);
    }

    /**
     * Generates a hashed key for SMS attempts cache based on mobile number.
     * This prevents direct exposure of mobile numbers in cache keys.
     *
     * @param string $mobileNumber
     * @return string
     */
    private function getSmsAttemptsCacheKey(string $mobileNumber): string
    {
        return 'sms_attempts_' . hash('sha256', $mobileNumber . config('app.key'));
    }

    /**
     * Generates a hashed key for OTP verification attempts cache based on mobile number.
     * This prevents direct exposure of mobile numbers in cache keys.
     *
     * @param string $mobileNumber
     * @return string
     */
    private function getVerifyAttemptsCacheKey(string $mobileNumber): string
    {
        return 'verify_attempts_' . hash('sha256', $mobileNumber . config('app.key'));
    }

    /**
     * Generates a hashed key for IP attempts cache based on IP address.
     * This prevents direct exposure of IP addresses in cache keys.
     *
     * @param string $ipAddress
     * @return string
     */
    private function getIpAttemptsCacheKey(string $ipAddress): string
    {
        return 'ip_attempts_' . hash('sha256', $ipAddress . config('app.key'));
    }

    /**
     * Checks if mobile number is rate limited for OTP sending and increments attempt count.
     *
     * @param string $mobileNumber
     * @return bool True if not rate limited, false otherwise.
     */
    public function checkAndIncrementSendAttempts(string $mobileNumber): bool
    {
        $attemptsCacheKey = $this->getSmsAttemptsCacheKey($mobileNumber); // Using hashed key
        $attempts = Cache::get($attemptsCacheKey, 0);

        if ($attempts >= $this->sendMaxAttempts) {
            Log::warning('Too many OTP send attempts for mobile number (RateLimitService).', [
                'mobile_hash' => hash('sha256', $mobileNumber),
                'attempts' => $attempts
            ]);
            return false; // Rate limited
        }

        if ($attempts === 0) {
            Cache::put($attemptsCacheKey, 1, now()->addMinutes($this->sendCooldownMinutes));
        } else {
            Cache::increment($attemptsCacheKey);
        }
        Log::info('OTP send attempt count updated (RateLimitService).', ['mobile_hash' => hash('sha256', $mobileNumber), 'new_attempts' => $attempts + 1]);
        return true; // Not rate limited, attempt recorded
    }

    /**
     * Checks if mobile number is rate limited for OTP verification and increments attempt count.
     *
     * @param string $mobileNumber
     * @return bool True if not rate limited, false otherwise.
     */
    public function checkAndIncrementVerifyAttempts(string $mobileNumber): bool
    {
        $verifyAttemptsCacheKey = $this->getVerifyAttemptsCacheKey($mobileNumber); // Using hashed key
        $verifyAttempts = Cache::get($verifyAttemptsCacheKey, 0);

        if ($verifyAttempts >= $this->verifyMaxAttempts) {
            Log::warning('Too many OTP verification attempts for mobile number (RateLimitService).', [
                'mobile_hash' => hash('sha256', $mobileNumber),
                'attempts' => $verifyAttempts
            ]);
            return false; // Rate limited
        }

        if ($verifyAttempts === 0) {
            Cache::put($verifyAttemptsCacheKey, 1, now()->addMinutes($this->verifyCooldownMinutes));
        } else {
            Cache::increment($verifyAttemptsCacheKey);
        }
        Log::info('OTP verification attempt count updated (RateLimitService).', ['mobile_hash' => hash('sha256', $mobileNumber), 'new_attempts' => $verifyAttempts + 1]);
        return true; // Not rate limited, attempt recorded
    }

    /**
     * Checks if IP address is rate limited and increments attempt count.
     *
     * @param string $ipAddress
     * @return bool True if not rate limited, false otherwise.
     */
    public function checkAndIncrementIpAttempts(string $ipAddress): bool
    {
        $ipAttemptsCacheKey = $this->getIpAttemptsCacheKey($ipAddress); // Using hashed key
        $ipAttempts = Cache::get($ipAttemptsCacheKey, 0);

        if ($ipAttempts >= $this->ipMaxAttempts) {
            Log::warning('Too many attempts from IP (RateLimitService).', [
                'ip_hash' => hash('sha256', $ipAddress), // Logging hashed IP
                'attempts' => $ipAttempts
            ]);
            return false; // Rate limited
        }

        if ($ipAttempts === 0) {
            Cache::put($ipAttemptsCacheKey, 1, now()->addMinutes($this->ipCooldownMinutes));
        } else {
            Cache::increment($ipAttemptsCacheKey);
        }
        Log::info('IP attempt count updated (RateLimitService).', ['ip_hash' => hash('sha256', $ipAddress), 'new_attempts' => $ipAttempts + 1]); // Logging hashed IP
        return true; // Not rate limited, attempt recorded
    }

    /**
     * Resets OTP verification attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function resetVerifyAttempts(string $mobileNumber): void
    {
        Cache::forget($this->getVerifyAttemptsCacheKey($mobileNumber)); // Using hashed key
        Log::info('OTP verification attempts reset (RateLimitService).', ['mobile_hash' => hash('sha256', $mobileNumber)]);
    }

    /**
     * Resets IP attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return void
     */
    public function resetIpAttempts(string $ipAddress): void
    {
        Cache::forget($this->getIpAttemptsCacheKey($ipAddress)); // Using hashed key
        Log::info('IP attempts reset (RateLimitService).', ['ip_hash' => hash('sha256', $ipAddress)]); // Logging hashed IP
    }
}
