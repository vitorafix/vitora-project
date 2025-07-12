<?php

namespace App\Services;

use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface; // Keep the interface for type hinting in method signatures
use App\Contracts\Services\AuditServiceInterface; // Keep the interface for type hinting in method signatures
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Session\Store as SessionStore; // For type hinting Laravel's session store

class OtpService implements OtpServiceInterface
{
    // Constants for Session & Cache Keys (duplicated from controller for clarity,
    // ideally these would be in a shared config or enum)
    const SESSION_MOBILE_FOR_OTP = 'mobile_number_for_otp';
    const SESSION_MOBILE_FOR_REGISTRATION = 'mobile_number_for_registration';
    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_';
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10;

    // We no longer inject RateLimitService and AuditService directly into the constructor
    // of OtpService to reduce its direct dependencies and make it more focused on OTP logic.
    // Instead, they are passed as arguments to the specific methods that need them,
    // or the auditLogger is passed as a callable.

    public function __construct()
    {
        // Constructor is now empty as direct injection of RateLimitService and AuditService is removed.
    }

    /**
     * Generates and stores an OTP for a given mobile number.
     *
     * @param string $mobileNumber
     * @return string The generated OTP.
     */
    public function generateAndStoreOtp(string $mobileNumber): string
    {
        // Generate a 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for a limited time (e.g., 2 minutes)
        Cache::put('otp:' . $mobileNumber, $otp, now()->addMinutes(2));

        return $otp;
    }

    /**
     * Verifies if the provided OTP matches the stored OTP for a mobile number.
     *
     * @param string $mobileNumber
     * @param string $otp
     * @return bool
     */
    public function verifyOtp(string $mobileNumber, string $otp): bool
    {
        $storedOtp = Cache::get('otp:' . $mobileNumber);
        return $storedOtp === $otp;
    }

    /**
     * Clears the stored OTP for a given mobile number.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function clearOtp(string $mobileNumber): void
    {
        Cache::forget('otp:' . $mobileNumber);
    }

    /**
     * Sends an OTP to the given mobile number.
     * Handles OTP generation, storage, rate limiting, and SMS sending.
     *
     * @param string $mobileNumber The mobile number to send OTP to.
     * @param string $ipAddress The IP address of the request for rate limiting.
     * @param SessionStore $session The current session instance.
     * @param RateLimitServiceInterface $rateLimitService The rate limit service instance.
     * @param callable $auditLogger A callable function for logging audit events.
     * @throws \Exception If OTP sending fails due to rate limits, internal errors, etc.
     */
    public function sendOtpForMobile(
        string $mobileNumber,
        string $ipAddress,
        SessionStore $session,
        RateLimitServiceInterface $rateLimitService, // Injected here
        callable $auditLogger
    ): void {
        // Service's responsibility: Apply rate limits.
        if (!$rateLimitService->checkAndIncrementIpAttempts($ipAddress)) {
            $auditLogger('Too many OTP send attempts from IP: ' . $ipAddress, 'warning');
            throw new \Exception('تعداد درخواست‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        if (!$rateLimitService->checkAndIncrementSendAttempts($mobileNumber)) {
            $auditLogger('Too many OTP send attempts for mobile number: ' . $mobileNumber, 'warning');
            throw new \Exception('تعداد درخواست‌های ارسال کد بیش از حد مجاز است. لطفاً یک دقیقه دیگر تلاش کنید.', 429);
        }

        // Service's responsibility: Check user existence and manage registration state.
        $user = User::where('mobile_number', $mobileNumber)->first();
        if (!$user) {
            try {
                $encryptedMobileNumber = Crypt::encryptString($mobileNumber);
                $session->put(self::SESSION_MOBILE_FOR_REGISTRATION, $encryptedMobileNumber);
                $auditLogger('OTP sent for new registration: ' . $mobileNumber, 'info');
            } catch (\Exception $e) {
                Log::error('Failed to encrypt mobile number for registration session: ' . $e->getMessage());
                throw new \Exception('خطا در آماده‌سازی ثبت‌نام. لطفاً دوباره تلاش کنید.', 500);
            }
        } else {
            $auditLogger('OTP sent for existing user login: ' . $mobileNumber, 'info', $user->id, 'User', $user->id);
        }

        try {
            // Service's responsibility: Generate and store OTP.
            $otp = $this->generateAndStoreOtp($mobileNumber);

            // In a real application, you would integrate with an SMS service here.
            // Example: MelipayamakSmsService::send($mobileNumber, $otp);
            Log::debug("OTP for {$mobileNumber}: {$otp}"); // Use debug level for production

            // Service's responsibility: Store encrypted mobile number in session for verification.
            try {
                $encryptedMobileNumber = Crypt::encryptString($mobileNumber);
                $session->put(self::SESSION_MOBILE_FOR_OTP, $encryptedMobileNumber);
            } catch (\Exception $e) {
                Log::error('Failed to encrypt mobile number for OTP verification session: ' . $e->getMessage());
                throw new \Exception('خطا در آماده‌سازی تأیید کد. لطفاً دوباره تلاش کنید.', 500);
            }

            $auditLogger('OTP successfully sent to ' . $mobileNumber, 'info');

        } catch (\Exception $e) {
            $auditLogger('Failed to send OTP for mobile number: ' . $mobileNumber . ' Error: ' . $e->getMessage(), 'error');
            throw new \Exception('خطا در ارسال کد تأیید. لطفاً دوباره تلاش کنید.', 500);
        }
    }

    /**
     * Verifies the provided OTP for a given mobile number.
     * Handles OTP validation, clearing, rate limit resets, and user lookup/creation.
     *
     * @param string $mobileNumber The mobile number for verification.
     * @param string $otp The OTP provided by the user.
     * @param string $ipAddress The IP address of the request for rate limiting.
     * @param SessionStore $session The current session instance.
     * @param RateLimitServiceInterface $rateLimitService The rate limit service instance.
     * @param callable $auditLogger A callable function for logging audit events.
     * @return \App\Models\User The authenticated user model.
     * @throws \Exception If OTP verification fails (invalid OTP, rate limit, etc.).
     */
    public function verifyOtpForMobile(
        string $mobileNumber,
        string $otp,
        string $ipAddress,
        SessionStore $session,
        RateLimitServiceInterface $rateLimitService, // Injected here
        callable $auditLogger
    ): User {
        // Service's responsibility: Apply rate limits.
        if (!$rateLimitService->checkAndIncrementIpAttempts($ipAddress)) {
            $auditLogger('Too many OTP verification attempts from IP: ' . $ipAddress, 'warning');
            throw new \Exception('تعداد تلاش‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        if (!$rateLimitService->checkAndIncrementVerifyAttempts($mobileNumber)) {
            $auditLogger('Too many OTP verification attempts for mobile number: ' . $mobileNumber, 'warning');
            throw new \Exception('تعداد تلاش‌های تأیید کد بیش از حد مجاز است. لطفاً ۵ دقیقه دیگر تلاش کنید.', 429);
        }

        // Service's responsibility: Verify OTP.
        if (!$this->verifyOtp($mobileNumber, $otp)) {
            $auditLogger('Invalid OTP provided for mobile number: ' . $mobileNumber, 'warning');
            throw new \Exception('کد تأیید نامعتبر است. لطفاً دوباره بررسی کنید.', 401);
        }

        // OTP is valid, clear it and reset rate limits.
        $this->clearOtp($mobileNumber);
        $rateLimitService->resetVerifyAttempts($mobileNumber);
        $rateLimitService->resetIpAttempts($ipAddress);

        // Service's responsibility: Find or create user.
        $user = User::where('mobile_number', $mobileNumber)->first();

        if (!$user) {
            // User does not exist, check for pending registration data from cache.
            $encryptedRegistrationData = Cache::get(self::CACHE_PENDING_REGISTRATION_PREFIX . $mobileNumber);
            $registrationData = null;

            if ($encryptedRegistrationData) {
                try {
                    $registrationData = Crypt::decrypt($encryptedRegistrationData);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    Log::error('Could not decrypt registration data from cache: ' . $e->getMessage());
                    throw new \Exception('خطا در بازیابی اطلاعات ثبت‌نام. لطفاً دوباره از ابتدا شروع کنید.', 500);
                }
            }

            if ($registrationData) {
                // Create user with provided registration data.
                try {
                    DB::beginTransaction();
                    $user = User::create([
                        'name' => $registrationData['name'],
                        'lastname' => $registrationData['lastname'],
                        'mobile_number' => $registrationData['mobile_number'],
                        'profile_completed' => false,
                        'status' => 'active',
                    ]);

                    // Assign a default role, e.g., 'user' (assuming Spatie/laravel-permission)
                    // $user->assignRole('user');

                    DB::commit();
                    Cache::forget(self::CACHE_PENDING_REGISTRATION_PREFIX . $mobileNumber);
                    $auditLogger('New user registered via OTP: ' . $mobileNumber, 'info', $user->id, 'User', $user->id);

                } catch (QueryException $e) {
                    DB::rollBack();
                    Log::error('Database error during new user registration via OTP: ' . $e->getMessage());
                    throw new \Exception('خطا در ثبت‌نام کاربر (پایگاه داده). لطفاً دوباره تلاش کنید.', 500);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('General error during new user registration via OTP: ' . $e->getMessage());
                    throw new \Exception('خطا در ثبت‌نام کاربر. لطفاً دوباره تلاش کنید.', 500);
                }
            } else {
                $auditLogger('OTP valid but no user or pending registration data found for mobile: ' . $mobileNumber, 'warning');
                throw new \Exception('مشکلی در فرآیند ثبت‌نام رخ داد. لطفاً دوباره از ابتدا شروع کنید.', 400);
            }
        }

        $auditLogger('OTP successfully verified for ' . $mobileNumber, 'info', $user->id, 'User', $user->id);
        return $user;
    }
}
