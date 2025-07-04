<?php

namespace App\Services;

use App\Contracts\Services\OtpServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class OtpService implements OtpServiceInterface
{
    /**
     * تولید و ذخیره OTP
     */
    public function generateAndStore(string $mobileNumber): string
    {
        $otp = random_int(100000, 999999);
        $cacheKey = 'otp_' . $mobileNumber;

        Cache::put($cacheKey, $otp, now()->addMinutes(config('auth.otp.expiry_minutes', 2)));

        $this->writeAuditLog([
            'action' => 'otp_generated',
            'description' => 'OTP generated and stored.',
            'mobile_hash' => $this->hashMobile($mobileNumber),
        ]);

        $logData = [
            'mobile_hash' => $this->hashMobile($mobileNumber),
            'otp_cache_key' => $cacheKey,
        ];

        if (app()->environment(['local', 'development'])) {
            $logData['otp_value_for_debug'] = $otp;
        }

        Log::info('OTP generated and stored.', $logData);

        return (string) $otp;
    }

    /**
     * تایید OTP
     */
    public function verify(string $mobileNumber, string $enteredOtp, ?int $currentAttempt = null): bool
    {
        $cacheKey = 'otp_' . $mobileNumber;
        $storedOtp = Cache::get($cacheKey);
        $mobileHash = $this->hashMobile($mobileNumber);

        Log::debug('Verifying OTP', [
            'mobile_hash' => $mobileHash,
            'cache_key' => $cacheKey,
            'stored_exists' => Cache::has($cacheKey),
            'stored_otp' => app()->environment(['local', 'development']) ? $storedOtp : 'HIDDEN',
            'entered_otp' => app()->environment(['local', 'development']) ? $enteredOtp : 'HIDDEN',
            'stored_type' => gettype($storedOtp),
            'entered_type' => gettype($enteredOtp),
        ]);

        $failureReason = null;

        if (!$storedOtp) {
            $failureReason = 'OTP expired or not found';
            $this->writeAuditLog([
                'action' => 'otp_verification_failed',
                'description' => 'OTP not found or expired.',
                'mobile_hash' => $mobileHash,
                'attempt_number' => $currentAttempt,
                'failure_reason' => $failureReason,
            ]);
            Log::warning($failureReason, ['mobile_hash' => $mobileHash]);
            return false;
        }

        $isVerified = (string)$storedOtp === (string)$enteredOtp;

        if ($isVerified) {
            Cache::forget($cacheKey);

            $this->writeAuditLog([
                'action' => 'otp_verification_success',
                'description' => 'OTP verified successfully and removed.',
                'mobile_hash' => $mobileHash,
                'attempt_number' => $currentAttempt,
            ]);

            Log::info('OTP verified and removed.', ['mobile_hash' => $mobileHash]);
        } else {
            $failureReason = 'OTP mismatch';
            $this->writeAuditLog([
                'action' => 'otp_verification_failed',
                'description' => 'OTP verification failed: Mismatch.',
                'mobile_hash' => $mobileHash,
                'attempt_number' => $currentAttempt,
                'failure_reason' => $failureReason,
            ]);
            Log::warning($failureReason, [
                'mobile_hash' => $mobileHash,
                'stored_otp' => app()->environment(['local', 'development']) ? $storedOtp : 'HIDDEN',
                'entered_otp' => app()->environment(['local', 'development']) ? $enteredOtp : 'HIDDEN',
            ]);
        }

        return $isVerified;
    }

    /**
     * ثبت لاگ در جدول audit_logs
     */
    protected function writeAuditLog(array $data): void
    {
        $defaultData = [
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'session_id' => Session::getId(),
            'attempt_number' => $data['attempt_number'] ?? null,
            'failure_reason' => $data['failure_reason'] ?? null,
            'request_source' => 'web',
            'geo_location' => json_encode($this->getGeoLocation(Request::ip())),
            'ip_is_blacklisted' => false,
            'device_info' => Request::userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('audit_logs')->insert(array_merge($defaultData, $data));
    }

    /**
     * هش شماره موبایل
     */
    protected function hashMobile(string $mobile): string
    {
        return hash('sha256', $mobile);
    }

    /**
     * موقعیت جغرافیایی (نمونه)
     */
    protected function getGeoLocation(?string $ip): array
    {
        return [
            'ip' => $ip,
            'country' => 'Unknown',
            'city' => 'Unknown',
        ];
    }
}
