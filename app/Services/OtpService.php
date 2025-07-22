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
use Illuminate\Contracts\Encryption\DecryptException;
use App\Exceptions\OtpSendException; // CHANGED: Now importing from App\Exceptions
use App\Http\Controllers\Auth\MobileAuthController; // Added for constants
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
            // Updated IP masking to be more robust
            if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $parts = explode('.', $value);
                return $parts[0] . '***' . substr($value, -strlen($parts[3]));
            } elseif (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                // Simple IPv6 masking, can be more complex if needed
                return substr($value, 0, 4) . '***' . substr($value, -4);
            }
            return '***'; // Fallback for invalid IP
        }
        return '***'; // Fallback
    }
}

class OtpService implements OtpServiceInterface
{
    const OTP_CACHE_PREFIX = 'otp_';
    const OTP_TTL_SECONDS = 120; // 2 minutes
    const PENDING_REGISTRATION_CACHE_PREFIX = 'pending_registration_';
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10; // 10 minutes

    // Dependency Injection for services - NEW: Constructor now accepts dependencies
    protected RateLimitServiceInterface $rateLimitService;
    protected AuditServiceInterface $auditService;

    public function __construct(RateLimitServiceInterface $rateLimitService, AuditServiceInterface $auditService)
    {
        $this->rateLimitService = $rateLimitService;
        $this->auditService = $auditService;
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
        return self::OTP_CACHE_PREFIX . $cleanMobile;
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
        return self::PENDING_REGISTRATION_CACHE_PREFIX . hashForCache($mobileNumber, 'pending_reg_cache_key');
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
        $mobileHashForData = hashForCache($mobileNumber, config('app.key')); // Use app.key for consistency
        $otpData = [
            'otp' => $otp,
            'timestamp' => time(),
            'mobile_hash' => $mobileHashForData
        ];
        Log::debug('OTPService: OTP data before encryption: ' . json_encode($otpData));

        // Encrypt the OTP data before storing it in the cache
        $encryptedOtp = Crypt::encryptString(json_encode($otpData));

        // Log the encrypted OTP string
        Log::debug('OTPService: Encrypted OTP string (first 50 chars): ' . substr($encryptedOtp, 0, 50) . '...');

        $cacheKey = $this->getOtpCacheKey($mobileNumber);
        Log::info('OTPService: Key being used for Cache::put: ' . $cacheKey);
        Log::info('OTPService: Cache driver in use: ' . config('cache.default'));

        try {
            // Store encrypted OTP in cache using the consistent cache key
            $saveResult = Cache::put($cacheKey, $encryptedOtp, now()->addSeconds(self::OTP_TTL_SECONDS)); // Use addSeconds for OTP_TTL_SECONDS
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
     * Verifies the provided OTP against the stored one.
     *
     * @param string $mobileNumber
     * @param string $providedOtp
     * @return bool
     */
    public function verifyOtp(string $mobileNumber, string $providedOtp): bool
    {
        Log::info('OTPService: verifyOtp called. Mobile: ' . $this->maskMobile($mobileNumber) . ', Provided OTP: ' . $providedOtp);

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
            $expectedMobileHash = hashForCache($mobileNumber, config('app.key')); // Use app.key for consistency

            Log::info('OTPService: Stored OTP Data - OTP: ' . $storedOtp . ', Timestamp: ' . $storedTimestamp . ', Stored Mobile Hash: ' . $storedMobileHash);
            Log::info('OTPService: Current Time: ' . time() . ', OTP Expiry Seconds: ' . self::OTP_TTL_SECONDS . ', Expected Mobile Hash: ' . $expectedMobileHash);

            // Verify the mobile hash to ensure the OTP belongs to the correct mobile number
            if ($storedMobileHash !== $expectedMobileHash) {
                Log::warning('OTPService: Mobile number hash mismatch during OTP verification for: ' . $this->maskMobile($mobileNumber) . '. Stored Hash: ' . $storedMobileHash . ', Expected Hash: ' . $expectedMobileHash);
                return false;
            }

            // Check the timestamp for expiry
            if (time() - $storedTimestamp > self::OTP_TTL_SECONDS) { // Use OTP_TTL_SECONDS constant
                Log::warning('OTPService: OTP expired for mobile: ' . $this->maskMobile($mobileNumber) . '. Time difference: ' . (time() - $storedTimestamp) . ' seconds.');
                Cache::forget($cacheKey); // Clear expired OTP
                return false; // OTP has expired
            }

            // Compare the provided OTP with the stored (and decrypted) OTP
            $isOtpMatch = ($storedOtp === $providedOtp);
            Log::info('OTPService: OTP Comparison - Stored: ' . $storedOtp . ', Provided: ' . $providedOtp . ', Match: ' . ($isOtpMatch ? 'true' : 'false'));

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
     * Sends an OTP to the specified mobile number.
     * Handles OTP generation, storage, rate limiting, and SMS sending.
     * This is the unified method for both login and registration OTP sending.
     *
     * @param string $mobileNumber The mobile number to send OTP to.
     * @param string $ipAddress The IP address of the request for rate limiting.
     * @param RateLimitServiceInterface $rateLimitService The rate limit service instance.
     * @param callable $auditLogger A callable function for logging audit events.
     * @param array|null $registrationData Optional array of registration data (name, lastname) for new users.
     * @throws OtpSendException
     */
    public function sendOtpForMobile(
        string $mobileNumber,
        string $ipAddress,
        RateLimitServiceInterface $rateLimitService,
        callable $auditLogger,
        ?array $registrationData = null // NEW: Added registrationData parameter
    ): void {
        Log::debug('OTPService: sendOtpForMobile called for mobile: ' . $this->maskMobile($mobileNumber) . ', IP: ' . $this->maskIp($ipAddress));
        Log::info('OTPService: Starting OTP sending process for mobile: ' . $this->maskMobile($mobileNumber));

        // Apply rate limits using injected service
        $ipRateLimitCheck = $this->rateLimitService->checkAndIncrementIpAttempts($ipAddress);
        Log::debug('OTPService: IP rate limit check result: ' . ($ipRateLimitCheck ? 'Passed' : 'Failed') . '. Current attempts for IP: ' . $this->rateLimitService->getIpAttempts($ipAddress));
        if (!$ipRateLimitCheck) {
            $auditLogger('تعداد تلاش‌های ارسال OTP از این IP بیش از حد مجاز است: ' . $this->maskIp($ipAddress), 'warning');
            throw new OtpSendException('تعداد درخواست‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', null, 429);
        }

        $mobileRateLimitCheck = $this->rateLimitService->checkAndIncrementSendAttempts($mobileNumber);
        Log::debug('OTPService: Mobile send rate limit check result: ' . ($mobileRateLimitCheck ? 'Passed' : 'Failed') . '. Current attempts for mobile: ' . $this->rateLimitService->getSendAttempts($mobileNumber));
        if (!$mobileRateLimitCheck) {
            $auditLogger('تعداد تلاش‌های ارسال OTP برای شماره موبایل بیش از حد مجاز است: ' . $this->maskMobile($mobileNumber), 'warning');
            throw new OtpSendException('تعداد درخواست‌های ارسال کد بیش از حد مجاز است. لطفاً یک دقیقه دیگر تلاش کنید.', null, 429);
        }

        $user = User::where('mobile_number', $mobileNumber)->first();

        // Handle new user registration vs. existing user login
        if (!$user) {
            if ($registrationData) {
                // This is a new registration flow, store pending data from RegisterController
                $pendingRegistrationCacheKey = $this->getPendingRegistrationCacheKey($mobileNumber);
                Cache::put($pendingRegistrationCacheKey, json_encode($registrationData), now()->addMinutes(self::PENDING_REGISTRATION_CACHE_TTL_MINUTES));
                Log::info('OTPService: Stored pending registration data in cache for mobile: ' . $this->maskMobile($mobileNumber) . ' with data: ' . json_encode($registrationData));
                $auditLogger(
                    'otp_send_event_registration',
                    'Mobile number ' . $this->maskMobile($mobileNumber) . ' not found in users table. Pending registration data stored.',
                    null, null, null,
                    [
                        'mobile_number_masked' => $this->maskMobile($mobileNumber),
                        'ip_address_masked' => $this->maskIp($ipAddress),
                        'pending_data' => $registrationData // Log the actual data
                    ]
                );
            } else {
                // This scenario means a new user tried to get OTP without going through registration form first.
                // Or RegisterController didn't pass registrationData.
                Log::warning('OTPService: Mobile number ' . $this->maskMobile($mobileNumber) . ' not found in users table and no registration data provided. This might be an unexpected flow.');
                throw new OtpSendException('این شماره در سیستم ثبت نشده است. لطفاً ابتدا ثبت‌نام کنید.', null, 404);
            }
        } else {
            // User exists, proceed with sending OTP for login
            Log::info('OTPService: Mobile number ' . $this->maskMobile($mobileNumber) . ' found in users table. Assuming existing user login.');
            $auditLogger(
                'otp_send_event_login',
                'OTP for existing user login sent: ' . $this->maskMobile($mobileNumber),
                $user->id, 'User', $user->id,
                [
                    'mobile_number_masked' => $this->maskMobile($mobileNumber),
                    'ip_address_masked' => $this->maskIp($ipAddress)
                ]
            );
        }

        $otp = $this->generateAndStoreOtp($mobileNumber); // OTP is generated and stored
        Log::info('OTPService: OTP generated and stored, value: ' . $otp);

        try {
            Log::info("SIMULATED SMS: To: {$mobileNumber}, Text: کد تایید شما: {$otp}\nفروشگاه چای");

            $auditLogger(
                'otp_send_success',
                'OTP با موفقیت به ' . $this->maskMobile($mobileNumber) . ' ارسال شد.',
                null, null, null,
                [
                    'generated_otp' => $otp,
                    'mobile_number_masked' => $mobileNumber, // Use unmasked for internal logging if needed
                    'ip_address_masked' => $this->maskIp($ipAddress)
                ]
            );

        } catch (\Exception $e) {
            $auditLogger(
                'otp_send_failed_generic',
                'ارسال OTP برای شماره موبایل ' . $this->maskMobile($mobileNumber) . ' ناموفق بود. خطا: ' . $e->getMessage(),
                null, null, null,
                [
                    'generated_otp' => $otp,
                    'mobile_number_masked' => $this->maskMobile($mobileNumber),
                    'exception' => $e->getTraceAsString(),
                    'error' => $e->getMessage()
                ], 'error'
            );
            throw new OtpSendException('خطا در ارسال کد تأیید. لطفاً دوباره تلاش کنید.', $otp, 500, $e);
        }
    }

    /**
     * Verifies the OTP for a mobile number, handling rate limits and user registration/login flow.
     *
     * @param string $mobileNumber The mobile number to verify.
     * @param string $otp The OTP provided by the user.
     * @param string $ipAddress The IP address of the request for rate limiting.
     * @param RateLimitServiceInterface $rateLimitService The rate limit service instance.
     * @param callable $auditLogger A callable function for logging audit events.
     * @return User The authenticated user object.
     * @throws \Exception If OTP verification fails.
     */
    public function verifyOtpForMobile(
        string $mobileNumber,
        string $otp,
        string $ipAddress,
        RateLimitServiceInterface $rateLimitService,
        callable $auditLogger
    ): User {
        Log::debug('OTPService: verifyOtpForMobile called. Mobile: ' . $this->maskMobile($mobileNumber) . ', Provided OTP: ' . $otp . ', IP: ' . $this->maskIp($ipAddress));

        // Apply rate limits using injected service
        $ipVerifyRateLimitCheck = $this->rateLimitService->checkAndIncrementIpVerifyAttempts($ipAddress);
        Log::debug('OTPService: IP verification rate limit check result: ' . ($ipVerifyRateLimitCheck ? 'Passed' : 'Failed') . '. Current attempts for IP: ' . $this->rateLimitService->getIpVerifyAttempts($ipAddress));
        if (!$ipVerifyRateLimitCheck) {
            $auditLogger('تعداد تلاش‌های تأیید از این IP بیش از حد مجاز است: ' . $this->maskIp($ipAddress), 'warning');
            throw new \Exception('تعداد تلاش‌های تأیید از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.', 429);
        }

        $mobileVerifyRateLimitCheck = $this->rateLimitService->checkAndIncrementVerifyAttempts($mobileNumber);
        Log::debug('OTPService: Mobile verification rate limit check result: ' . ($mobileVerifyRateLimitCheck ? 'Passed' : 'Failed') . '. Current attempts for mobile: ' . $this->rateLimitService->getVerifyAttempts($mobileNumber));
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
        $pendingRegistrationCacheKey = $this->getPendingRegistrationCacheKey($mobileNumber);
        Log::debug('OTPService: Attempting to retrieve pending registration data from cache with key: ' . $pendingRegistrationCacheKey);
        $pendingRegistrationData = Cache::get($pendingRegistrationCacheKey);
        Log::debug('OTPService: Pending registration data retrieved: ' . ($pendingRegistrationData ? 'Found' : 'Not Found'));


        if (!$user) {
            // New user registration flow
            if ($pendingRegistrationData) {
                DB::beginTransaction();
                try {
                    $userData = json_decode($pendingRegistrationData, true);

                    // NEW LOGS: Check decoded data
                    Log::debug('OTPService: Decoded pending registration data (raw): ' . $pendingRegistrationData);
                    Log::debug('OTPService: Decoded pending registration data (array): ' . json_encode($userData));

                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($userData)) {
                        Log::error('OTPService: Failed to decode pending registration data for mobile: ' . $this->maskMobile($mobileNumber) . '. JSON Error: ' . json_last_error_msg() . '. Raw data: ' . $pendingRegistrationData);
                        throw new \Exception('خطا در پردازش اطلاعات ثبت‌نام. لطفاً دوباره تلاش کنید.', 500);
                    }
                    
                    // Create new user - REMOVED DEFAULT 'کاربر جدید'
                    $user = User::create([
                        'name' => $userData['name'] ?? null, // Use name from cache, default to null if not present
                        'lastname' => $userData['lastname'] ?? null, // Use lastname from cache, default to null if not present
                        'mobile_number' => $mobileNumber,
                        'email_verified_at' => now(), // Assume mobile verification implies email verification for now
                        'profile_completed' => (!empty($userData['name']) && !empty($userData['lastname'])), // Set based on provided data
                        'status' => 'active',
                    ]);

                    // Assign default role (e.g., 'user')
                    $userRole = Role::where('name', 'user')->first();
                    if ($userRole) {
                        $user->assignRole($userRole);
                        Log::info('OTPService: Assigned "user" role to new user: ' . $user->id);
                        $auditLogger(
                            'role_assigned_on_registration',
                            'Role "user" assigned to new user: ' . $this->maskMobile($user->mobile_number),
                            $user->id, 'User', $user->id,
                            ['role' => 'user']
                        );
                    } else {
                        Log::warning('OTPService: "user" role not found. New user created without a role.');
                        $auditLogger(
                            'role_not_found_on_registration',
                            'Attempted to assign "user" role but role not found for user: ' . $this->maskMobile($user->mobile_number),
                            $user->id, 'User', $user->id,
                            ['missing_role' => 'user'], 'warning'
                        );
                    }

                    DB::commit();
                    Log::info('OTPService: New user registered via OTP with ID: ' . $user->id . ' for mobile: ' . $this->maskMobile($mobileNumber));
                    $auditLogger(
                        'user_registered_via_otp',
                        'New user registered via OTP: ' . $this->maskMobile($mobileNumber),
                        $user->id, 'User', $user->id,
                        ['user_id' => $user->id]
                    );

                } catch (QueryException $e) {
                    DB::rollBack();
                    Log::error('OTPService: Database error during new user registration via OTP for mobile: ' . $this->maskMobile($mobileNumber) . '. Error: ' . $e->getMessage());
                    $auditLogger(
                        'user_registration_failed',
                        'Database error during new user registration for mobile: ' . $this->maskMobile($mobileNumber) . '. Error: ' . $e->getMessage(),
                        null, null, null,
                        ['error' => $e->getMessage(), 'exception' => $e->getTraceAsString()], 'error'
                    );
                    throw new \Exception('خطا در ثبت‌نام کاربر (پایگاه داده). لطفاً دوباره تلاش کنید.', 500);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('OTPService: General error during new user registration via OTP for mobile: ' . $this->maskMobile($mobileNumber) . '. Error: ' . $e->getMessage());
                    $auditLogger(
                        'user_registration_failed',
                        'General error during new user registration for mobile: ' . $this->maskMobile($mobileNumber) . '. Error: ' . $e->getMessage(),
                        null, null, null,
                        ['error' => $e->getMessage(), 'exception' => $e->getTraceAsString()], 'error'
                    );
                    throw new \Exception('خطا در ثبت‌نام کاربر. لطفاً دوباره تلاش کنید.', 500);
                } finally {
                    // Clear pending registration data from cache regardless of success or failure
                    Cache::forget($pendingRegistrationCacheKey);
                    Log::debug('OTPService: Cleared pending registration data from cache for mobile: ' . $this->maskMobile($mobileNumber));
                }
            } else {
                Log::warning('OTPService: OTP valid but no user or pending registration data found for mobile: ' . $this->maskMobile($mobileNumber) . '. This might indicate an issue with session/cache persistence or initial registration flow.');
                $auditLogger('OTP معتبر است اما هیچ کاربر یا اطلاعات ثبت نام در انتظار برای موبایل یافت نشد: ' . $this->maskMobile($mobileNumber), 'warning');
                throw new \Exception('مشکلی در فرآیند ثبت‌نام رخ داد. لطفاً دوباره از ابتدا شروع کنید.', 400);
            }
        } else {
            // Existing user login flow
            Log::info('OTPService: Existing user found with ID: ' . $user->id . ' for mobile: ' . $this->maskMobile($mobileNumber) . '. User will be returned for login.');
            $auditLogger(
                'user_logged_in_via_otp',
                'Existing user logged in via OTP: ' . $this->maskMobile($mobileNumber),
                $user->id, 'User', $user->id,
                ['user_id' => $user->id]
            );
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
