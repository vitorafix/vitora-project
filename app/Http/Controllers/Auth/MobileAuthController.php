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
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log; // Added for logging
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Throwable;

// Import new service classes and interfaces
use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface;
use App\Contracts\Services\AuditServiceInterface;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Requests\Auth\RegisterRequest;

// Add Spatie's Role class
use Spatie\Permission\Models\Role; // Add this line

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

    // Constants for Session & Cache Keys
    const SESSION_MOBILE_FOR_OTP = 'mobile_number_for_otp';
    const SESSION_MOBILE_FOR_REGISTRATION = 'mobile_number_for_registration';
    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_';
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10;

    // Dependency Injection for services
    protected OtpServiceInterface $otpService;
    protected RateLimitServiceInterface $rateLimitService;
    protected AuditServiceInterface $auditService;

    public function __construct(
        OtpServiceInterface $otpService,
        RateLimitServiceInterface $rateLimitService,
        AuditServiceInterface $auditService
    ) {
        $this->otpService = $otpService;
        $this->rateLimitService = $rateLimitService;
        $this->auditService = $auditService;
    }

    /**
     * Show the mobile login form.
     * نمایش فرم ورود با موبایل.
     *
     * @return View
     */
    public function showMobileLoginForm(): View
    {
        // مسئولیت کنترلر: رندر کردن ویو برای ورود با موبایل.
        // اگر کاربر از قبل احراز هویت شده است، او را به داشبورد هدایت کنید.
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
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
        // مسئولیت کنترلر: مدیریت وضعیت سشن برای رندر کردن ویو.
        $encryptedMobileNumber = $request->session()->get(self::SESSION_MOBILE_FOR_OTP);
        $mobileNumber = null;

        if (!$encryptedMobileNumber) {
            return redirect()->route('auth.mobile-login-form')->withErrors(['mobile_number' => 'شماره موبایل برای تأیید کد یافت نشد. لطفاً دوباره وارد شوید.']);
        }

        try {
            $mobileNumber = Crypt::decryptString($encryptedMobileNumber);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // استفاده از maskForLog برای لاگ کردن داده‌های حساس
            Log::error('Could not decrypt mobile number from session: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'mobile_masked' => maskForLog($mobileNumber ?? 'N/A', 'phone') // ماسک کردن شماره موبایل در صورت وجود
            ]);
            return redirect()->route('auth.mobile-login-form')->with('error', 'خطا در بازیابی شماره موبایل. لطفاً دوباره تلاش کنید.');
        }

        $attemptCount = $request->session()->get('otp_attempt_count', 0);

        // رندر کردن ویو تایید OTP.
        return view('auth.verify-otp', compact('mobileNumber', 'attemptCount')); // فرض بر این است که 'resources/views/auth/verify-otp.blade.php' وجود دارد.
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
        // شماره موبایل قبلاً توسط SendOtpRequest اعتبارسنجی و پاکسازی شده است.
        $mobileNumber = $request->mobile_number;
        $ipAddress = $request->ip();

        Log::debug('MobileAuthController: Starting sendOtp process for mobile: ' . maskForLog($mobileNumber, 'phone')); // Added debug log
        try {
            Log::debug('MobileAuthController: Calling otpService->sendOtpForMobile for mobile: ' . maskForLog($mobileNumber, 'phone')); // Added debug log

            // کنترلر منطق اصلی ارسال OTP را به OtpService واگذار می‌کند.
            // RateLimitService و یک auditLogger قابل فراخوانی را به سرویس ارسال کنید.
            $this->otpService->sendOtpForMobile(
                $mobileNumber,
                $ipAddress,
                $request->session(), // ارسال فقط شیء سشن
                $this->rateLimitService, // ارسال RateLimitService تزریق شده
                function ($message, $level = 'info', $userId = null, $userType = null, $objectId = null, $extra = []) use ($request, $mobileNumber, $ipAddress) {
                    // این closure به سرویس اجازه می‌دهد بدون وابندگی مستقیم به AuditService، لاگ‌های حسابرسی را ثبت کند.
                    // پارامتر $extra را به AuditService->log ارسال کنید.
                    $this->auditService->log(
                        'otp_send_event',
                        $message,
                        $request,
                        array_merge($extra, [ // ادغام extra ارسالی از OtpService با داده‌های کنترلر
                            'mobile_number_masked' => maskForLog($mobileNumber, 'phone'), // ماسک شده برای زمینه
                            'ip_address_masked' => maskForLog($ipAddress, 'ip') // ماسک شده برای زمینه
                        ]),
                        hashForCache($mobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                        $userId, $userType, $objectId, $level
                    );
                }
            );

            Log::debug('MobileAuthController: otpService->sendOtpForMobile completed successfully for mobile: ' . maskForLog($mobileNumber, 'phone')); // Added debug log

            // اگر فراخوانی سرویس موفقیت آمیز بود، پاسخ را بر اساس نوع درخواست تعیین کنید.
            // اگر درخواست از نوع AJAX/JSON باشد، پاسخ JSON برگردانده می‌شود.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'کد تأیید با موفقیت ارسال شد.']);
            }

            // NEW: Check if this is a new user registration flow
            if ($request->session()->has(self::SESSION_MOBILE_FOR_REGISTRATION)) {
                // Clear the registration session flag after checking
                $request->session()->forget(self::SESSION_MOBILE_FOR_REGISTRATION);
                Log::info('MobileAuthController: Redirecting new user to registration form.');
                return redirect()->route('auth.register-form', ['mobile_number' => $mobileNumber])
                                 ->with('status', 'شماره موبایل شما یافت نشد. لطفاً برای ادامه ثبت‌نام کنید.');
            } else {
                // Existing user or regular login flow, redirect to OTP verification
                Log::info('MobileAuthController: Redirecting existing user to OTP verification form.');
                return redirect()->route('auth.verify-otp-form')->with('status', 'کد تأیید با موفقیت ارسال شد.');
            }


        } catch (OtpSendException $e) { // Catch the custom exception
            Log::error('MobileAuthController: OtpSendException caught for mobile: ' . maskForLog($mobileNumber, 'phone') . '. Error: ' . $e->getMessage()); // Added debug log
            $generatedOtp = $e->getGeneratedOtp(); // Get the generated OTP from the exception
            $this->auditService->log(
                'otp_send_failed_exception',
                'Failed to send OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(), // ماسک شده در پیام
                $request,
                [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'), // ماسک شده برای زمینه
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'), // ماسک شده برای زمینه
                    'error' => $e->getMessage(),
                    'generated_otp' => $generatedOtp // Include generated OTP in the log
                ],
                hashForCache($mobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                null, null, null, 'error'
            );

            // از respondWithError helper برای مدیریت خطای سازگار استفاده کنید.
            return $this->respondWithError(
                $e->getMessage(), // استفاده از پیام استثنا برای بازخورد کاربر
                $e->getCode() ?: 500, // استفاده از کد استثنا یا پیش‌فرض 500
                $request,
                'mobile_number'
            );
        } catch (\Exception $e) { // Catch any other generic exceptions
            Log::error('MobileAuthController: Generic Exception caught for mobile: ' . maskForLog($mobileNumber, 'phone') . '. Error: ' . $e->getMessage()); // Added debug log
            // کنترلر استثنائات را از سرویس دریافت کرده و به طور مناسب پاسخ می‌دهد.
            // خطای حیاتی را از طریق AuditService ثبت کنید.
            // در اینجا، extra را از OtpService دریافت نمی‌کنیم، بنابراین فقط اطلاعات کنترلر را ارسال می‌کنیم.
            // OtpService خودش generated_otp را در لاگ خطای خود ثبت می‌کند.
            $this->auditService->log(
                'otp_send_failed_exception',
                'Failed to send OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(), // ماسک شده در پیام
                $request,
                [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'), // ماسک شده برای زمینه
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'), // ماسک شده برای زمینه
                    'error' => $e->getMessage()
                ],
                hashForCache($mobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                null, null, null, 'error'
            );

            // از respondWithError helper برای مدیریت خطای سازگار استفاده کنید.
            return $this->respondWithError(
                $e->getMessage(), // استفاده از پیام استثنا برای بازخورد کاربر
                $e->getCode() ?: 500, // استفاده از کد استثنا یا پیش‌فرض 500
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
        // داده‌ها قبلاً توسط RegisterRequest اعتبارسنجی و پاکسازی شده‌اند.
        $validatedData = $request->validated();

        try {
            // ایجاد کاربر
            $user = User::create([
                'name' => $validatedData['name'],
                'lastname' => $validatedData['lastname'] ?? null, // نام خانوادگی می‌تواند null باشد.
                'mobile_number' => $validatedData['mobile_number'],
                // می‌توانید یک رمز عبور پیش‌فرض اضافه کنید یا ایجاد رمز عبور را بعداً مدیریت کنید.
                // برای سیستم‌های مبتنی بر OTP، رمز عبور ممکن است در هنگام ثبت نام لازم نباشد.
                // 'password' => Hash::make(Str::random(10)), // مثال: تولید یک رمز عبور تصادفی
            ]);

            // Assign the 'user' role to the newly registered user
            // انتساب نقش 'user' به کاربر تازه ثبت نام شده
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
                Log::warning('Role "user" not found when registering user: ' . maskForLog($user->mobile_number, 'phone'));
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


            // ثبت رویداد ثبت نام
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

            // اگر ثبت نام موفقیت آمیز بود، می‌توانید:
            // 1. کاربر را بلافاصله وارد کنید: Auth::login($user);
            // 2. به صفحه تایید OTP برای شماره تازه ثبت نام شده هدایت کنید (توصیه می‌شود برای جریان‌های احراز هویت موبایل)
            //    شماره موبایل را در سشن برای تایید OTP ذخیره کنید.
            $request->session()->put(self::SESSION_MOBILE_FOR_OTP, Crypt::encryptString($user->mobile_number));
            $request->session()->put(self::SESSION_MOBILE_FOR_REGISTRATION, true); // نشان می‌دهد که این یک جریان ثبت نام است.

            if ($request->expectsJson()) {
                return response()->json(['message' => 'ثبت‌نام با موفقیت انجام شد. کد تأیید ارسال شد.'], 201);
            }

            return redirect()->route('auth.verify-otp-form')->with('status', 'ثبت‌نام شما با موفقیت انجام شد. کد تأیید برای شما ارسال گردید.');

        } catch (QueryException $e) {
            // مدیریت خطاهای خاص پایگاه داده، به عنوان مثال، ورودی تکراری اگر قانون unique به نوعی شکست بخورد یا شرایط رقابتی
            Log::error('Database error during registration: ' . $e->getMessage(), [
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
            // دریافت هرگونه استثنای عمومی دیگر
            Log::error('General error during registration: ' . $e->getMessage(), [
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
    public function verifyOtp(VerifyOtpRequest $request)
    {
        // شماره موبایل و OTP قبلاً توسط VerifyOtpRequest اعتبارسنجی و پاکسازی شده‌اند.
        $mobileNumber = $request->mobile_number;
        $otp = $request->otp;
        $ipAddress = $request->ip();

        Log::debug('MobileAuthController: Starting verifyOtp process for mobile: ' . maskForLog($mobileNumber, 'phone')); // Added debug log
        try {
            Log::debug('MobileAuthController: Calling otpService->verifyOtpForMobile for mobile: ' . maskForLog($mobileNumber, 'phone')); // Added debug log

            // کنترلر منطق تایید را به OtpService واگذار می‌کند.
            // RateLimitService و یک auditLogger قابل فراخوانی را به سرویس ارسال کنید.
            $user = $this->otpService->verifyOtpForMobile(
                $mobileNumber,
                $otp,
                $ipAddress,
                $request->session(), // ارسال فقط شیء سشن
                $this->rateLimitService, // ارسال RateLimitService تزریق شده
                function ($message, $level = 'info', $userId = null, $userType = null, $objectId = null, $extra = []) use ($request, $mobileNumber, $ipAddress) {
                    // این closure به سرویس اجازه می‌دهد لاگ‌های حسابرسی را ثبت کند.
                    // پارامتر $extra را به AuditService->log ارسال کنید.
                    $this->auditService->log(
                        'otp_verify_event',
                        $message,
                        $request,
                        array_merge($extra, [ // ادغام extra ارسالی از OtpService با داده‌های کنترلر
                            'mobile_number_masked' => maskForLog($mobileNumber, 'phone'), // ماسک شده برای زمینه
                            'ip_address_masked' => maskForLog($ipAddress, 'ip') // ماسک شده برای زمینه
                        ]),
                        hashForCache($mobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                        $userId, $userType, $objectId, $level
                    );
                }
            );

            Log::debug('MobileAuthController: otpService->verifyOtpForMobile completed successfully for mobile: ' . maskForLog($mobileNumber, 'phone')); // Added debug log

            // اگر تایید موفقیت آمیز بود، کاربر را وارد کنید.
            Auth::login($user, $request->boolean('remember'));

            // ثبت ورود کاربر از طریق سرویس حسابرسی.
            $this->auditService->log(
                'user_logged_in_via_otp',
                'User logged in via OTP: ' . maskForLog($mobileNumber, 'phone'), // ماسک شده در پیام
                $request,
                [
                    'user_id' => $user->id,
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone') // ماسک شده برای زمینه
                ],
                hashForCache($mobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                $user->id, 'User', $user->id, 'info'
            );

            // پاک کردن داده‌های سشن استفاده شده برای تایید OTP.
            $request->session()->forget(self::SESSION_MOBILE_FOR_OTP);
            $request->session()->forget(self::SESSION_MOBILE_FOR_REGISTRATION);

            // اگر درخواست از نوع AJAX/JSON باشد، پاسخ JSON برگردانده می‌شود.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'ورود با موفقیت انجام شد.']);
            }

            // هدایت به URL مورد نظر یا صفحه اصلی (root URL).
            return redirect()->intended('/');

        } catch (\Exception $e) {
            Log::error('MobileAuthController: Exception caught during verifyOtp for mobile: ' . maskForLog($mobileNumber, 'phone') . '. Error: ' . $e->getMessage()); // Added debug log
            // کنترلر استثنائات را از سرویس دریافت کرده و به طور مناسب پاسخ می‌دهد.
            // در اینجا، extra را از OtpService دریافت نمی‌کنیم، بنابراین فقط اطلاعات کنترلر را ارسال می‌کنیم.
            // OtpService خودش generated_otp را در لاگ خطای خود ثبت می‌کند.
            $this->auditService->log(
                'otp_verify_failed_exception',
                'Failed to verify OTP for mobile number: ' . maskForLog($mobileNumber, 'phone') . ' Error: ' . $e->getMessage(), // ماسک شده در پیام
                $request,
                [
                    'mobile_number_masked' => maskForLog($mobileNumber, 'phone'), // ماسک شده برای زمینه
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'), // ماسک شده برای زمینه
                    'error' => $e->getMessage()
                ],
                hashForCache($mobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                null, null, null, 'error'
            );

            return $this->respondWithError(
                $e->getMessage(),
                $e->getCode() ?: 401, // پیش‌فرض 401 برای خطاهای تایید
                $request,
                'otp'
            );
        }
    }

    /**
     * Handles the request to change the mobile number and send a new OTP.
     * مدیریت درخواست تغییر شماره موبایل و ارسال OTP جدید.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeMobileNumber(Request $request)
    {
        // 1. Validate the new mobile number
        // اعتبارسنجی شماره موبایل جدید (اینجا از FormRequest استفاده نشده، بنابراین اعتبارسنجی دستی انجام می‌شود)
        $validator = Validator::make($request->all(), [
            'new_mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'unique:users,mobile_number'],
        ], [
            'new_mobile_number.required' => 'شماره موبایل جدید الزامی است.',
            'new_mobile_number.regex' => 'فرمت شماره موبایل جدید صحیح نیست.',
            'new_mobile_number.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // پاکسازی و نرمال‌سازی شماره موبایل جدید
        // این متد normalizeMobileNumber باید از کنترلر حذف شود و به یک FormRequest جدید منتقل شود
        // یا در OtpService به عنوان یک متد private باقی بماند اگر فقط در آنجا استفاده می‌شود.
        // با توجه به اینکه SendOtpRequest و VerifyOtpRequest این منطق را دارند،
        // این متد در اینجا تکراری است و باید حذف شود.
        $newMobileNumber = $this->normalizeMobileNumber($request->input('new_mobile_number'));
        $ipAddress = $request->ip();

        Log::debug('MobileAuthController: Starting changeMobileNumber process for new mobile: ' . maskForLog($newMobileNumber, 'phone')); // Added debug log
        try {
            Log::debug('MobileAuthController: Calling otpService->sendOtpForMobile for new mobile: ' . maskForLog($newMobileNumber, 'phone')); // Added debug log

            // 2. Send a new OTP to the new mobile number
            // ارسال OTP جدید به شماره موبایل جدید
            // استفاده مجدد از منطق موجود sendOtp که محدودیت نرخ و حسابرسی را مدیریت می‌کند.
            // OtpService::sendOtpForMobile شماره موبایل جدید را در سشن برای تایید OTP قرار می‌دهد.
            $this->otpService->sendOtpForMobile(
                $newMobileNumber,
                $ipAddress,
                $request->session(), // ارسال فقط شیء سشن
                $this->rateLimitService,
                function ($message, $level = 'info', $userId = null, $userType = null, $objectId = null, $extra = []) use ($request, $newMobileNumber, $ipAddress) {
                    $this->auditService->log(
                        'otp_send_event_new_mobile',
                        $message,
                        $request,
                        array_merge($extra, [ // ادغام extra ارسالی از OtpService با داده‌های کنترلر
                            'mobile_number_masked' => maskForLog($newMobileNumber, 'phone'), // ماسک شده برای زمینه
                            'ip_address_masked' => maskForLog($ipAddress, 'ip') // ماسک شده برای زمینه
                        ]),
                        hashForCache($newMobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                        $userId, $userType, $objectId, $level
                    );
                }
            );

            Log::debug('MobileAuthController: otpService->sendOtpForMobile completed successfully for new mobile: ' . maskForLog($newMobileNumber, 'phone')); // Added debug log

            // 3. If successful, respond with success message
            // در صورت موفقیت، پاسخ با پیام موفقیت
            // اگر درخواست از نوع AJAX/JSON باشد، پاسخ JSON برگردانده می‌شود.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'شماره موبایل با موفقیت تغییر یافت و کد جدید ارسال شد.']);
            }
            // در غیر این صورت، نیازی به ریدایرکت نیست زیرا این متد فقط برای AJAX استفاده می‌شود.
            // اگر این متد برای فرم‌های سنتی هم استفاده شود، باید ریدایرکت مناسب اضافه شود.
            return response()->json(['message' => 'شماره موبایل با موفقیت تغییر یافت و کد جدید ارسال شد.']); // Fallback JSON response

        } catch (OtpSendException $e) { // Catch the custom exception
            Log::error('MobileAuthController: OtpSendException caught during changeMobileNumber for new mobile: ' . maskForLog($newMobileNumber, 'phone') . '. Error: ' . $e->getMessage()); // Added debug log
            $generatedOtp = $e->getGeneratedOtp(); // Get the generated OTP from the exception
            $this->auditService->log(
                'change_mobile_number_failed_exception',
                'Failed to change mobile number: ' . maskForLog($newMobileNumber, 'phone') . ' Error: ' . $e->getMessage(), // ماسک شده در پیام
                $request,
                [
                    'mobile_number_masked' => maskForLog($newMobileNumber, 'phone'), // ماسک شده برای زمینه
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'), // ماسک شده برای زمینه
                    'error' => $e->getMessage(),
                    'generated_otp' => $generatedOtp // Include generated OTP in the log
                ],
                hashForCache($newMobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                null, null, null, 'error'
            );

            // پاسخ با پیام خطا
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        } catch (\Exception $e) { // Catch any other generic exceptions
            Log::error('MobileAuthController: Generic Exception caught during changeMobileNumber for new mobile: ' . maskForLog($newMobileNumber, 'phone') . '. Error: ' . $e->getMessage()); // Added debug log
            // ثبت خطا
            // در اینجا، extra را از OtpService دریافت نمی‌کنیم، بنابراین فقط اطلاعات کنترلر را ارسال می‌کنیم.
            // OtpService خودش generated_otp را در لاگ خطای خود ثبت می‌کند.
            $this->auditService->log(
                'change_mobile_number_failed_exception',
                'Failed to change mobile number: ' . maskForLog($newMobileNumber, 'phone') . ' Error: ' . $e->getMessage(), // ماسک شده در پیام
                $request,
                [
                    'mobile_number_masked' => maskForLog($newMobileNumber, 'phone'), // ماسک شده برای زمینه
                    'ip_address_masked' => maskForLog($ipAddress, 'ip'), // ماسک شده برای زمینه
                    'error' => $e->getMessage()
                ],
                hashForCache($newMobileNumber, 'audit_mobile_hash'), // استفاده از hashForCache برای شناسه
                null, null, null, 'error'
            );

            // پاسخ با پیام خطا
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }


    /**
     * Log the user out of the application.
     * خروج کاربر از برنامه.
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
            [
                'user_id' => $userId,
                'mobile_number_masked' => maskForLog($mobileNumber ?? 'N/A', 'phone') // ماسک شده برای زمینه
            ],
            $mobileNumber ? hashForCache($mobileNumber, 'audit_mobile_hash') : null, // استفاده از hashForCache برای شناسه
            $userId, 'User', $userId, 'info'
        );

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
            Log::warning('Invalid mobile number after normalization: ' . maskForLog($mobileNumber, 'phone') . ' -> ' . maskForLog($normalizedNumber, 'phone')); // استفاده از maskForLog
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
