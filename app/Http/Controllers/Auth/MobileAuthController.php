<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart; // اگر در اینجا استفاده نمی‌شود، می‌توان حذف کرد
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator; // Still used for internal helper validation
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Services\MelipayamakSmsService; // اگر مستقیماً استفاده نمی‌شود، می‌توان حذف کرد
use Illuminate\View\View;
use Illuminate\Support\Facades\Session;
use App\Services\CartService; // اگر در اینجا استفاده نمی‌شود، می‌توان حذف کرد
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

// Import new service classes and interfaces
use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface;
use App\Contracts\Services\AuditServiceInterface;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;

class MobileAuthController extends Controller
{
    // Constants for Rate Limiting
    // این مقادیر از config/auth.php خوانده می‌شوند، اما برای وضوح در اینجا نیز تعریف شده‌اند.
    // تغییرات اصلی باید در config/auth.php یا فایل .env انجام شود.
    const OTP_SEND_RATE_LIMIT = 5; // پیش‌فرض config/auth.php
    const OTP_SEND_COOLDOWN_MINUTES = 1; // پیش‌فرض config/auth.php
    const OTP_VERIFY_RATE_LIMIT = 10; // پیش‌فرض config/auth.php
    const OTP_VERIFY_COOLDOWN_MINUTES = 5; // پیش‌فرض config/auth.php
    const OTP_IP_MAX_ATTEMPTS = 10; // پیش‌فرض config/auth.php
    const OTP_IP_COOLDOWN_MINUTES = 60; // پیش‌فرض config/auth.php

    // Constants for Session & Cache Keys
    const SESSION_MOBILE_FOR_OTP = 'mobile_number_for_otp';
    const SESSION_MOBILE_FOR_REGISTRATION = 'mobile_number_for_registration';
    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_';
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10; // TTL for pending registration data

    // Dependency Injection for services
    protected $cartService;
    protected $otpService;
    protected $rateLimitService;
    protected $auditService;

    public function __construct(
        CartService $cartService,
        OtpServiceInterface $otpService,
        RateLimitServiceInterface $rateLimitService,
        AuditServiceInterface $auditService
    ) {
        $this->cartService = $cartService;
        $this->otpService = $otpService;
        $this->rateLimitService = $rateLimitService;
        $this->auditService = $auditService;
    }

    /**
     * Show the mobile login form.
     *
     * @return View
     */
    public function showMobileLoginForm(): View
    {
        return view('auth.login'); // Assuming 'auth.login' is your mobile login form
    }

    /**
     * Show the OTP verification form.
     * This method is called after sending OTP or when redirected to verify.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function showOtpVerifyForm(Request $request): View|RedirectResponse
    {
        // Retrieve encrypted mobile number from session
        $encryptedMobileNumber = $request->session()->get(self::SESSION_MOBILE_FOR_OTP);
        $mobileNumber = null;

        if (!$encryptedMobileNumber) {
            // If mobile number is not in session, redirect back to login
            return redirect()->route('auth.mobile-login-form')->withErrors(['mobile_number' => 'شماره موبایل برای تأیید کد یافت نشد. لطفاً دوباره وارد شوید.']);
        }

        try {
            // Attempt to decrypt the mobile number
            $mobileNumber = Crypt::decryptString($encryptedMobileNumber);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // If decryption fails (e.g., wrong key or corrupted data), log and redirect
            Log::error('Could not decrypt mobile number from session: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return redirect()->route('auth.mobile-login-form')->with('error', 'خطا در بازیابی شماره موبایل. لطفاً دوباره تلاش کنید.');
        }

        // You might also want to pass attemptCount if you are tracking it in the session
        $attemptCount = $request->session()->get('otp_attempt_count', 0);

        return view('auth.verify-otp', compact('mobileNumber', 'attemptCount'));
    }


    /**
     * Send OTP to the provided mobile number.
     *
     * @param SendOtpRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function sendOtp(SendOtpRequest $request)
    {
        $mobileNumber = $this->normalizeMobileNumber($request->mobile_number);
        $ipAddress = $request->ip();

        // Rate limit check for IP address
        // این متد هم تعداد تلاش‌ها را بررسی می‌کند و هم آن را افزایش می‌دهد.
        if (!$this->rateLimitService->checkAndIncrementIpAttempts($ipAddress)) {
            $this->auditService->log(
                'otp_send_failed_ip_rate_limit',
                'Too many OTP send attempts from IP: ' . $ipAddress,
                $request,
                ['ip_address' => $ipAddress, 'mobile_number' => $mobileNumber],
                hash('sha256', $mobileNumber),
                null, null, null, 'warning'
            );
            return $this->respondWithError(
                'تعداد درخواست‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.',
                429,
                $request,
                'general',
                null,
                false
            );
        }

        // Rate limit check for mobile number
        // این متد هم تعداد تلاش‌ها را بررسی می‌کند و هم آن را افزایش می‌دهد.
        if (!$this->rateLimitService->checkAndIncrementSendAttempts($mobileNumber)) {
            $this->auditService->log(
                'otp_send_failed_mobile_rate_limit',
                'Too many OTP send attempts for mobile number: ' . $mobileNumber,
                $request,
                ['mobile_number' => $mobileNumber],
                hash('sha256', $mobileNumber),
                null, null, null, 'warning'
            );
            return $this->respondWithError(
                'تعداد درخواست‌های ارسال کد بیش از حد مجاز است. لطفاً یک دقیقه دیگر تلاش کنید.',
                429,
                $request,
                'mobile_number',
                null,
                false
            );
        }

        // Check if user exists
        $user = User::where('mobile_number', $mobileNumber)->first();
        if (!$user) {
            // If user doesn't exist, store mobile number in session for registration
            try {
                $encryptedMobileNumber = Crypt::encryptString($mobileNumber);
                $request->session()->put(self::SESSION_MOBILE_FOR_REGISTRATION, $encryptedMobileNumber);
                $this->auditService->log(
                    'otp_send_for_new_registration',
                    'OTP sent for new registration: ' . $mobileNumber,
                    $request,
                    ['mobile_number' => $mobileNumber],
                    hash('sha256', $mobileNumber),
                    null, 'User', null, 'info'
                );
            } catch (\Exception $e) {
                Log::error('Failed to encrypt mobile number for registration session: ' . $e->getMessage());
                return $this->respondWithError(
                    'خطا در آماده‌سازی ثبت‌نام. لطفاً دوباره تلاش کنید.',
                    500,
                    $request,
                    'mobile_number'
                );
            }
        } else {
            $this->auditService->log(
                'otp_send_for_login',
                'OTP sent for existing user login: ' . $mobileNumber,
                $request,
                ['mobile_number' => $mobileNumber],
                hash('sha256', $mobileNumber),
                $user->id, 'User', $user->id, 'info'
            );
        }

        try {
            $otp = $this->otpService->generateAndStoreOtp($mobileNumber);

            // In a real application, you would send the OTP via SMS here.
            // For example: MelipayamakSmsService::send($mobileNumber, $otp);
            // برای محیط Production، این لاگ را حذف یا به Log::debug تغییر دهید.
            Log::debug("OTP for {$mobileNumber}: {$otp}"); // تغییر به debug برای امنیت بیشتر

            // Store mobile number in session for OTP verification form
            try {
                $encryptedMobileNumber = Crypt::encryptString($mobileNumber);
                $request->session()->put(self::SESSION_MOBILE_FOR_OTP, $encryptedMobileNumber);
            } catch (\Exception $e) {
                Log::error('Failed to encrypt mobile number for OTP verification session: ' . $e->getMessage());
                return $this->respondWithError(
                    'خطا در آماده‌سازی تأیید کد. لطفاً دوباره تلاش کنید.',
                    500,
                    $request,
                    'mobile_number'
                );
            }

            return redirect()->route('auth.verify-otp-form')->with('status', 'کد تأیید با موفقیت ارسال شد.');

        } catch (\Exception $e) {
            $this->auditService->log(
                'otp_send_failed_exception',
                'Failed to send OTP for mobile number: ' . $mobileNumber . ' Error: ' . $e->getMessage(),
                $request,
                ['mobile_number' => $mobileNumber, 'error' => $e->getMessage()],
                hash('sha256', $mobileNumber),
                $user->id ?? null,
                null, null, 'error'
            );
            return $this->respondWithError(
                'خطا در ارسال کد تأیید. لطفاً دوباره تلاش کنید.',
                500,
                $request,
                'mobile_number'
            );
        }
    }

    /**
     * Verify OTP and log in the user or redirect to registration.
     *
     * @param VerifyOtpRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $mobileNumber = $this->normalizeMobileNumber($request->mobile_number);
        $otp = $this->normalizeOtp($request->otp);
        $ipAddress = $request->ip();

        // Rate limit check for IP address
        // این متد هم تعداد تلاش‌ها را بررسی می‌کند و هم آن را افزایش می‌دهد.
        if (!$this->rateLimitService->checkAndIncrementIpAttempts($ipAddress)) {
            $this->auditService->log(
                'otp_verify_failed_ip_rate_limit',
                'Too many OTP verification attempts from IP: ' . $ipAddress,
                $request,
                ['ip_address' => $ipAddress, 'mobile_number' => $mobileNumber],
                hash('sha256', $mobileNumber),
                null, null, null, 'warning'
            );
            return $this->respondWithError(
                'تعداد تلاش‌ها از این IP بیش از حد مجاز است. لطفاً بعداً تلاش کنید.',
                429,
                $request,
                'general'
            );
        }

        // Rate limit check for OTP verification
        // این متد هم تعداد تلاش‌ها را بررسی می‌کند و هم آن را افزایش می‌دهد.
        if (!$this->rateLimitService->checkAndIncrementVerifyAttempts($mobileNumber)) {
            $this->auditService->log(
                'otp_verify_failed_mobile_rate_limit',
                'Too many OTP verification attempts for mobile number: ' . $mobileNumber,
                $request,
                ['mobile_number' => $mobileNumber],
                hash('sha256', $mobileNumber),
                null, null, null, 'warning'
            );
            return $this->respondWithError(
                'تعداد تلاش‌های تأیید کد بیش از حد مجاز است. لطفاً ۵ دقیقه دیگر تلاش کنید.',
                429,
                $request,
                'otp'
            );
        }

        if (!$this->otpService->verifyOtp($mobileNumber, $otp)) {
            // اگر تأیید ناموفق باشد، checkAndIncrementVerifyAttempts قبلاً تعداد تلاش را افزایش داده است.
            $this->auditService->log(
                'otp_verify_failed_invalid',
                'Invalid OTP provided for mobile number: ' . $mobileNumber,
                $request,
                ['mobile_number' => $mobileNumber, 'otp' => $otp],
                hash('sha256', $mobileNumber),
                null, null, null, 'warning'
            );
            return $this->respondWithError(
                'کد تأیید نامعتبر است. لطفاً دوباره بررسی کنید.',
                401,
                $request,
                'otp'
            );
        }

        // OTP is valid, clear it and reset rate limits
        $this->otpService->clearOtp($mobileNumber);
        $this->rateLimitService->resetVerifyAttempts($mobileNumber); // Reset verify attempts on success
        $this->rateLimitService->resetIpAttempts($request->ip()); // Reset IP attempts on success

        // Find or create user
        $user = User::where('mobile_number', $mobileNumber)->first();

        if (!$user) {
            // User does not exist, check for pending registration data
            $encryptedRegistrationData = Cache::get(self::CACHE_PENDING_REGISTRATION_PREFIX . $mobileNumber);
            $registrationData = null;
            if ($encryptedRegistrationData) {
                try {
                    $registrationData = Crypt::decrypt($encryptedRegistrationData);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    Log::error('Could not decrypt registration data from cache: ' . $e->getMessage());
                    return $this->respondWithError(
                        'خطا در بازیابی اطلاعات ثبت‌نام. لطفاً دوباره از ابتدا شروع کنید.',
                        500,
                        $request,
                        'general',
                        'auth.mobile-login-form'
                    );
                } catch (\Exception $e) { // Catch any other cache related errors
                    Log::error('Error retrieving registration data from cache: ' . $e->getMessage());
                    return $this->respondWithError(
                        'خطا در بازیابی اطلاعات ثبت‌نام از کش. لطفاً دوباره از ابتدا شروع کنید.',
                        500,
                        $request,
                        'general',
                        'auth.mobile-login-form'
                    );
                }
            }

            if ($registrationData) {
                // Create user with provided registration data
                try {
                    DB::beginTransaction();
                    $user = User::create([
                        'name' => $registrationData['name'],
                        'lastname' => $registrationData['lastname'],
                        'mobile_number' => $registrationData['mobile_number'],
                        'profile_completed' => false,
                        'status' => 'active',
                    ]);

                    // Assign a default role, e.g., 'user'
                    $user->assignRole('user');

                    DB::commit();

                    Cache::forget(self::CACHE_PENDING_REGISTRATION_PREFIX . $mobileNumber);

                    $this->auditService->log(
                        'user_registered_via_otp',
                        'New user registered and logged in via OTP: ' . $mobileNumber,
                        $request,
                        ['user_id' => $user->id, 'mobile_number' => $mobileNumber],
                        hash('sha256', $mobileNumber),
                        $user->id, 'User', $user->id, 'info'
                    );

                } catch (QueryException $e) {
                    DB::rollBack();
                    $this->auditService->log(
                        'user_registration_failed_db_query',
                        'Database error during new user registration via OTP: ' . $mobileNumber . ' Error: ' . $e->getMessage(),
                        $request,
                        ['mobile_number' => $mobileNumber, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                        hash('sha256', $mobileNumber),
                        null, null, null, 'critical'
                    );
                    return $this->respondWithError(
                        'خطا در ثبت‌نام کاربر (پایگاه داده). لطفاً دوباره تلاش کنید.',
                        500,
                        $request,
                        'general'
                    );
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->auditService->log(
                        'user_registration_failed_general',
                        'General error during new user registration via OTP: ' . $mobileNumber . ' Error: ' . $e->getMessage(),
                        $request,
                        ['mobile_number' => $mobileNumber, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                        hash('sha256', $mobileNumber),
                        null, null, null, 'critical'
                    );
                    return $this->respondWithError(
                        'خطا در ثبت‌نام کاربر. لطفاً دوباره تلاش کنید.',
                        500,
                        $request,
                        'general'
                    );
                }
            } else {
                $this->auditService->log(
                    'otp_valid_no_user_or_pending_registration',
                    'OTP valid but no user or pending registration data found for mobile: ' . $mobileNumber,
                    $request,
                    ['mobile_number' => $mobileNumber],
                    hash('sha256', $mobileNumber),
                    null, null, null, 'warning'
                );
                return $this->respondWithError(
                    'مشکلی در فرآیند ثبت‌نام رخ داد. لطفاً دوباره از ابتدا شروع کنید.',
                    400,
                    $request,
                    'general',
                    'auth.mobile-login-form'
                );
            }
        }

        // Log in the user
        Auth::login($user, $request->boolean('remember'));

        $this->auditService->log(
            'user_logged_in_via_otp',
            'User logged in via OTP: ' . $mobileNumber,
            $request,
            ['user_id' => $user->id, 'mobile_number' => $mobileNumber],
            hash('sha256', $mobileNumber),
            $user->id, 'User', $user->id, 'info'
        );

        // Clear session data used for OTP verification
        $request->session()->forget(self::SESSION_MOBILE_FOR_OTP);
        $request->session()->forget(self::SESSION_MOBILE_FOR_REGISTRATION);

        // Redirect to intended URL or dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        $mobileNumber = Auth::user() ? Auth::user()->mobile_number : null;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->auditService->log(
            'user_logged_out',
            'User logged out.',
            $request,
            ['user_id' => $userId, 'mobile_number' => $mobileNumber],
            $mobileNumber ? hash('sha256', $mobileNumber) : null,
            $userId, 'User', $userId, 'info'
        );

        return redirect('/');
    }

    /**
     * Helper method to normalize mobile number (remove spaces, convert Persian/Arabic digits, ensure 09 prefix).
     *
     * @param string $mobileNumber
     * @return string
     * @throws \InvalidArgumentException If the mobile number is invalid after normalization.
     */
    private function normalizeMobileNumber(string $mobileNumber): string
    {
        // Remove any spaces or dashes
        $normalizedNumber = str_replace([' ', '-'], '', $mobileNumber);

        // Convert Persian/Arabic digits to English digits
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];

        $normalizedNumber = str_replace($persianDigits, range(0, 9), $normalizedNumber);
        $normalizedNumber = str_replace($arabicDigits, range(0, 9), $normalizedNumber);

        // Ensure it starts with '09' and has 11 digits
        if (Str::startsWith($normalizedNumber, '9') && strlen($normalizedNumber) === 10) {
            $normalizedNumber = '0' . $normalizedNumber;
        }

        // Stricter validation after normalization
        if (!preg_match('/^09\d{9}$/', $normalizedNumber)) {
            Log::warning('Invalid mobile number after normalization: ' . $mobileNumber . ' -> ' . $normalizedNumber);
            throw new \InvalidArgumentException('شماره موبایل پس از نرمال‌سازی نامعتبر است.');
        }

        return $normalizedNumber;
    }

    /**
     * Helper method to normalize OTP input (remove spaces, convert Persian/Arabic digits).
     *
     * @param string $otp
     * @return string
     */
    private function normalizeOtp(string $otp): string
    {
        // Remove any spaces or dashes
        $normalizedOtp = str_replace([' ', '-'], '', $otp);

        // Convert Persian/Arabic digits to English digits
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        
        $normalizedOtp = str_replace($persianDigits, range(0, 9), $normalizedOtp);
        $normalizedOtp = str_replace($arabicDigits, range(0, 9), $normalizedOtp);

        return $normalizedOtp;
    }

    /**
     * Helper method to respond with error, either JSON or redirect.
     *
     * @param string $message
     * @param int $code
     * @param Request $request
     * @param string $errorField
     * @param string|null $redirectRoute
     * @param bool $showRegisterLink
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function respondWithError(
        string $message,
        int $code,
        Request $request,
        string $errorField = 'general',
        ?string $redirectRoute = null,
        bool $showRegisterLink = false
    ) {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $code);
        }
        $redirect = back()->withErrors([$errorField => $message])->withInput();
        if ($redirectRoute) {
            $redirect = redirect()->route($redirectRoute)->with('status', $message);
            if ($showRegisterLink) {
                $redirect->with('show_register_link', true);
            }
        }
        return $redirect;
    }
}
