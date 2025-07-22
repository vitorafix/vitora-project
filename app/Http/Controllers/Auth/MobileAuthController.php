<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

// Import new service classes and interfaces
use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface;
use App\Contracts\Services\AuditServiceInterface;
use App\Services\Contracts\CartServiceInterface;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
// REMOVED: No longer needed here as registration is handled by RegisterController
// use App\Http\Requests\Auth\RegisterRequest;

// Add Spatie's Role class if used
use Spatie\Permission\Models\Role;

use App\Exceptions\OtpSendException; // NEW: Import OtpSendException from its new location

/**
 * Custom Exception to carry generated OTP on failure.
 * REMOVED: This class definition has been moved to app/Exceptions/OtpSendException.php
 */
/*
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
*/


class MobileAuthController extends Controller
{
    // Constants for Rate Limiting (these can also be moved to config files)
    const OTP_SEND_RATE_LIMIT = 5;
    const OTP_SEND_COOLDOWN_MINUTES = 1;
    const OTP_VERIFY_RATE_LIMIT = 10;
    const OTP_VERIFY_COOLDOWN_MINUTES = 5;
    const OTP_IP_MAX_ATTEMPTS = 10;
    const OTP_IP_COOLDOWN_MINUTES = 60;

    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_';
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
        try {
            if (Auth::guard('api')->check()) {
                return redirect()->intended(route('dashboard'));
            }
        } catch (Throwable $e) {
            Log::debug('MobileAuthController: No valid JWT token found or token invalid, proceeding to login form: ' . $e->getMessage());
        }

        return view('auth.login');
    }

    /**
     * Show the OTP verification form.
     * نمایش فرم تأیید OTP.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function showOtpVerifyForm(Request $request): View|RedirectResponse
    {
        $mobileNumber = $request->query('mobile_number');

        if (empty($mobileNumber)) {
            return redirect()->route('auth.mobile-login-form')->withErrors(['mobile_number' => 'شماره موبایل برای تأیید کد یافت نشد. لطفاً دوباره وارد شوید.']);
        }

        $attemptCount = 0;

        return view('auth.verify-otp', compact('mobileNumber', 'attemptCount'));
    }


    /**
     * Send OTP to the provided mobile number.
     * ارسال کد OTP به شماره موبایل.
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
            // NEW LOGIC: Check if the user exists before attempting to send OTP
            $user = User::where('mobile_number', $mobileNumber)->first();

            if (!$user) {
                // User does not exist, return a JSON response indicating registration is required
                // This is specifically for AJAX calls from the login page.
                Log::info('MobileAuthController: Mobile number ' . maskForLog($mobileNumber, 'phone') . ' not found. Responding with requires_registration.');
                return response()->json([
                    'message' => 'این شماره در سیستم ثبت نشده است. لطفاً ابتدا ثبت‌نام کنید.',
                    'requires_registration' => true
                ], 404); // Using 404 Not Found as per user's suggestion for non-existent user
            }

            // User exists, proceed with sending OTP for login
            Log::debug('MobileAuthController: Calling otpService->sendOtpForMobile for mobile: ' . maskForLog($mobileNumber, 'phone'));

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

            // OTP sent successfully for an existing user
            return response()->json([
                'message' => 'کد تأیید با موفقیت ارسال شد.',
                'user_exists' => true // Indicate that user exists and OTP was sent for login
            ]);

        } catch (OtpSendException $e) {
            Log::error('MobileAuthController: OtpSendException caught for mobile: ' . maskForLog($mobileNumber, 'phone') . '. Error: ' . $e->getMessage());
            $generatedOtp = $e->getGeneratedOtp();
            $this->auditService->log(
                'otp_send_failed_exception',
                'Failed to send OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(),
                $request,
                array_merge($e->getTrace(), [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'),
                    'error' => $e->getMessage(),
                    'generated_otp' => $generatedOtp
                ]),
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
                array_merge($e->getTrace(), [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'),
                    'error' => $e->getMessage()
                ]),
                hashForCache($mobileNumber, 'audit_mobile_hash'),
                null, null, null, 'error'
            );

            return $this->respondWithError(
                'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.', // Generic error message for unexpected exceptions
                500,
                $request,
                'mobile_number'
            );
        }
    }

    /**
     * REMOVED: This method was creating users directly without OTP verification.
     * User registration (creation) should now happen exclusively within OtpService->verifyOtpForMobile
     * after successful OTP verification, leveraging the pending registration data.
     *
     * ثبت‌نام کاربر جدید.
     *
     * @param RegisterRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    /*
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $user = User::create([
                'name' => $validatedData['name'],
                'lastname' => $validatedData['lastname'] ?? null,
                'mobile_number' => $validatedData['mobile_number'],
                'profile_completed' => (!empty($validatedData['name']) && !empty($validatedData['lastname'])),
                'status' => 'active',
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
                $token = JWTAuth::fromUser($user);
                Log::info('MobileAuthController: JWT token generated for newly registered user ' . $user->id);

                return response()->json([
                    'message' => 'ثبت‌نام با موفقیت انجام شد و شما وارد شدید.',
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60
                ], 201);
            }

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
    */


    /**
     * Verify OTP and log in the user or redirect to registration.
     * تأیید OTP و ورود کاربر یا هدایت به ثبت‌نام.
     *
     * @param VerifyOtpRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function verifyOtpAndLogin(
        VerifyOtpRequest $request
    ): RedirectResponse|\Illuminate\Http\JsonResponse {
        $mobileNumber = $request->mobile_number;
        $otp = $request->otp;
        $ipAddress = $request->ip();
        $guestUuid = $request->header('X-Guest-UUID');

        // NEW: Get name and lastname from the request for existing users who might complete profile
        // توجه: این name و lastname از درخواست verifyOtpAndLogin می‌آیند، نه از کش ثبت‌نام.
        // برای کاربران جدید، نام و نام خانوادگی باید از کش pending_registration_data در OtpService دریافت شود.
        $name = $request->input('name');
        $lastName = $request->input('lastname');

        Log::debug('MobileAuthController: Starting verifyOtpAndLogin process for mobile: ' . maskForLog($mobileNumber, 'phone'));
        try {
            Log::debug('MobileAuthController: Calling otpService->verifyOtpForMobile for mobile: ' . maskForLog($mobileNumber, 'phone'));

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

            // Update user profile if name/lastname are provided and profile is not completed
            // این بخش برای به‌روزرسانی نام و نام خانوادگی کاربرانی است که قبلاً ثبت‌نام کرده‌اند
            // اما پروفایلشان کامل نیست و اطلاعات نام را در درخواست verifyOtpAndLogin ارسال می‌کنند.
            // برای کاربران جدید، نام باید در OtpService هنگام ایجاد کاربر تنظیم شود.
            $updated = false;
            if (empty($user->name) && !empty($name)) {
                $user->name = $name;
                $updated = true;
            }
            if (empty($user->lastname) && !empty($lastName)) {
                $user->lastname = $lastName;
                $updated = true;
            }

            // Update profile_completed based on current name and lastname status
            $user->profile_completed = (!empty($user->name) && !empty($user->lastname));

            if ($updated || $user->isDirty('profile_completed')) {
                $user->save();
                Log::info('MobileAuthController: User profile updated during OTP verification. User ID: ' . $user->id);
            }

            if (!empty($guestUuid)) {
                Log::info('MobileAuthController: Guest UUID found (' . $guestUuid . ') for user login. Attempting cart merge.');
                $this->cartService->assignGuestCartToUser($user, $guestUuid);
                Log::info('MobileAuthController: Cart merge initiated for user ' . $user->id . ' with guest UUID ' . $guestUuid);
            } else {
                Log::info('MobileAuthController: No guest_uuid found in header for user login, skipping cart merge.');
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

            if ($request->expectsJson()) {
                $token = JWTAuth::fromUser($user);
                Log::info('MobileAuthController: JWT token generated for user ' . $user->id);

                return response()->json([
                    'message' => 'ورود با موفقیت انجام شد.',
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ]);
            }

            return redirect()->intended('/');

        } catch (\Exception $e) {
            Log::error('MobileAuthController: Exception caught during verifyOtpAndLogin for mobile: ' . maskForLog($mobileNumber, 'phone') . '. Error: ' . $e->getMessage());
            $this->auditService->log(
                'otp_verify_failed_exception',
                'Failed to verify OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(),
                $request,
                array_merge($e->getTrace(), [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'),
                    'error' => $e->getMessage()
                ]),
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
     * Logs in the user to the Laravel web session using a valid JWT token.
     * این متد کاربر را با استفاده از یک توکن JWT معتبر، در سشن وب لاراول لاگین می‌کند.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function jwtLogin(Request $request)
    {
        // توکن را از بدنه درخواست (که توسط جاوااسکریپت ارسال می‌شود) دریافت کنید
        $token = $request->input('token');

        if (!$token) {
            Log::warning('MobileAuthController: jwtLogin called without token.');
            return response()->json(['message' => 'توکن یافت نشد.'], 400);
        }

        try {
            // توکن را اعتبارسنجی کرده و کاربر مربوطه را دریافت کنید
            // این کار JWTAuth::parseToken() و سپس authenticate() را انجام می‌دهد.
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                Log::warning('MobileAuthController: User not found for provided JWT token.');
                return response()->json(['message' => 'کاربر یافت نشد.'], 404);
            }

            // کاربر را در سشن وب لاراول لاگین کنید
            Auth::login($user);
            Log::info('MobileAuthController: User ' . $user->id . ' logged in to web session via JWT token.');

            return response()->json([
                'message' => 'ورود به سشن وب با موفقیت انجام شد.',
                'user' => $user->only(['id', 'name', 'lastname', 'mobile_number', 'profile_completed']) // فقط اطلاعات مورد نیاز را برگردانید
            ]);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            Log::warning('MobileAuthController: JWT token expired during jwtLogin for web session. Error: ' . $e->getMessage());
            return response()->json(['message' => 'توکن منقضی شده است. لطفاً دوباره وارد شوید.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            Log::warning('MobileAuthController: JWT token invalid during jwtLogin for web session. Error: ' . $e->getMessage());
            return response()->json(['message' => 'توکن نامعتبر است. لطفاً دوباره وارد شوید.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            Log::error('MobileAuthController: JWT exception during jwtLogin for web session. Error: ' . $e->getMessage());
            return response()->json(['message' => 'خطا در پردازش توکن احراز هویت. لطفاً دوباره تلاش کنید.'], 401);
        } catch (\Exception $e) {
            Log::error('MobileAuthController: Generic error during jwtLogin for web session: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطایی در ورود رخ داد. لطفاً دوباره تلاش کنید.'], 500);
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
            $user = Auth::guard('api')->user();
            if ($user) {
                $userId = $user->id;
                $mobileNumber = $user->mobile_number;
            }
        } catch (Throwable $e) {
            Log::debug('MobileAuthController: No JWT authenticated user during logout attempt: ' . $e->getMessage());
        }

        try {
            if (JWTAuth::getToken()) {
                JWTAuth::invalidate(JWTAuth::getToken());
                Log::info('MobileAuthController: JWT token invalidated for user ' . ($userId ?? 'N/A'));
            }
        } catch (Throwable $e) {
            Log::warning('MobileAuthController: Failed to invalidate JWT token during logout for user ' . ($userId ?? 'N/A') . ': ' . $e->getMessage());
        }

        // این خطوط مربوط به سشن وب لاراول هستند و برای مسیرهای API که از JWT استفاده می‌کنند،
        // معمولاً ضروری نیستند و می‌توانند باعث خطای "Session store not set on request." شوند.
        // بنابراین، این خطوط را حذف یا کامنت می‌کنیم.
        // Auth::guard('web')->logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();
        // Log::info('MobileAuthController: User logged out from web session.');


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

        if ($request->expectsJson()) {
            return response()->json(['message' => 'خروج با موفقیت انجام شد.']);
        }

        return redirect('/');
    }

    /**
     * Helper method to normalize mobile number (remove spaces, convert Persian/Arabic digits, ensure 09 prefix).
     * متد کمکی برای نرمال‌سازی شماره موبایل (حذف فواصل، تبدیل ارقام فارسی/عربی، اطمینان از پیشوند 09).
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
     * متد کمکی برای نرمال‌سازی ورودی OTP (حذف فواصل، تبدیل ارقام فارسی/عربی).
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
     * متد کمکی برای پاسخ با خطا، به صورت JSON یا ریدایرکت.
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
        $redirect = back()->withErrors([$errorField => $message])->withInput();
        if ($redirectRoute) {
            $redirect = redirect()->route($redirectRoute)->with('status', $message);
            if ($showRegisterLink) {
                $redirect->with('show_register_link', true);
            }
        }
        return $redirect;
    }

    // NEW: Add changeMobileNumber method if it's used
    public function changeMobileNumber(Request $request)
    {
        // This method needs to be implemented based on your specific logic
        // It would likely involve sending a new OTP to the new number,
        // verifying it, and then updating the user's mobile_number in the database.
        // For now, it's a placeholder to avoid "method not found" errors if called.
        Log::warning('MobileAuthController: changeMobileNumber method called but not fully implemented.');
        return response()->json(['message' => 'تغییر شماره موبایل در حال حاضر پشتیبانی نمی‌شود.'], 501);
    }
}
