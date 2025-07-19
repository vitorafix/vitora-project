<?php

namespace App\Services;

use App\Contracts\Services\RateLimitServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// Make sure SecurityHelper.php is properly autoloaded, e.g., by adding it to composer.json
// or by using a global helper file in Laravel.
// For this example, we assume hashForCache and maskForLog functions are available.

class RateLimitService implements RateLimitServiceInterface
{
    // Constants for rate limiting
    const OTP_SEND_ATTEMPTS_LIMIT = 3; // Max attempts to send OTP per mobile number
    const OTP_SEND_WINDOW_MINUTES = 1; // Time window for OTP send attempts
    const OTP_IP_ATTEMPTS_LIMIT = 5; // Max attempts to send OTP per IP address
    const OTP_IP_WINDOW_MINUTES = 5; // Time window for IP-based OTP send attempts

    const OTP_VERIFY_ATTEMPTS_LIMIT = 5; // Max attempts to verify OTP per mobile number
    const OTP_VERIFY_WINDOW_MINUTES = 5; // Time window for OTP verification attempts
    const OTP_IP_VERIFY_ATTEMPTS_LIMIT = 10; // Max attempts to verify OTP per IP address
    const OTP_IP_VERIFY_WINDOW_MINUTES = 10; // Time window for IP-based OTP verification attempts

    /**
     * Generates a hashed cache key for mobile number based rate limits.
     *
     * @param string $mobileNumber
     * @param string $type 'send' or 'verify'
     * @return string
     */
    private function getMobileCacheKey(string $mobileNumber, string $type): string
    {
        return 'rate_limit:' . $type . ':' . hashForCache($mobileNumber, $type . '_mobile_rate_limit');
    }

    /**
     * Generates a hashed cache key for IP address based rate limits.
     *
     * @param string $ipAddress
     * @param string $type 'send' or 'verify'
     * @return string
     */
    private function getIpCacheKey(string $ipAddress, string $type): string
    {
        return 'rate_limit:' . $type . ':ip:' . hashForCache($ipAddress, $type . '_ip_rate_limit');
    }

    /**
     * Checks and increments the OTP send attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return bool True if allowed, false if rate limit exceeded.
     */
    public function checkAndIncrementSendAttempts(string $mobileNumber): bool
    {
        $key = $this->getMobileCacheKey($mobileNumber, 'send');
        $attempts = Cache::get($key, 0);

        if ($attempts >= self::OTP_SEND_ATTEMPTS_LIMIT) {
            Log::warning('RateLimitService: Mobile send rate limit exceeded for: ' . maskForLog($mobileNumber, 'phone'));
            return false;
        }

        Cache::put($key, $attempts + 1, now()->addMinutes(self::OTP_SEND_WINDOW_MINUTES));
        Log::info('OTP send attempt count updated (RateLimitService).', [
            'mobile_hash' => hashForCache($mobileNumber, 'send_mobile_rate_limit'),
            'new_attempts' => $attempts + 1,
            'mobile_masked' => maskForLog($mobileNumber, 'phone')
        ]);
        return true;
    }

    /**
     * Gets the current OTP send attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return int
     */
    public function getSendAttempts(string $mobileNumber): int
    {
        $key = $this->getMobileCacheKey($mobileNumber, 'send');
        return Cache::get($key, 0);
    }

    /**
     * Checks and increments the OTP send attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return bool True if allowed, false if rate limit exceeded.
     */
    public function checkAndIncrementIpAttempts(string $ipAddress): bool
    {
        $key = $this->getIpCacheKey($ipAddress, 'send');
        $attempts = Cache::get($key, 0);

        if ($attempts >= self::OTP_IP_ATTEMPTS_LIMIT) {
            Log::warning('RateLimitService: IP send rate limit exceeded for: ' . maskForLog($ipAddress, 'ip'));
            return false;
        }

        Cache::put($key, $attempts + 1, now()->addMinutes(self::OTP_IP_WINDOW_MINUTES));
        Log::info('IP attempt count updated (RateLimitService).', [
            'ip_hash' => hashForCache($ipAddress, 'send_ip_rate_limit'),
            'new_attempts' => $attempts + 1,
            'ip_masked' => maskForLog($ipAddress, 'ip')
        ]);
        return true;
    }

    /**
     * Gets the current OTP send attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return int
     */
    public function getIpAttempts(string $ipAddress): int
    {
        $key = $this->getIpCacheKey($ipAddress, 'send');
        return Cache::get($key, 0);
    }

    /**
     * Checks and increments the OTP verification attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return bool True if allowed, false if rate limit exceeded.
     */
    public function checkAndIncrementVerifyAttempts(string $mobileNumber): bool
    {
        $key = $this->getMobileCacheKey($mobileNumber, 'verify');
        $attempts = Cache::get($key, 0);

        if ($attempts >= self::OTP_VERIFY_ATTEMPTS_LIMIT) {
            Log::warning('RateLimitService: Mobile verification rate limit exceeded for: ' . maskForLog($mobileNumber, 'phone'));
            return false;
        }

        Cache::put($key, $attempts + 1, now()->addMinutes(self::OTP_VERIFY_WINDOW_MINUTES));
        Log::info('OTP verification attempt count updated (RateLimitService).', [
            'mobile_hash' => hashForCache($mobileNumber, 'verify_mobile_rate_limit'),
            'new_attempts' => $attempts + 1,
            'mobile_masked' => maskForLog($mobileNumber, 'phone')
        ]);
        return true;
    }

    /**
     * Gets the current OTP verification attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return int
     */
    public function getVerifyAttempts(string $mobileNumber): int
    {
        $key = $this->getMobileCacheKey($mobileNumber, 'verify');
        return Cache::get($key, 0);
    }

    /**
     * Checks and increments the OTP verification attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return bool True if allowed, false if rate limit exceeded.
     */
    public function checkAndIncrementIpVerifyAttempts(string $ipAddress): bool
    {
        $key = $this->getIpCacheKey($ipAddress, 'verify');
        $attempts = Cache::get($key, 0);

        if ($attempts >= self::OTP_IP_VERIFY_ATTEMPTS_LIMIT) {
            Log::warning('RateLimitService: IP verification rate limit exceeded for: ' . maskForLog($ipAddress, 'ip'));
            return false;
        }

        Cache::put($key, $attempts + 1, now()->addMinutes(self::OTP_IP_VERIFY_WINDOW_MINUTES));
        Log::info('IP verification attempt count updated (RateLimitService).', [
            'ip_hash' => hashForCache($ipAddress, 'verify_ip_rate_limit'),
            'new_attempts' => $attempts + 1,
            'ip_masked' => maskForLog($ipAddress, 'ip')
        ]);
        return true;
    }

    /**
     * Gets the current OTP verification attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return int
     */
    public function getIpVerifyAttempts(string $ipAddress): int
    {
        $key = $this->getIpCacheKey($ipAddress, 'verify');
        return Cache::get($key, 0);
    }

    /**
     * Clears all rate limit attempts for a given mobile number.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function clearAttempts(string $mobileNumber): void
    {
        Cache::forget($this->getMobileCacheKey($mobileNumber, 'send'));
        Cache::forget($this->getMobileCacheKey($mobileNumber, 'verify'));
        Log::info('RateLimitService: Cleared all attempts for mobile: ' . maskForLog($mobileNumber, 'phone'));
    }

    /**
     * Clears all IP-based rate limit attempts for a given IP address.
     *
     * @param string $ipAddress
     * @return void
     */
    public function clearIpAttempts(string $ipAddress): void
    {
        Cache::forget($this->getIpCacheKey($ipAddress, 'send'));
        Cache::forget($this->getIpCacheKey($ipAddress, 'verify'));
        Log::info('RateLimitService: Cleared all IP attempts for: ' . maskForLog($ipAddress, 'ip'));
    }
}
