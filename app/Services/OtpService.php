<?php

namespace App\Services;

use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface; // Keep the interface for type hinting in method signatures
use App\Contracts\Services\AuditServiceInterface; // Keep the interface for type hinting in method signatures
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt; // Added for encryption/decryption
use Illuminate\Support\Facades\DB;
use Illuminate\Session\Store as SessionStore; // For type hinting Laravel's session store
use Illuminate\Contracts\Encryption\DecryptException; // Added for handling decryption exceptions

// Make sure the helper file is loaded (e.g., via composer.json "files" autoload)
// require_once app_path('Helpers/SecurityHelper.php'); // Not strictly necessary if autoloaded by composer

class OtpService implements OtpServiceInterface
{
    // Constants for Session & Cache Keys (duplicated from controller for clarity,
    // ideally these would be in a shared config or enum)
    const SESSION_MOBILE_FOR_OTP = 'mobile_number_for_otp';
    const SESSION_MOBILE_FOR_REGISTRATION = 'mobile_number_for_registration';
    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_';
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10;

    // Constants for OTP expiry duration (added as per suggestion)
    const OTP_EXPIRY_MINUTES = 2;
    const OTP_EXPIRY_SECONDS = self::OTP_EXPIRY_MINUTES * 60;

    // We no longer inject RateLimitService and AuditService directly into the constructor
    // of OtpService to reduce its direct dependencies and make it more focused on OTP logic.
    // Instead, they are passed as arguments to the specific methods that need them,
    // or the auditLogger is passed as a callable.

    public function __construct()
    {
        // Constructor is now empty as direct injection of RateLimitService and AuditService is removed.
    }

    /**
     * Generates a hashed key for OTP cache based on mobile number using a helper function.
     * This prevents direct exposure of mobile numbers in cache keys.
     *
     * @param string $mobileNumber
     * @return string
     */
    private function getOtpCacheKey(string $mobileNumber): string
    {
        // Use the global helper function for hashing mobile numbers for cache keys
        return 'otp:' . hashForCache($mobileNumber, 'otp_cache_key');
    }

    /**
     * Generates and stores an OTP for a given mobile number, encrypting it before caching.
     *
     * @param string $mobileNumber
     * @return string The generated OTP.
     */
    public function generateAndStoreOtp(string $mobileNumber): string
    {
        // Generate a 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Prepare OTP data with OTP, a timestamp, and a hash of the mobile number for additional verification
        $otpData = [
            'otp' => $otp,
            'timestamp' => time(), // Optional, for expiry check
            'mobile_hash' => hashForCache($mobileNumber, 'otp_data_hash') // Using helper for mobile_hash within data
        ];

        // Encrypt the OTP data before storing it in the cache
        $encryptedOtp = Crypt::encryptString(json_encode($otpData));

        // Store encrypted OTP in cache using a hashed key
        Cache::put($this->getOtpCacheKey($mobileNumber), $encryptedOtp, now()->addMinutes(self::OTP_EXPIRY_MINUTES));

        return $otp;
    }

    /**
     * Verifies if the provided OTP matches the stored (and encrypted) OTP for a mobile number.
     * It decrypts the stored OTP and checks for validity, optional expiry, and mobile hash integrity.
     *
     * @param string $mobileNumber
     * @param string $otp
     * @return bool
     */
    public function verifyOtp(string $mobileNumber, string $otp): bool
    {
        // Retrieve the encrypted OTP from cache using the hashed key
        $encryptedOtp = Cache::get($this->getOtpCacheKey($mobileNumber));

        // If no encrypted OTP is found, it means it doesn't exist or has expired
        if (!$encryptedOtp) {
            return false;
        }

        try {
            // Decrypt the stored OTP string
            $otpDataJson = Crypt::decryptString($encryptedOtp);
            
            // Decode the JSON string back into an array
            $otpData = json_decode($otpDataJson, true);

            // Validate if JSON decoding was successful and if essential keys exist
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($otpData) || !isset($otpData['otp'], $otpData['timestamp'], $otpData['mobile_hash'])) {
                Log::error('Invalid or corrupted OTP data retrieved for mobile number: ' . maskForLog($mobileNumber, 'phone')); // Using maskForLog
                return false;
            }

            // Verify the mobile hash to ensure the OTP belongs to the correct mobile number
            if ($otpData['mobile_hash'] !== hashForCache($mobileNumber, 'otp_data_hash')) { // Using helper for mobile_hash verification
                Log::warning('Mobile number hash mismatch during OTP verification for: ' . maskForLog($mobileNumber, 'phone')); // Using maskForLog
                return false;
            }

            // Check the timestamp for expiry
            if (time() - $otpData['timestamp'] > self::OTP_EXPIRY_SECONDS) {
                return false; // OTP has expired
            }

            // Compare the provided OTP with the stored (and decrypted) OTP
            return $otpData['otp'] === $otp;

        } catch (DecryptException $e) {
            // Error during decryption, meaning the OTP is invalid or corrupted
            Log::error('OTP decryption failed for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage()); // Using maskForLog
            return false;
        } catch (\Exception $e) {
            // Catch any other general exceptions during JSON decoding or data access
            Log::error('Error processing OTP data for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage()); // Using maskForLog
            return false;
        }
    }

    /**
     * Clears the stored OTP for a given mobile number.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function clearOtp(string $mobileNumber): void
    {
        // Forget the OTP from cache using the hashed key
        Cache::forget($this->getOtpCacheKey($mobileNumber));
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
        // Note: The rateLimitService methods are expected to handle their own key hashing internally
        // using the new helper functions (hashForCache, maskForLog).
        if (!$rateLimitService->checkAndIncrementIpAttempts($ipAddress)) {
            $auditLogger('Too many OTP send attempts from IP: ' . maskForLog($ipAddress, 'ip'), 'warning'); // Using maskForLog
            throw new \Exception('تعداد درخواست‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        if (!$rateLimitService->checkAndIncrementSendAttempts($mobileNumber)) {
            $auditLogger('Too many OTP send attempts for mobile number: ' . maskForLog($mobileNumber, 'phone'), 'warning'); // Using maskForLog
            throw new \Exception('تعداد درخواست‌های ارسال کد بیش از حد مجاز است. لطفاً یک دقیقه دیگر تلاش کنید.', 429);
        }

        // Service's responsibility: Check user existence and manage registration state.
        // IMPORTANT: Use original mobile number for database queries
        $user = User::where('mobile_number', $mobileNumber)->first();
        if (!$user) {
            try {
                // IMPORTANT: Encrypt original mobile number for session
                $encryptedMobileNumber = Crypt::encryptString($mobileNumber);
                $session->put(self::SESSION_MOBILE_FOR_REGISTRATION, $encryptedMobileNumber);
                $auditLogger('OTP sent for new registration: ' . maskForLog($mobileNumber, 'phone'), 'info'); // Using maskForLog
            } catch (\Exception $e) {
                Log::error('Failed to encrypt mobile number for registration session: ' . $e->getMessage());
                throw new \Exception('خطا در آماده‌سازی ثبت‌نام. لطفاً دوباره تلاش کنید.', 500);
            }
        } else {
            $auditLogger('OTP sent for existing user login: ' . maskForLog($mobileNumber, 'phone'), 'info', $user->id, 'User', $user->id); // Using maskForLog
        }

        try {
            // Service's responsibility: Generate and store OTP.
            $otp = $this->generateAndStoreOtp($mobileNumber);

            // In a real application, you would integrate with an SMS service here.
            // IMPORTANT: Send original mobile number to SMS service
            // Example: MelipayamakSmsService::send($mobileNumber, $otp);
            Log::debug("OTP for " . maskForLog($mobileNumber, 'phone') . ": {$otp}"); // Using maskForLog for debug log

            // Service's responsibility: Store encrypted mobile number in session for verification.
            try {
                // IMPORTANT: Encrypt original mobile number for session
                $encryptedMobileNumber = Crypt::encryptString($mobileNumber);
                $session->put(self::SESSION_MOBILE_FOR_OTP, $encryptedMobileNumber);
            } catch (\Exception $e) {
                Log::error('Failed to encrypt mobile number for OTP verification session: ' . $e->getMessage());
                throw new \Exception('خطا در آماده‌سازی تأیید کد. لطفاً دوباره تلاش کنید.', 500);
            }

            $auditLogger('OTP successfully sent to ' . maskForLog($mobileNumber, 'phone'), 'info'); // Using maskForLog

        } catch (\Exception $e) {
            $auditLogger('Failed to send OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(), 'error'); // Using maskForLog
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
            $auditLogger('Too many OTP verification attempts from IP: ' . maskForLog($ipAddress, 'ip'), 'warning'); // Using maskForLog
            throw new \Exception('تعداد تلاش‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        if (!$rateLimitService->checkAndIncrementVerifyAttempts($mobileNumber)) {
            $auditLogger('Too many OTP verification attempts for mobile number: ' . maskForLog($mobileNumber, 'phone'), 'warning'); // Using maskForLog
            throw new \Exception('تعداد تلاش‌های تأیید کد بیش از حد مجاز است. لطفاً ۵ دقیقه دیگر تلاش کنید.', 429);
        }

        // Service's responsibility: Verify OTP.
        if (!$this->verifyOtp($mobileNumber, $otp)) {
            $auditLogger('Invalid OTP provided for mobile number: ' . maskForLog($mobileNumber, 'phone'), 'warning'); // Using maskForLog
            throw new \Exception('کد تأیید نامعتبر است. لطفاً دوباره بررسی کنید.', 401);
        }

        // OTP is valid, clear it and reset rate limits.
        $this->clearOtp($mobileNumber);
        $rateLimitService->resetVerifyAttempts($mobileNumber);
        $rateLimitService->resetIpAttempts($ipAddress);

        // Service's responsibility: Find or create user.
        // IMPORTANT: Use original mobile number for database queries
        $user = User::where('mobile_number', $mobileNumber)->first();

        if (!$user) {
            // User does not exist, check for pending registration data from cache.
            // IMPORTANT: If CACHE_PENDING_REGISTRATION_PREFIX directly uses mobile number,
            // consider hashing it here as well for consistency using hashForCache.
            $encryptedRegistrationData = Cache::get(self::CACHE_PENDING_REGISTRATION_PREFIX . $mobileNumber);
            $registrationData = null;

            if ($encryptedRegistrationData) {
                try {
                    // IMPORTANT: Decrypt original mobile number from cache
                    $registrationData = Crypt::decrypt($encryptedRegistrationData);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    Log::error('Could not decrypt registration data from cache: ' . $e->getMessage());
                    throw new \Exception('خطا در بازیابی اطلاعات ثبت‌نام. لطفاً دوباره از ابتدا شروع کنید.', 500);
                }
            }

            if ($registrationData) {
                // Create user with provided registration data.
                // IMPORTANT: Use original mobile number for database creation
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
                    // IMPORTANT: If CACHE_PENDING_REGISTRATION_PREFIX directly uses mobile number,
                    // consider hashing it here as well for consistency using hashForCache.
                    Cache::forget(self::CACHE_PENDING_REGISTRATION_PREFIX . $mobileNumber);
                    $auditLogger('New user registered via OTP: ' . maskForLog($mobileNumber, 'phone'), 'info', $user->id, 'User', $user->id); // Using maskForLog

                } catch (\Illuminate\Database\QueryException $e) { // Use QueryException for database errors
                    DB::rollBack();
                    Log::error('Database error during new user registration via OTP: ' . $e->getMessage());
                    throw new \Exception('خطا در ثبت‌نام کاربر (پایگاه داده). لطفاً دوباره تلاش کنید.', 500);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('General error during new user registration via OTP: ' . $e->getMessage());
                    throw new \Exception('خطا در ثبت‌نام کاربر. لطفاً دوباره تلاش کنید.', 500);
                }
            } else {
                $auditLogger('OTP valid but no user or pending registration data found for mobile: ' . maskForLog($mobileNumber, 'phone'), 'warning'); // Using maskForLog
                throw new \Exception('مشکلی در فرآیند ثبت‌نام رخ داد. لطفاً دوباره از ابتدا شروع کنید.', 400);
            }
        }

        $auditLogger('OTP successfully verified for ' . maskForLog($mobileNumber, 'phone'), 'info', $user->id, 'User', $user->id); // Using maskForLog
        return $user;
    }
}
