<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache; // Cache facade for OTP storage
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\View\View;
// use Illuminate\Support\Facades\Session; // REMOVED: No longer using Session
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

// Import new service classes and interfaces - FIXED SYNTAX
use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface;
use App\Contracts\Services\AuditServiceInterface;
use App\Services\Contracts\CartServiceInterface;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\Auth\RegisterRequest;

// Add Spatie's Role class - FIXED SYNTAX
use Spatie\Permission\Models\Role;

/**
 * Custom Exception to carry generated OTP on failure.
 * Ideally, this class should be in its own file (e.g., App/Exceptions/OtpSendException.php)
 * but for demonstration purposes, it's included here.
 * استثنای سفارشی برای حمل OTP تولید شده در صورت شکست.
 * به صورت ایده‌آل، این کلاس باید در فایل خودش باشد (مثلاً App/Exceptions/OtpSendException.php)
 * اما برای اهداف نمایشی، در اینجا گنجانده شده است.
 */
class OtpSendException extends \Exception
{
    public ?string $generatedOtp;

    public function __construct(string $message, ?string $generatedOtp = null, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->generatedOtp = $generatedOtp;
    }

    public function getGeneratedOtp(): ?string
    {
        return $this->generatedOtp;
    }
}


class MobileAuthController extends Controller
{
    // Constants for Rate Limiting (these can also be moved to config files)
    const OTP_SEND_RATE_LIMIT = 5;
    const OTP_SEND_COOLDOWN_MINUTES = 1;
    const OTP_VERIFY_RATE_LIMIT = 10;
    const OTP_VERIFY_COOLDOWN_MINUTES = 5;
    const OTP_IP_MAX_ATTEMPTS = 10;
    const OTP_IP_COOLDOWN_MINUTES = 60;

    // REMOVED: Constants for Session Keys are no longer needed
    // const SESSION_MOBILE_FOR_OTP = 'mobile_number_for_otp';
    // const SESSION_MOBILE_FOR_REGISTRATION = 'mobile_number_for_registration';
    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_'; // Still relevant for cache
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10;

    // Dependency Injection for services
    protected OtpServiceInterface $otpService;
    protected RateLimitServiceInterface $rateLimitService;
    protected AuditServiceInterface $auditService;
    protected CartServiceInterface $cartService;

    public function __construct(
        OtpServiceInterface $otpService,
        RateLimitServiceInterface $rateLimitService,
        AuditServiceInterface $auditService,
        CartServiceInterface $cartService
    ) {
        $this->otpService = $otpService;
        $this->rateLimitService = $rateLimitService;
        $this->auditService = $auditService;
        $this->cartService = $cartService;
    }

    /**
     * Show the mobile login form.
     * نمایش فرم ورود با موبایل.
     *
     * @return View|RedirectResponse
     */
    public function showMobileLoginForm(): View|RedirectResponse
    {
        // مسئولیت کنترلر: رندر کردن ویو برای ورود با موبایل.
        // اگر کاربر از قبل احراز هویت شده است (با JWT)، او را به داشبورد هدایت کنید.
        // توجه: برای احراز هویت JWT در سمت سرور، باید توکن را از هدر درخواست بررسی کنید.
        // در اینجا فرض می‌شود که این فرم برای شروع جریان ورود است و کاربر هنوز توکن ندارد.
        try {
            if (JWTAuth::parseToken()->authenticate()) {
                return redirect()->intended(route('dashboard'));
            }
        } catch (Throwable $e) {
            // Token is invalid or not present, proceed to login form
            Log::debug('No valid JWT token found or token invalid, proceeding to login form: ' . $e->getMessage());
        }

        // مسیر ویو به 'auth.login' تغییر داده شد تا با ساختار 'resources/views/auth/login.blade.php' مطابقت داشته باشد.
        return view('auth.login');
    }

    /**
     * Show the OTP verification form.
     * نمایش فرم تایید OTP.
     * این متد پس از ارسال OTP یا هنگام هدایت به صفحه تایید فراخوانی می‌شود.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function showOtpVerifyForm(Request $request): View|RedirectResponse
    {
        // مسئولیت کنترلر: دریافت شماره موبایل از کوئری پارامتر (به جای سشن).
        // این فرض می‌کند که کلاینت پس از sendOtp، شماره موبایل را به این مسیر ارسال می‌کند.
        $mobileNumber = $request->query('mobile_number');

        if (!$mobileNumber) {
            return redirect()->route('auth.mobile-login-form')->withErrors(['mobile_number' => 'شماره موبایل برای تأیید کد یافت نشد. لطفاً دوباره وارد شوید.']);
        }

        // نیازی به decrypt کردن نیست، زیرا شماره مستقیماً از کوئری می‌آید.
        // $attemptCount دیگر از سشن گرفته نمی‌شود، زیرا RateLimitService مسئول آن است.
        $attemptCount = 0; // می‌توانید این را از RateLimitService دریافت کنید اگر نیاز دارید نمایش دهید.

        // رندر کردن ویو تایید OTP.
        return view('auth.verify-otp', compact('mobileNumber', 'attemptCount'));
    }


    /**
     * Send OTP to the provided mobile number.
     * ارسال OTP به شماره موبایل ارائه شده.
     *
     * @param SendOtpRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function sendOtp(SendOtpRequest $request)
    {
        $mobileNumber = $request->mobile_number;
        $ipAddress = $request->ip();

        Log::debug('MobileAuthController: Starting sendOtp process for mobile: ' . maskForLog($mobileNumber, 'phone'));
        try {
            Log::debug('MobileAuthController: Calling otpService->sendOtpForMobile for mobile: ' . maskForLog($mobileNumber, 'phone'));

            // OtpService دیگر شیء سشن را دریافت نمی‌کند.
            // OtpService باید از Cache (مثل Redis) برای ذخیره OTP استفاده کند.
            $this->otpService->sendOtpForMobile(
                $mobileNumber,
                $ipAddress,
                $this->rateLimitService,
                function ($message, $level = 'info', $userId = null, $userType = null, $objectId = null, $extra = []) use ($request, $mobileNumber, $ipAddress) {
                    $this->auditService->log(
                        'otp_send_event',
                        $message,
                        $request,
                        array_merge($extra, [
                            'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                            'ip_address_masked' => maskForLog($ipAddress, 'ip')
                        ]),
                        hashForCache($mobileNumber, 'audit_mobile_hash'),
                        $userId, $userType, $objectId, $level
                    );
                }
            );

            Log::debug('MobileAuthController: otpService->sendOtpForMobile completed successfully for mobile: ' . maskForLog($mobileNumber, 'phone'));

            if ($request->expectsJson()) {
                return response()->json(['message' => 'کد تأیید با موفقیت ارسال شد.']);
            }

            // NEW: Check if this is a new user registration flow based on user existence
            $userExists = User::where('mobile_number', $mobileNumber)->exists();

            if (!$userExists) {
                Log::info('MobileAuthController: Redirecting new user to registration form.');
                return redirect()->route('auth.register-form', ['mobile_number' => $mobileNumber])
                                 ->with('status', 'شماره موبایل شما یافت نشد. لطفاً برای ادامه ثبت‌نام کنید.');
            } else {
                Log::info('MobileAuthController: Redirecting existing user to OTP verification form.');
                return redirect()->route('auth.verify-otp-form', ['mobile_number' => $mobileNumber])->with('status', 'کد تأیید با موفقیت ارسال شد.');
            }

        } catch (OtpSendException $e) {
            Log::error('MobileAuthController: OtpSendException caught for mobile: ' . maskForLog($mobileNumber, 'phone') . '. Error: ' . $e->getMessage());
            $generatedOtp = $e->getGeneratedOtp();
            $this->auditService->log(
                'otp_send_failed_exception',
                'Failed to send OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(),
                $request,
                [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'),
                    'error' => $e->getMessage(),
                    'generated_otp' => $generatedOtp
                ],
                hashForCache($mobileNumber, 'audit_mobile_hash'),
                null, null, null, 'error'
            );

            return $this->respondWithError(
                $e->getMessage(),
                $e->getCode() ?: 500,
                $request,
                'mobile_number'
            );
        } catch (\Exception $e) {
            Log::error('MobileAuthController: Generic Exception caught for mobile: ' . maskForLog($mobileNumber, 'phone') . '. Error: ' . $e->getMessage());
            $this->auditService->log(
                'otp_send_failed_exception',
                'Failed to send OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(),
                $request,
                [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'),
                    'error' => $e->getMessage()
                ],
                hashForCache($mobileNumber, 'audit_mobile_hash'),
                null, null, null, 'error'
            );

            return $this->respondWithError(
                $e->getMessage(),
                $e->getCode() ?: 500,
                $request,
                'mobile_number'
            );
        }
    }

    /**
     * Register a new user.
     * ثبت نام کاربر جدید.
     *
     * @param RegisterRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'lastname' => $validatedData['lastname'] ?? null,
                'mobile_number' => $validatedData['mobile_number'],
            ]);

            $userRole = Role::where('name', 'user')->first();
            if ($userRole) {
                $user->assignRole($userRole);
                $this->auditService->log(
                    'role_assigned_on_registration',
                    'Role "user" assigned to new user: ' . maskForLog($user->mobile_number, 'phone'),
                    $request,
                    [
                        'user_id' => $user->id,
                        'mobile_number_masked' => maskForLog($user->mobile_number, 'phone'),
                        'role' => 'user'
                    ],
                    hashForCache($user->mobile_number, 'audit_mobile_hash'),
                    $user->id, 'User', $user->id, 'info'
                );
            } else {
                Log::warning('MobileAuthController: Role "user" not found when registering user: ' . maskForLog($user->mobile_number, 'phone'));
                $this->auditService->log(
                    'role_not_found_on_registration',
                    'Attempted to assign "user" role but role not found for user: ' . maskForLog($user->mobile_number, 'phone'),
                    $request,
                    [
                        'user_id' => $user->id,
                        'mobile_number_masked' => maskForLog($user->mobile_number, 'phone'),
                        'missing_role' => 'user'
                    ],
                    hashForCache($user->mobile_number, 'audit_mobile_hash'),
                    $user->id, 'User', $user->id, 'warning'
                );
            }

            $this->auditService->log(
                'user_registered',
                'New user registered: ' . maskForLog($user->mobile_number, 'phone'),
                $request,
                [
                    'user_id' => $user->id,
                    'mobile_number_masked' => maskForLog($user->mobile_number, 'phone'),
                ],
                hashForCache($user->mobile_number, 'audit_mobile_hash'),
                $user->id, 'User', $user->id, 'info'
            );

            if ($request->expectsJson()) {
                // Generate JWT token for API clients immediately after registration
                $token = JWTAuth::fromUser($user);
                Log::info('MobileAuthController: JWT token generated for newly registered user ' . $user->id);

                return response()->json([
                    'message' => 'ثبت‌نام با موفقیت انجام شد و شما وارد شدید.',
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60 // TTL in seconds
                ], 201);
            }

            // For web flow, redirect to verify-otp-form with mobile number in query
            return redirect()->route('auth.verify-otp-form', ['mobile_number' => $user->mobile_number])->with('status', 'ثبت‌نام شما با موفقیت انجام شد. کد تأیید برای شما ارسال گردید.');

        } catch (QueryException $e) {
            Log::error('MobileAuthController: Database error during registration: ' . $e->getMessage(), [
                'mobile_number_masked' => maskForLog($validatedData['mobile_number'], 'phone'),
                'exception' => $e->getTraceAsString()
            ]);
            return $this->respondWithError(
                'خطا در ثبت‌نام. لطفاً دوباره تلاش کنید.',
                500,
                $request,
                'general'
            );
        } catch (\Exception $e) {
            Log::error('MobileAuthController: General error during registration: ' . $e->getMessage(), [
                'mobile_number_masked' => maskForLog($validatedData['mobile_number'], 'phone'),
                'exception' => $e->getTraceAsString()
            ]);
            return $this->respondWithError(
                'خطایی رخ داد. لطفاً دوباره تلاش کنید.',
                500,
                $request,
                'general'
            );
        }
    }


    /**
     * Verify OTP and log in the user or redirect to registration.
     * تایید OTP و ورود کاربر یا هدایت به ثبت نام.
     *
     * @param VerifyOtpRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function verifyOtpAndLogin( // RENAMED FROM verifyOtp
        VerifyOtpRequest $request
    ): RedirectResponse|\Illuminate\Http\JsonResponse {
        $mobileNumber = $request->mobile_number;
        $otp = $request->otp;
        $ipAddress = $request->ip();
        $guestUuid = $request->cookie('guest_uuid');

        Log::debug('MobileAuthController: Starting verifyOtpAndLogin process for mobile: ' . maskForLog($mobileNumber, 'phone'));
        try {
            Log::debug('MobileAuthController: Calling otpService->verifyOtpForMobile for mobile: ' . maskForLog($mobileNumber, 'phone'));

            // OtpService دیگر شیء سشن را دریافت نمی‌کند.
            $user = $this->otpService->verifyOtpForMobile(
                $mobileNumber,
                $otp,
                $ipAddress,
                $this->rateLimitService,
                function ($message, $level = 'info', $userId = null, $userType = null, $objectId = null, $extra = []) use ($request, $mobileNumber, $ipAddress) {
                    $this->auditService->log(
                        'otp_verify_event',
                        $message,
                        $request,
                        array_merge($extra, [
                            'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                            'ip_address_masked' => maskForLog($ipAddress, 'ip')
                        ]),
                        hashForCache($mobileNumber, 'audit_mobile_hash'),
                        $userId, $userType, $objectId, $level
                    );
                }
            );

            Log::debug('MobileAuthController: otpService->verifyOtpForMobile completed successfully for mobile: ' . maskForLog($mobileNumber, 'phone'));

            // REMOVED: Auth::login is no longer used for JWT flow.
            // Auth::login($user, $request->boolean('remember'));

            // NEW: Merge any existing guest cart with the newly logged-in user's cart
            if ($guestUuid) {
                Log::info('MobileAuthController: Guest UUID found (' . $guestUuid . ') for user login. Attempting cart merge.');
                $this->cartService->assignGuestCartToUser($user, $guestUuid);
                Log::info('MobileAuthController: Cart merge initiated for user ' . $user->id . ' with guest UUID ' . $guestUuid);
            } else {
                Log::info('MobileAuthController: No guest_uuid found in cookie for user login, skipping cart merge.');
            }

            $this->auditService->log(
                'user_logged_in_via_otp',
                'User logged in via OTP: ' . maskForLog($mobileNumber, 'phone'),
                $request,
                [
                    'user_id' => $user->id,
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone')
                ],
                hashForCache($mobileNumber, 'audit_mobile_hash'),
                $user->id, 'User', $user->id, 'info'
            );

            // REMOVED: Session data is no longer used for OTP verification.
            // $request->session()->forget(self::SESSION_MOBILE_FOR_OTP);
            // $request->session()->forget(self::SESSION_MOBILE_FOR_REGISTRATION);

            if ($request->expectsJson()) {
                // Generate JWT token for API clients
                $token = JWTAuth::fromUser($user);
                Log::info('MobileAuthController: JWT token generated for user ' . $user->id);

                return response()->json([
                    'message' => 'ورود با موفقیت انجام شد.',
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60 // TTL in seconds
                ]);
            }

            // For web flow, redirect to intended URL or home.
            // The client-side should handle storing the JWT token from the previous API call
            // or perform a direct login if this is a purely web-based OTP flow (less common with JWT).
            // For simplicity, we assume the web client will handle the token after a successful API verification.
            return redirect()->intended('/');

        } catch (\Exception $e) {
            Log::error('MobileAuthController: Exception caught during verifyOtpAndLogin for mobile: ' . maskForLog($mobileNumber, 'phone') . '. Error: ' . $e->getMessage());
            $this->auditService->log(
                'otp_verify_failed_exception',
                'Failed to verify OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(),
                $request,
                [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'),
                    'error' => $e->getMessage()
                ],
                hashForCache($mobileNumber, 'audit_mobile_hash'),
                null, null, null, 'error'
            );

            return $this->respondWithError(
                $e->getMessage(),
                $e->getCode() ?: 401,
                $request,
                'otp'
            );
        }
    }

    /**
     * Log the user out of the application.
     * خروج کاربر از برنامه.
     *
     * @param Request $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $userId = null;
        $mobileNumber = null;

        try {
            // Attempt to get the authenticated user via JWT
            $user = JWTAuth::parseToken()->authenticate();
            if ($user) {
                $userId = $user->id;
                $mobileNumber = $user->mobile_number;
            }
        } catch (Throwable $e) {
            // No valid JWT token or user not authenticated via JWT
            Log::debug('No JWT authenticated user during logout attempt: ' . $e->getMessage());
        }


        // Invalidate JWT token if it exists
        try {
            if (JWTAuth::getToken()) {
                JWTAuth::invalidate(JWTAuth::getToken());
                Log::info('MobileAuthController: JWT token invalidated for user ' . ($userId ?? 'N/A'));
            }
        } catch (Throwable $e) {
            Log::warning('MobileAuthController: Failed to invalidate JWT token during logout for user ' . ($userId ?? 'N/A') . ': ' . $e->getMessage());
        }

        // REMOVED: Session-based logout is no longer needed for JWT.
        // Auth::guard('web')->logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();

        $this->auditService->log(
            'user_logged_out',
            'User logged out.',
            $request,
            [
                'user_id' => $userId,
                'mobile_number_masked' => maskForLog($mobileNumber ?? 'N/A', 'phone')
            ],
            $mobileNumber ? hashForCache($mobileNumber, 'audit_mobile_hash') : null,
            $userId, 'User', $userId, 'info'
        );

        // Always return JSON response for API logout, or redirect for web (if applicable)
        if ($request->expectsJson()) {
            return response()->json(['message' => 'خروج با موفقیت انجام شد.']);
        }

        // For web-based logout, redirect to home. The client-side should remove the JWT token.
        return redirect('/');
    }

    /**
     * Helper method to normalize mobile number (remove spaces, convert Persian/Arabic digits, ensure 09 prefix).
     * This is a utility function, and can remain in the controller or be moved to a dedicated helper/utility class.
     * متد کمکی برای نرمال‌سازی شماره موبایل (حذف فواصل، تبدیل ارقام فارسی/عربی، اطمینان از پیشوند 09).
     * این یک تابع کمکی است و می‌تواند در کنترلر باقی بماند یا به یک کلاس کمکی/ابزاری اختصاصی منتقل شود.
     *
     * @param string $mobileNumber
     * @return string
     * @throws \InvalidArgumentException If the mobile number is invalid after normalization.
     */
    private function normalizeMobileNumber(string $mobileNumber): string
    {
        $normalizedNumber = str_replace([' ', '-'], '', $mobileNumber);
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $normalizedNumber = str_replace($persianDigits, range(0, 9), $normalizedNumber);
        $normalizedNumber = str_replace($arabicDigits, range(0, 9), $normalizedNumber);

        if (Str::startsWith($normalizedNumber, '9') && strlen($normalizedNumber) === 10) {
            $normalizedNumber = '0' . $normalizedNumber;
        }

        if (!preg_match('/^09\d{9}$/', $normalizedNumber)) {
            Log::warning('Invalid mobile number after normalization: ' . maskForLog($mobileNumber, 'phone') . ' -> ' . maskForLog($normalizedNumber, 'phone'));
            throw new \InvalidArgumentException('شماره موبایل پس از نرمال‌سازی نامعتبر است.');
        }

        return $normalizedNumber;
    }

    /**
     * Helper method to normalize OTP input (remove spaces, convert Persian/Arabic digits).
     * Similar to normalizeMobileNumber, this can be a utility function.
     * متد کمکی برای نرمال‌سازی ورودی OTP (حذف فواصل، تبدیل ارقام فارسی/عربی).
     * مشابه normalizeMobileNumber، این می‌تواند یک تابع کمکی باشد.
     *
     * @param string $otp
     * @return string
     */
    private function normalizeOtp(string $otp): string
    {
        $normalizedOtp = str_replace([' ', '-'], '', $otp);
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $normalizedOtp = str_replace($persianDigits, range(0, 9), $normalizedOtp);
        $normalizedOtp = str_replace($arabicDigits, range(0, 9), $normalizedOtp);

        return $normalizedOtp;
    }

    /**
     * Helper method to respond with error, either JSON or redirect.
     * This method is a controller-level utility for consistent error responses.
     * متد کمکی برای پاسخ با خطا، چه JSON و چه ریدایرکت.
     * این متد یک ابزار در سطح کنترلر برای پاسخ‌های خطای سازگار است.
     *
     * @param string $message
     * @param int    $code
     * @param Request $request
     * @param string $errorField
     * @param string|null $redirectRoute
     * @param bool   $showRegisterLink
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function respondWithError(
        string $message,
        int $code,
        Request $request,
        string $errorField = 'general',
        ?string $redirectRoute = null,
        bool  $showRegisterLink = false
    ) {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'error_field' => $errorField], $code);
        }
        // For web-based redirects, `back()->withErrors` still relies on session.
        // If your web views are completely stateless and don't use session for errors,
        // you might need to pass errors via query parameters or remove this part.
        // For now, it's kept as a fallback for web, but for API, only JSON is returned.
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
