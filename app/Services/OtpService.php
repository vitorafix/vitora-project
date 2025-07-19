<?php

namespace App\Services;

use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface;
use App\Contracts\Services\AuditServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Http\Controllers\Auth\OtpSendException;
use Illuminate\Support\Str; // Added for Str::random()
use Illuminate\Support\Facades\Hash; // Added for Hash::make()
use Spatie\Permission\Models\Role; // Added for Role model

// Make sure SecurityHelper.php is properly autoloaded, e.g., by adding it to composer.json
// or by using a global helper file in Laravel.
// For this example, we assume hashForCache and maskForLog functions are available.
// If hashForCache is not defined globally, you might need to implement it here or remove its usage.
if (!function_exists('hashForCache')) {
    function hashForCache(string $value, string $salt = ''): string
    {
        return hash('sha256', $value . $salt);
    }
}
if (!function_exists('maskForLog')) {
    function maskForLog(string $value, string $type = 'generic'): string
    {
        if ($type === 'phone' && strlen($value) === 11) {
            return substr($value, 0, 4) . '***' . substr($value, -4);
        }
        if ($type === 'ip') {
            return '***.***.***.' . implode('.', array_slice(explode('.', $value), -1));
        }
        return '***'; // Fallback
    }
}

class OtpService implements OtpServiceInterface
{
    // Constants for Session & Cache Keys
    const SESSION_MOBILE_FOR_OTP = 'mobile_number_for_otp';
    const SESSION_MOBILE_FOR_REGISTRATION = 'mobile_number_for_registration';
    // Changed: CACHE_PENDING_REGISTRATION_PREFIX is now just a prefix, the mobile number will be hashed.
    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_';
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10;

    // Constants for OTP expiry duration
    const OTP_EXPIRY_MINUTES = 2;
    const OTP_EXPIRY_SECONDS = self::OTP_EXPIRY_MINUTES * 60;

    public function __construct()
    {
        // Constructor is now empty as direct injection of RateLimitService and AuditService is removed.
    }

    /**
     * Generates a cache key for OTP based on mobile number.
     * This method is crucial for consistent key generation.
     *
     * @param string $mobileNumber
     * @return string
     */
    private function getOtpCacheKey(string $mobileNumber): string
    {
        $cleanMobile = preg_replace('/[^0-9]/', '', $mobileNumber); // Clean the mobile number
        // Based on the bug.txt, the cache key is simply 'otp_' followed by the mobile number.
        // We are removing the hashing logic here to match the actual cache storage.
        return 'otp_' . $cleanMobile;
    }

    /**
     * Generates a hashed key for pending registration cache based on mobile number.
     * This prevents direct exposure of mobile numbers in cache keys for pending registrations.
     *
     * @param string $mobileNumber
     * @return string
     */
    private function getPendingRegistrationCacheKey(string $mobileNumber): string
    {
        // Assumes maskForLog and hashForCache functions are available.
        // If these functions are not available, they should be defined or removed.
        return self::CACHE_PENDING_REGISTRATION_PREFIX . hashForCache($mobileNumber, 'pending_reg_cache_key');
    }

    /**
     * Generates and stores an OTP for a given mobile number, encrypting it before caching.
     *
     * @param string $mobileNumber
     * @return string The generated OTP.
     * @throws \Exception If OTP fails to be stored in cache.
     */
    public function generateAndStoreOtp(string $mobileNumber): string
    {
        Log::info('OTPService: Entering generateAndStoreOtp for mobile: ' . $this->maskMobile($mobileNumber));

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Prepare OTP data with OTP, a timestamp, and a hash of the mobile number for additional verification
        // Assumes hashForCache function is available.
        $mobileHashForData = hashForCache($mobileNumber, 'otp_data_hash');
        $otpData = [
            'otp' => $otp,
            'timestamp' => time(), // Optional, for expiry check
            'mobile_hash' => $mobileHashForData // Using helper for mobile_hash within data
        ];
        Log::debug('OTPService: generateAndStoreOtp - Mobile hash for data: ' . $mobileHashForData);

        // Log OTP data before encryption
        Log::debug('OTPService: OTP data before encryption: ' . json_encode($otpData));

        // Encrypt the OTP data before storing it in the cache
        $encryptedOtp = Crypt::encryptString(json_encode($otpData));

        // Log the encrypted OTP string
        Log::debug('OTPService: Encrypted OTP string (first 50 chars): ' . substr($encryptedOtp, 0, 50) . '...');

        $cacheKey = $this->getOtpCacheKey($mobileNumber);
        // New log: Display the cache key exactly before saving
        Log::info('OTPService: Key being used for Cache::put: ' . $cacheKey);
        Log::info('OTPService: Cache driver in use: ' . config('cache.default'));


        try {
            // Store encrypted OTP in cache using the consistent cache key
            $saveResult = Cache::put($cacheKey, $encryptedOtp, now()->addMinutes(self::OTP_EXPIRY_MINUTES));
            Log::info('OTPService: Cache::put operation completed. Result: ' . ($saveResult ? 'Success' : 'Failure') . ' for key: ' . $cacheKey);

            // Added: Immediate check to confirm if OTP was actually stored in cache
            $retrievedOtpAfterStore = Cache::get($cacheKey);
            Log::info('OTPService: Immediately retrieved from cache after store: ' . ($retrievedOtpAfterStore ? 'Found' : 'Not Found') . ' for key: ' . $cacheKey);

            // If OTP was not found immediately after storing, something is wrong with the cache.
            if (!$retrievedOtpAfterStore) {
                Log::critical('OTPService: Failed to store OTP in cache for mobile: ' . $this->maskMobile($mobileNumber) . '. Cache driver might be misconfigured or unavailable.');
                throw new \Exception('خطا در ذخیره کد تأیید. لطفاً با پشتیبانی تماس بگیرید.', 500);
            }

            // Verify content immediately after saving
            try {
                $verifiedContent = Crypt::decryptString($retrievedOtpAfterStore);
                $verifiedOtpData = json_decode($verifiedContent, true);
                Log::info('OTPService: Immediate verification - Decrypted OTP: ' . ($verifiedOtpData['otp'] ?? 'N/A') . ', Matches original: ' . (($verifiedOtpData['otp'] ?? null) == $otp ? 'true' : 'false'));
            } catch (DecryptException $e) {
                Log::error('OTPService: Immediate verification - Decryption failed: ' . $e->getMessage());
            } catch (\Exception $e) {
                Log::error('OTPService: Immediate verification - Error decoding JSON or other issue: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::critical('OTPService: Exception during Cache::put operation for mobile: ' . $this->maskMobile($mobileNumber) . '. Error: ' . $e->getMessage());
            throw new \Exception('خطا در ذخیره کد تأیید. لطفاً با پشتیبانی تماس بگیرید.', 500);
        }

        Log::debug('OTPService: Generated and stored OTP: ' . $otp . ' for mobile: ' . $this->maskMobile($mobileNumber) . ' with cache key: ' . $cacheKey);
        Log::info('OTPService: Exiting generateAndStoreOtp successfully.');
        return $otp;
    }

    /**
     * Verifies if the provided OTP matches the stored (and encrypted) OTP for a mobile number.
     * It decrypts the stored OTP and checks for validity, optional expiry, and mobile hash integrity.
     *
     * @param string $mobileNumber
     * @param string $otp The OTP provided by the user.
     * @return bool
     */
    public function verifyOtp(string $mobileNumber, string $otp): bool
    {
        Log::info('OTPService: verifyOtp called. Mobile: ' . $this->maskMobile($mobileNumber) . ', Provided OTP: ' . $otp);

        // Retrieve the encrypted OTP from cache using the consistent cache key
        $cacheKey = $this->getOtpCacheKey($mobileNumber);
        Log::info('OTPService: Attempting to retrieve OTP from cache with key: ' . $cacheKey);
        Log::info('OTPService: Cache driver in use: ' . config('cache.default'));

        $encryptedOtp = Cache::get($cacheKey);

        // Log the encrypted string retrieved from cache
        Log::info('OTPService: Retrieved encrypted OTP from cache: ' . ($encryptedOtp ? 'Found' : 'Not Found'));
        if ($encryptedOtp) {
            Log::debug('OTPService: Retrieved encrypted OTP content (first 50 chars): ' . substr($encryptedOtp, 0, 50) . '...');
        }


        // If no encrypted OTP is found, it means it doesn't exist or has expired
        if (!$encryptedOtp) {
            Log::warning('OTPService: No encrypted OTP found in cache for mobile: ' . $this->maskMobile($mobileNumber) . ' using key: ' . $cacheKey);
            return false;
        }

        try {
            // Decrypt the stored OTP string
            $otpDataJson = Crypt::decryptString($encryptedOtp);
            // Log the JSON string after decryption
            Log::debug('OTPService: Decrypted OTP JSON string: ' . $otpDataJson);
            
            // Decode the JSON string back into an array
            $otpData = json_decode($otpDataJson, true);
            // Log the OTP data array after decoding
            Log::debug('OTPService: Decoded OTP data array: ' . json_encode($otpData));

            // Validate if JSON decoding was successful and if essential keys exist
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($otpData) || !isset($otpData['otp'], $otpData['timestamp'], $otpData['mobile_hash'])) {
                Log::error('OTPService: Invalid or corrupted OTP data retrieved for mobile number: ' . $this->maskMobile($mobileNumber) . '. JSON Error: ' . json_last_error_msg());
                return false;
            }

            $storedOtp = $otpData['otp'];
            $storedTimestamp = $otpData['timestamp'];
            $storedMobileHash = $otpData['mobile_hash'];
            // Assumes hashForCache function is available.
            $expectedMobileHash = hashForCache($mobileNumber, 'otp_data_hash');

            Log::info('OTPService: Stored OTP Data - OTP: ' . $storedOtp . ', Timestamp: ' . $storedTimestamp . ', Stored Mobile Hash: ' . $storedMobileHash);
            Log::info('OTPService: Current Time: ' . time() . ', OTP Expiry Seconds: ' . self::OTP_EXPIRY_SECONDS . ', Expected Mobile Hash: ' . $expectedMobileHash);

            // Verify the mobile hash to ensure the OTP belongs to the correct mobile number
            if ($storedMobileHash !== $expectedMobileHash) {
                Log::warning('OTPService: Mobile number hash mismatch during OTP verification for: ' . $this->maskMobile($mobileNumber) . '. Stored Hash: ' . $storedMobileHash . ', Expected Hash: ' . $expectedMobileHash);
                return false;
            }

            // Check the timestamp for expiry
            if (time() - $storedTimestamp > self::OTP_EXPIRY_SECONDS) {
                Log::warning('OTPService: OTP expired for mobile: ' . $this->maskMobile($mobileNumber) . '. Time difference: ' . (time() - $storedTimestamp) . ' seconds.');
                return false; // OTP has expired
            }

            // Compare the provided OTP with the stored (and decrypted) OTP
            $isOtpMatch = ($storedOtp === $otp);
            Log::info('OTPService: OTP Comparison - Stored: ' . $storedOtp . ', Provided: ' . $otp . ', Match: ' . ($isOtpMatch ? 'true' : 'false'));

            if ($isOtpMatch) {
                // OTP is valid, clear it from cache
                $this->clearOtp($mobileNumber);
                Log::info('OTPService: OTP successfully verified and cleared from cache.');
            } else {
                Log::warning('OTPService: Provided OTP does not match stored OTP.');
            }
            return $isOtpMatch;

        } catch (DecryptException $e) {
            Log::error('OTPService: OTP decryption failed for mobile number: ' . $this->maskMobile($mobileNumber) . ' Error: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('OTPService: Error processing OTP data for mobile number: ' . $this->maskMobile($mobileNumber) . ' Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifies the OTP for a mobile number, handling rate limits and user registration/login flow.
     *
     * @param string $mobileNumber The mobile number to verify.
     * @param string $otp The OTP provided by the user.
     * @param string $ipAddress The IP address of the request for rate limiting.
     * @param SessionStore $session The current session instance.
     * @param RateLimitServiceInterface $rateLimitService The rate limit service instance.
     * @param callable $auditLogger A callable function for logging audit events.
     * @return User The authenticated user object.
     * @throws \Exception If OTP verification fails.
     */
    public function verifyOtpForMobile(
        string $mobileNumber,
        string $otp,
        string $ipAddress,
        SessionStore $session,
        RateLimitServiceInterface $rateLimitService,
        callable $auditLogger
    ): User {
        Log::debug('OTPService: verifyOtpForMobile called. Mobile: ' . $this->maskMobile($mobileNumber) . ', Provided OTP: ' . $otp . ', IP: ' . $this->maskIp($ipAddress));

        // Increment and check IP-based verification attempts
        $ipVerifyRateLimitCheck = $rateLimitService->checkAndIncrementIpVerifyAttempts($ipAddress);
        Log::debug('OTPService: IP verification rate limit check result: ' . ($ipVerifyRateLimitCheck ? 'Passed' : 'Failed') . '. Current attempts for IP: ' . $rateLimitService->getIpVerifyAttempts($ipAddress));
        if (!$ipVerifyRateLimitCheck) {
            $auditLogger('تعداد تلاش‌های تأیید از این IP بیش از حد مجاز است: ' . $this->maskIp($ipAddress), 'warning');
            throw new \Exception('تعداد تلاش‌های تأیید از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        // Increment and check mobile-based verification attempts
        $mobileVerifyRateLimitCheck = $rateLimitService->checkAndIncrementVerifyAttempts($mobileNumber);
        Log::debug('OTPService: Mobile verification rate limit check result: ' . ($mobileVerifyRateLimitCheck ? 'Passed' : 'Failed') . '. Current attempts for mobile: ' . $rateLimitService->getVerifyAttempts($mobileNumber));
        if (!$mobileVerifyRateLimitCheck) {
            $auditLogger('تعداد تلاش‌های تأیید کد برای موبایل بیش از حد مجاز است: ' . $this->maskMobile($mobileNumber), 'warning');
            throw new \Exception('تعداد تلاش‌های تأیید کد برای این شماره بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        // Perform the actual OTP verification
        Log::info('OTPService: Calling verifyOtp for actual OTP comparison.');
        if (!$this->verifyOtp($mobileNumber, $otp)) {
            $auditLogger('تایید OTP برای ' . $this->maskMobile($mobileNumber) . ' ناموفق بود. کد OTP نامعتبر است.', 'warning');
            throw new \Exception('کد تأیید نامعتبر است. لطفاً دوباره بررسی کنید.', 400);
        }

        // OTP is valid, proceed with user registration or login
        $user = User::where('mobile_number', $mobileNumber)->first();

        // Check if there's pending registration data in cache (for new users)
        $pendingRegistrationData = Cache::get($this->getPendingRegistrationCacheKey($mobileNumber));

        if (!$user) {
            // New user registration flow
            if ($pendingRegistrationData) {
                DB::beginTransaction();
                try {
                    $userData = json_decode($pendingRegistrationData, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('OTPService: Failed to decode pending registration data for mobile: ' . $this->maskMobile($mobileNumber));
                        throw new \Exception('خطا در پردازش اطلاعات ثبت‌نام. لطفاً دوباره تلاش کنید.', 500);
                    }

                    // Create new user
                    $user = User::create([
                        'name' => $userData['name'] ?? 'کاربر جدید', // Default name if not provided
                        'lastname' => $userData['lastname'] ?? null,
                        'mobile_number' => $mobileNumber,
                        'password' => Hash::make(Str::random(24)), // Generate a random, strong password
                        'email_verified_at' => now(), // Assume mobile verification implies email verification for now
                    ]);

                    // Assign default role (e.g., 'user')
                    // Make sure the 'user' role exists in your database
                    $userRole = Role::where('name', 'user')->first();
                    if ($userRole) {
                        $user->assignRole($userRole);
                        Log::info('OTPService: Assigned "user" role to new user: ' . $user->id);
                    } else {
                        Log::warning('OTPService: "user" role not found. New user created without a role.');
                    }

                    DB::commit();
                    Log::info('OTPService: New user registered via OTP with ID: ' . $user->id . ' for mobile: ' . $this->maskMobile($mobileNumber));
                    $auditLogger('کاربر جدید از طریق OTP برای موبایل ثبت نام شد: ' . $this->maskMobile($mobileNumber), 'info', $user->id, 'User', $user->id);

                    // Clear pending registration data from cache
                    Cache::forget($this->getPendingRegistrationCacheKey($mobileNumber));
                    Log::debug('OTPService: Cleared pending registration data from cache for mobile: ' . $this->maskMobile($mobileNumber));

                } catch (QueryException $e) {
                    DB::rollBack();
                    Log::error('OTPService: Database error during new user registration via OTP for mobile: ' . $this->maskMobile($mobileNumber) . '. Error: ' . $e->getMessage());
                    throw new \Exception('خطا در ثبت‌نام کاربر (پایگاه داده). لطفاً دوباره تلاش کنید.', 500);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('OTPService: General error during new user registration via OTP for mobile: ' . $this->maskMobile($mobileNumber) . '. Error: ' . $e->getMessage());
                    throw new \Exception('خطا در ثبت‌نام کاربر. لطفاً دوباره تلاش کنید.', 500);
                }
            } else {
                Log::warning('OTPService: OTP valid but no user or pending registration data found for mobile: ' . $this->maskMobile($mobileNumber) . '. This might indicate an issue with session/cache persistence or initial registration flow.');
                $auditLogger('OTP معتبر است اما هیچ کاربر یا اطلاعات ثبت نام در انتظار برای موبایل یافت نشد: ' . $this->maskMobile($mobileNumber), 'warning');
                throw new \Exception('مشکلی در فرآیند ثبت‌نام رخ داد. لطفاً دوباره از ابتدا شروع کنید.', 400);
            }
        } else {
            Log::info('OTPService: Existing user found with ID: ' . $user->id . ' for mobile: ' . $this->maskMobile($mobileNumber) . '. User will be returned for login.');
        }

        $auditLogger('OTP با موفقیت برای ' . $this->maskMobile($mobileNumber) . ' تأیید شد.', 'info', $user->id, 'User', $user->id);
        return $user;
    }

    /**
     * Clears the stored OTP for a given mobile number.
     *
     * @param string $mobileNumber
     * @return void
     */
    public function clearOtp(string $mobileNumber): void
    {
        // Forget the OTP from cache using the consistent cache key
        $cacheKey = $this->getOtpCacheKey($mobileNumber);
        Cache::forget($cacheKey);
        Log::info('OTPService: Cleared OTP from cache for mobile: ' . $this->maskMobile($mobileNumber) . ' with key: ' . $cacheKey);
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
        RateLimitServiceInterface $rateLimitService,
        callable $auditLogger
    ): void {
        Log::debug('OTPService: sendOtpForMobile called for mobile: ' . $this->maskMobile($mobileNumber) . ', IP: ' . $this->maskIp($ipAddress));
        Log::info('OTPService: Starting OTP sending process for mobile: ' . $this->maskMobile($mobileNumber)); // New log at the beginning of the function

        // Service's responsibility: Apply rate limits.
        $ipRateLimitCheck = $rateLimitService->checkAndIncrementIpAttempts($ipAddress);
        // The following log is now active because the getIpAttempts method exists in RateLimitService.
        Log::debug('OTPService: IP rate limit check result: ' . ($ipRateLimitCheck ? 'Passed' : 'Failed') . '. Current attempts for IP: ' . $rateLimitService->getIpAttempts($ipAddress));
        if (!$ipRateLimitCheck) {
            $auditLogger('تعداد تلاش‌های ارسال OTP از این IP بیش از حد مجاز است: ' . $this->maskIp($ipAddress), 'warning');
            throw new \Exception('تعداد درخواست‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        $mobileRateLimitCheck = $rateLimitService->checkAndIncrementSendAttempts($mobileNumber);
        // The following log is now active because the getSendAttempts method exists in RateLimitService.
        Log::debug('OTPService: Mobile send rate limit check result: ' . ($mobileRateLimitCheck ? 'Passed' : 'Failed') . '. Current attempts for mobile: ' . $rateLimitService->getSendAttempts($mobileNumber));
        if (!$mobileRateLimitCheck) {
            $auditLogger('تعداد تلاش‌های ارسال OTP برای شماره موبایل بیش از حد مجاز است: ' . $this->maskMobile($mobileNumber), 'warning');
            throw new \Exception('تعداد درخواست‌های ارسال کد بیش از حد مجاز است. لطفاً یک دقیقه دیگر تلاش کنید.', 429);
        }

        // Service's responsibility: Check user existence and manage registration state.
        $user = User::where('mobile_number', $mobileNumber)->first();
        if (!$user) {
            Log::info('OTPService: Mobile number ' . $this->maskMobile($mobileNumber) . ' not found in users table. Assuming new registration.');
            try {
                $encryptedMobileNumber = Crypt::encryptString($mobileNumber);
                $session->put(self::SESSION_MOBILE_FOR_REGISTRATION, $encryptedMobileNumber);
                Log::debug('OTPService: Mobile number encrypted and stored in session for registration: ' . $this->maskMobile($mobileNumber));
                $auditLogger('OTP برای ثبت نام جدید ارسال شد: ' . $this->maskMobile($mobileNumber), 'info');
            } catch (\Exception $e) {
                Log::error('Failed to encrypt mobile number for registration session: ' . $e->getMessage());
                throw new \Exception('خطا در آماده‌سازی ثبت‌نام. لطفاً دوباره تلاش کنید.', 500);
            }
        } else {
            Log::info('OTPService: Mobile number ' . $this->maskMobile($mobileNumber) . ' found in users table. Assuming existing user login.');
            $auditLogger('OTP برای ورود کاربر موجود ارسال شد: ' . $this->maskMobile($mobileNumber), 'info', $user->id, 'User', $user->id);
        }

        // --- Critical Section: OTP Generation and Storage ---
        // This is where generateAndStoreOtp is called.
        $otp = $this->generateAndStoreOtp($mobileNumber); // OTP is generated and stored
        Log::info('OTPService: OTP generated and stored, value: ' . $otp); // Log to confirm OTP reception
        // --- End Critical Section ---

        try {
            Log::info("SIMULATED SMS: To: {$mobileNumber}, Text: کد تایید شما: {$otp}\nفروشگاه چای");

            try {
                $encryptedMobileNumber = Crypt::encryptString($mobileNumber);
                $session->put(self::SESSION_MOBILE_FOR_OTP, $encryptedMobileNumber);
                Log::debug('OTPService: Mobile number encrypted and stored in session for OTP verification: ' . $this->maskMobile($mobileNumber));
            } catch (\Exception $e) {
                Log::error('Failed to encrypt mobile number for OTP verification session: ' . $e->getMessage());
                throw new \Exception('خطا در آماده‌سازی تأیید کد. لطفاً دوباره تلاش کنید.', 500);
            }

            // Assumes maskForLog function is available.
            $auditLogger(
                'otp_send_event',
                'OTP با موفقیت به ' . $this->maskMobile($mobileNumber) . ' ارسال شد.',
                null,
                null,
                null,
                [
                    'generated_otp' => $otp,
                    'mobile_number_masked' => $this->maskMobile($mobileNumber)
                ]
            );

        } catch (\Exception $e) {
            // Assumes maskForLog function is available.
            $auditLogger(
                'otp_send_failed_exception',
                'ارسال OTP برای شماره موبایل ' . $this->maskMobile($mobileNumber) . ' ناموفق بود. خطا: ' . $e->getMessage(),
                null,
                null,
                null,
                [
                    'generated_otp' => $otp,
                    'mobile_number_masked' => $this->maskMobile($mobileNumber)
                ]
            );
            throw new OtpSendException('خطا در ارسال کد تأیید. لطفاً دوباره تلاش کنید.', $otp, 500, $e);
        }
    }

    /**
     * Masks a mobile number for logging purposes.
     * @param string $mobileNumber
     * @return string
     */
    private function maskMobile(string $mobileNumber): string
    {
        if (function_exists('maskForLog')) {
            return maskForLog($mobileNumber, 'phone');
        }
        // Fallback if maskForLog helper is not available
        return substr($mobileNumber, 0, 4) . '***' . substr($mobileNumber, -4);
    }

    /**
     * Masks an IP address for logging purposes.
     * @param string $ipAddress
     * @return string
     */
    private function maskIp(string $ipAddress): string
    {
        if (function_exists('maskForLog')) {
            return maskForLog($ipAddress, 'ip');
        }
        // Fallback if maskForLog helper is not available
        return '***.***.***.' . implode('.', array_slice(explode('.', $ipAddress), -1));
    }
}
