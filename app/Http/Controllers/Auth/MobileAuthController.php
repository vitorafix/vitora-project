<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator; // Still used for internal helper validation
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Services\MelipayamakSmsService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Session;
use App\Services\CartService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// Import new service classes and interfaces
use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface;
use App\Contracts\Services\AuditServiceInterface;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;

class MobileAuthController extends Controller
{
    // Dependency Injection for services
    protected $cartService;
    protected $otpService;
    protected $rateLimitService;
    protected $auditService;

    public function __construct(
        CartService $cartService,
        OtpServiceInterface $otpService, // Type-hinting against interface
        RateLimitServiceInterface $rateLimitService, // Type-hinting against interface
        AuditServiceInterface $auditService // Type-hinting against interface
    ) {
        $this->cartService = $cartService;
        $this->otpService = $otpService;
        $this->rateLimitService = $rateLimitService;
        $this->auditService = $auditService;
    }

    /**
     * Display the mobile number login form.
     *
     * @return \Illuminate\View\View
     */
    public function showMobileLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Send OTP to the mobile number.
     *
     * @param  \App\Http\Requests\SendOtpRequest  $request // Using Form Request for validation
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function sendOtp(SendOtpRequest $request)
    {
        // Honeypot check is now handled by the Form Request's validation logic,
        // but a final check here is harmless and can act as a fallback.
        if ($this->isBot($request)) {
            return $this->handleBotDetection($request);
        }

        // Validation is handled by SendOtpRequest.
        // If validation fails, it automatically redirects or returns JSON response.

        $mobileNumber = $request->mobile_number;
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // IP-based Rate Limiting for sendOtp
        if (!$this->rateLimitService->checkAndIncrementIpAttempts($ipAddress)) {
            $this->auditService->log('ip_rate_limit_exceeded', 'Too many OTP send attempts from IP.', $request, null, Auth::id());
            return $this->respondWithError(
                'تعداد تلاش‌ها از IP شما بیش از حد مجاز است. لطفاً پس از ' . config('auth.otp.ip_attempts.cooldown_minutes') . ' دقیقه دیگر تلاش کنید.',
                429,
                $request,
                'general'
            );
        }

        // Check for user existence (for login/registration flow)
        $user = User::where('mobile_number', $mobileNumber)->first();
        if (!Auth::check() && !$user) {
            $this->auditService->log('unauthenticated_otp_send_non_existent', 'Unauthenticated user attempted OTP send for non-existent mobile number.', $request, hash('sha256', $mobileNumber));
            return $this->respondWithError('درخواست نامعتبر است.', 400, $request, 'mobile_number');
        }

        // OTP send attempts rate limiting for mobile number
        if (!$this->rateLimitService->checkAndIncrementSendAttempts($mobileNumber)) {
            $this->auditService->log('mobile_otp_send_rate_limit_exceeded', 'Too many OTP requests for mobile number.', $request, hash('sha256', $mobileNumber));
            return $this->respondWithError(
                'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً پس از ' . config('auth.otp.send_attempts.cooldown_minutes') . ' دقیقه دیگر تلاش کنید.',
                429,
                $request,
                'mobile_number'
            );
        }

        // Generate and store OTP using OtpService
        $otp = $this->otpService->generateAndStore($mobileNumber);

        $smsService = new MelipayamakSmsService();
        $patternCode = config('services.melipayamak.pattern_code');

        try {
            if ($patternCode) {
                $smsService->sendByPattern($mobileNumber, $patternCode, ['verification-code' => $otp]);
            } else {
                $smsService->send($mobileNumber, "کد تایید شما: " . $otp . "\nفروشگاه چای");
            }
            $this->auditService->log('otp_send_success', 'OTP sent to mobile number.', $request, hash('sha256', $mobileNumber));

        } catch (\Exception $e) {
            $this->auditService->log('otp_send_failure', 'Error sending OTP SMS: ' . $e->getMessage(), $request, hash('sha256', $mobileNumber));
            return $this->respondWithError('خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.', 500, $request, 'mobile_number');
        }

        // Store the redirect path in session if provided from the profile page
        if ($request->has('redirect_to_profile')) {
            $request->session()->put('otp_redirect_after_verify', $request->input('redirect_to_profile'));
            Log::info('OTP redirect path stored for profile update', ['path' => $request->input('redirect_to_profile')]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'کد تایید به شماره شما ارسال شد.'], 200);
        }

        // Encrypt mobile number before storing in session for security
        $request->session()->put('mobile_number_for_otp', Crypt::encryptString($mobileNumber));
        return redirect()->route('auth.verify-otp-form')->with('status', 'کد تایید به شماره شما ارسال شد.');
    }

    /**
     * Display the OTP verification form.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showVerifyOtpForm(Request $request): View|RedirectResponse
    {
        try {
            // Decrypt mobile number when retrieving from session
            $mobileNumber = Crypt::decryptString($request->session()->get('mobile_number_for_otp'));
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $this->auditService->log('invalid_encrypted_mobile_in_session', 'Attempt to access verifyOtpForm with invalid encrypted mobile number in session.', $request);
            return redirect()->route('auth.mobile-login-form')->withErrors(['mobile_number' => 'ابتدا شماره موبایل خود را وارد کنید.']);
        }
        
        if (!$mobileNumber) {
            $this->auditService->log('missing_mobile_in_session', 'Attempt to access verifyOtpForm without mobile number in session.', $request);
            return redirect()->route('auth.mobile-login-form')->withErrors(['mobile_number' => 'ابتدا شماره موبایل خود را وارد کنید.']);
        }
        return view('auth.verify-otp', compact('mobileNumber'));
    }

    /**
     * Verify OTP and log in/register the user.
     *
     * @param  \App\Http\Requests\VerifyOtpRequest  $request // Using Form Request for validation
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verifyOtp(VerifyOtpRequest $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // Honeypot check is now handled by the Form Request's validation logic,
        // but a final check here is harmless and can act as a fallback.
        if ($this->isBot($request)) {
            return $this->handleBotDetection($request);
        }

        // Validation is handled by VerifyOtpRequest.
        // If validation fails, it automatically redirects or returns JSON response.

        $mobileNumber = $request->mobile_number;
        $enteredOtp = $request->otp; // Get the entered OTP
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // --- بهبودهای جدید برای اعتبارسنجی و لاگ OTP ---
        // 1. نرمال‌سازی OTP ورودی: حذف فاصله‌ها و تبدیل اعداد فارسی/عربی به انگلیسی
        $enteredOtp = $this->normalizeOtp($enteredOtp);
        // ----------------------------------------------------

        // IP-based Rate Limiting for verifyOtp
        if (!$this->rateLimitService->checkAndIncrementIpAttempts($ipAddress)) {
            $this->auditService->log('ip_rate_limit_exceeded', 'Too many OTP verification attempts from IP.', $request, null, Auth::id());
            return $this->respondWithError(
                'تعداد تلاش‌ها از IP شما بیش از حد مجاز است. لطفاً پس از ' . config('auth.otp.ip_attempts.cooldown_minutes') . ' دقیقه دیگر تلاش کنید.',
                429,
                $request,
                'otp' // Error on OTP field for consistency
            );
        }

        // OTP verification attempts rate limiting for mobile number
        if (!$this->rateLimitService->checkAndIncrementVerifyAttempts($mobileNumber)) {
            $this->auditService->log('mobile_otp_verify_rate_limit_exceeded', 'Too many OTP verification attempts for mobile number.', $request, hash('sha256', $mobileNumber));
            return $this->respondWithError(
                'تعداد تلاش‌های تأیید بیش از حد مجاز است. لطفاً پس از ' . config('auth.otp.verify_attempts.cooldown_minutes') . ' دقیقه دیگر تلاش کنید.',
                429,
                $request,
                'otp'
            );
        }

        // --- لاگ‌برداری برای اشکال‌زدایی مقایسه OTP ---
        $otpCacheKey = 'otp_' . $mobileNumber;
        $storedOtp = Cache::get($otpCacheKey);
        Log::debug('Verifying OTP via OtpService', [
            'mobile_hash' => hash('sha256', $mobileNumber),
            'otp_cache_key' => $otpCacheKey,
            'stored_otp_exists_before_check' => Cache::has($otpCacheKey),
            'stored_otp_value_for_debug' => $storedOtp, // فقط برای اشکال‌زدایی، در محیط پروداکشن حذف شود
            'entered_otp_value_for_debug' => $enteredOtp, // فقط برای اشکال‌زدایی، در محیط پروداکشن حذف شود
        ]);
        // ---------------------------------------------

        // Verify OTP using OtpService
        if (!$this->otpService->verify($mobileNumber, $enteredOtp)) {
            // Audit Trail for OTP verification failure
            $this->auditService->log('otp_verification_failure', 'Invalid or expired OTP entered.', $request, hash('sha256', $mobileNumber), Auth::id());
            return $this->respondWithError('کد تایید اشتباه یا منقضی شده است.', 400, $request, 'otp');
        }

        // Reset rate limits on successful verification
        $this->rateLimitService->resetVerifyAttempts($mobileNumber);
        $this->rateLimitService->resetIpAttempts($ipAddress);

        $this->auditService->log('otp_verification_success', 'OTP verified successfully.', $request, hash('sha256', $mobileNumber), Auth::id());

        $currentSessionId = Session::getId();
        $authenticatedUser = Auth::user(); // Get the currently authenticated user

        $message = '';
        $user = null; // Initialize user to null

        // Process user action (login, registration, or mobile update)
        if ($authenticatedUser && $authenticatedUser->mobile_number !== $mobileNumber) {
            // Scenario: Authenticated user changing mobile number
            $redirectToProfile = $request->session()->get('otp_redirect_after_verify');
            if (!$redirectToProfile) {
                $this->auditService->log('unauthorized_mobile_change_attempt', 'Authenticated user tried to change mobile number outside of profile flow.', $request, hash('sha256', $mobileNumber), $authenticatedUser->id);
                return $this->respondWithError('شما قبلاً ثبت‌نام کرده‌اید. لطفاً ورود کنید.', 403, $request);
            }
            $authenticatedUser->mobile_number = $mobileNumber;
            $authenticatedUser->save();
            $user = $authenticatedUser; // Set user to the updated authenticated user
            $message = 'شماره موبایل با موفقیت تغییر یافت.';
            $this->auditService->log('mobile_number_updated', 'Mobile number successfully updated.', $request, hash('sha256', $mobileNumber), $user->id);

        } else if (!$authenticatedUser) {
            // Scenario: Unauthenticated user trying to login or register
            $user = User::where('mobile_number', $mobileNumber)->first();
            if ($user) {
                // User exists, log them in
                Auth::login($user);
                $this->cartService->transferGuestCartToUserCart($currentSessionId, $user);
                $message = 'ورود با موفقیت انجام شد.';
                $this->auditService->log('user_login_success', 'User logged in successfully via OTP.', $request, hash('sha256', $mobileNumber), $user->id);
            } else {
                // User does not exist, check for pending registration data
                $registrationData = Cache::get('pending_registration_' . $mobileNumber);
                if ($registrationData) {
                    $user = User::create([
                        'name' => $registrationData['name'],
                        'lastname' => $registrationData['lastname'],
                        'mobile_number' => $registrationData['mobile_number'],
                        'profile_completed' => false, // یا is_active = true
                    ]);
                    Cache::forget('pending_registration_' . $mobileNumber);
                    Auth::login($user);
                    $this->cartService->assignGuestCartToNewUser($currentSessionId, $user);
                    $message = 'ثبت‌نام و ورود با موفقیت انجام شد.';
                    $this->auditService->log('user_registration_success', 'New user registered successfully via OTP.', $request, hash('sha256', $mobileNumber), $user->id);
                } else {
                    // No user and no pending registration data
                    $this->auditService->log('no_user_or_pending_registration', 'User not found and no pending registration data.', $request, hash('sha256', $mobileNumber));
                    $request->session()->flash('user_not_found_mobile', $mobileNumber);
                    return $this->respondWithError('کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.', 404, $request, 'mobile_number', 'auth.mobile-login-form', true);
                }
            }
        } else {
            // Scenario: Authenticated user verifies OTP for their current mobile number (no change needed)
            $user = $authenticatedUser; // Set user to the authenticated user
            $message = 'شماره موبایل شما قبلاً همین بوده و نیازی به تغییر نبود.';
            $this->auditService->log('otp_verified_same_mobile', 'Attempted to verify OTP for the same mobile number as current user.', $request, hash('sha256', $mobileNumber), $user->id);
        }

        // Check for the redirect path stored in session
        $redirectTo = $request->session()->pull('otp_redirect_after_verify', null);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'user' => $user, 'redirect_url' => $redirectTo], 200);
        }

        if ($redirectTo) {
            Log::info('Redirecting to stored path after OTP verification', ['path' => $redirectTo]);
            return redirect($redirectTo)->with('status', $message);
        }

        return redirect()->intended('/')->with('status', $message);
    }

    /**
     * Log out the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id(); // Get user ID before logging out
        Auth::guard('web')->logout();
        Log::info('User logged out', ['user_id' => $userId]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Audit Trail for logout
        $this->auditService->log('user_logout', 'User logged out.', $request, null, $userId);

        return redirect('/');
    }

    /**
     * Helper method to check for bot activity (honeypot).
     *
     * @param Request $request
     * @return bool
     */
    private function isBot(Request $request): bool
    {
        return $request->filled('website');
    }

    /**
     * Helper method to handle bot detection response.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function handleBotDetection(Request $request)
    {
        Log::warning('Bot detected (honeypot triggered).', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        $this->auditService->log('bot_detection', 'Bot activity detected via honeypot.', $request);
        return $this->respondWithError('درخواست نامعتبر است.', 400, $request);
    }

    /**
     * Helper method to handle validation errors.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function handleValidationError(\Illuminate\Contracts\Validation\Validator $validator, Request $request)
    {
        Log::warning('Validation failed.', ['errors' => $validator->errors()->toArray()]);
        $this->auditService->log('validation_failure', 'Form validation failed.', $request, null, Auth::id());
        if ($request->expectsJson()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        return back()->withErrors($validator)->withInput();
    }

    /**
     * Helper method to respond with an error message.
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
}
