<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Str;
use App\Services\MelipayamakSmsService; // ایمپورت کردن سرویس پیامک
use Illuminate\Support\Facades\Crypt; // اضافه شده: ایمپورت کردن کلاس Crypt برای رمزگذاری
use Illuminate\Support\Facades\Log; // اضافه شده برای لاگ
use App\Http\Requests\Auth\RegisterRequest; // ایمپورت کردن RegisterRequest

// NEW: Import OtpServiceInterface and AuditServiceInterface
use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\AuditServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface; // NEW: Import RateLimitServiceInterface
use App\Exceptions\OtpSendException; // NEW: Import OtpSendException from its new location

// Fallback helper functions - ensure these are available globally or defined here
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

class RegisterController extends Controller
{
    // Constants for Session & Cache Keys (consistent with OtpService)
    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_';
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10;
    const OTP_EXPIRY_MINUTES = 2; // Consistent with OtpService

    // Dependency Injection for services
    protected OtpServiceInterface $otpService;
    protected AuditServiceInterface $auditService;
    protected RateLimitServiceInterface $rateLimitService; // NEW: Add RateLimitService

    public function __construct(
        OtpServiceInterface $otpService,
        AuditServiceInterface $auditService,
        RateLimitServiceInterface $rateLimitService // NEW: Inject RateLimitService
    ) {
        $this->otpService = $otpService;
        $this->auditService = $auditService;
        $this->rateLimitService = $rateLimitService; // NEW: Assign RateLimitService
    }

    /**
     * نمایش فرم ثبت‌نام (نام، نام خانوادگی، شماره موبایل).
     * این فرم هم برای کاربران جدیدی که مستقیماً به صفحه ثبت‌نام می‌آیند
     * و هم برای کاربرانی که از MobileAuthController هدایت می‌شوند (شماره موبایل در سشن است) نمایش داده می‌شود.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm(Request $request): View
    {
        // UPDATED: Always try to get from query parameter first
        $mobileNumber = $request->query('mobile_number');

        // شماره موبایل را به ویو ارسال می‌کنیم.
        return view('auth.register', compact('mobileNumber'));
    }

    /**
     * ثبت‌نام کاربر جدید با نام، نام خانوادگی و شماره موبایل و ارسال OTP.
     *
     * @param  \App\Http\Requests\Auth\RegisterRequest  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(RegisterRequest $request)
    {
        // داده‌ها قبلاً توسط RegisterRequest اعتبارسنجی و پاکسازی شده‌اند.
        $validatedData = $request->validated();

        $mobileNumber = $validatedData['mobile_number'];
        $ipAddress = $request->ip(); // Get IP address for rate limiting and auditing

        // NEW: Check if a user with this mobile number already exists
        $existingUser = User::where('mobile_number', $mobileNumber)->first();
        if ($existingUser) {
            Log::warning('RegisterController: Attempted registration for existing mobile number: ' . maskForLog($mobileNumber, 'phone'));
            if ($request->expectsJson()) {
                return response()->json(['message' => 'این شماره موبایل قبلاً ثبت‌نام شده است. لطفاً وارد شوید.'], 409); // 409 Conflict
            }
            return back()->withErrors(['mobile_number' => 'این شماره موبایل قبلاً ثبت‌نام شده است. لطفاً وارد شوید.'])->withInput();
        }


        // تولید کلید کش برای اطلاعات ثبت‌نام در حال انتظار (با استفاده از هش)
        // این بخش برای ذخیره نام و نام خانوادگی کاربر جدید قبل از تایید OTP است.
        $pendingRegistrationCacheKey = self::CACHE_PENDING_REGISTRATION_PREFIX . hashForCache($mobileNumber, 'pending_reg_cache_key');

        $registrationData = [
            'name' => $validatedData['name'],
            'lastname' => $validatedData['lastname'] ?? null,
            'mobile_number' => $mobileNumber,
        ];
        // ذخیره با کلید هش شده
        Cache::put($pendingRegistrationCacheKey, json_encode($registrationData), now()->addMinutes(self::PENDING_REGISTRATION_CACHE_TTL_MINUTES));
        Log::debug('RegisterController: Stored pending registration data in cache with key: ' . $pendingRegistrationCacheKey . ' and data: ' . json_encode($registrationData));


        // --- تغییر کلیدی: استفاده از OtpService برای ارسال OTP ---
        try {
            Log::info('RegisterController: Calling otpService->sendOtpForMobile for new registration: ' . maskForLog($mobileNumber, 'phone'));
            $this->otpService->sendOtpForMobile(
                $mobileNumber,
                $ipAddress,
                $this->rateLimitService, // Pass RateLimitService
                function ($message, $level = 'info', $userId = null, $userType = null, $objectId = null, $extra = []) use ($request, $mobileNumber, $ipAddress) {
                    $this->auditService->log(
                        'otp_send_event_registration', // Changed event type for clarity
                        $message,
                        $request,
                        array_merge($extra, [
                            'mobile_number_masked' => maskForLog($mobileNumber, 'phone'),
                            'ip_address_masked' => maskForLog($ipAddress, 'ip')
                        ]),
                        hashForCache($mobileNumber, 'audit_mobile_hash'),
                        $userId, $userType, $objectId, $level
                    );
                },
                $registrationData // NEW: Pass the registrationData array here
            );
            Log::info("RegisterController: OTP sent via OtpService to {$mobileNumber}");
        } catch (OtpSendException $e) {
            Log::error('RegisterController: OtpSendException caught during registration OTP send: ' . $e->getMessage(), [
                'mobile_number' => maskForLog($mobileNumber, 'phone'),
                'exception' => $e->getTraceAsString(),
                'generated_otp' => $e->getGeneratedOtp() // Log generated OTP from exception
            ]);
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
            }
            return back()->withErrors(['mobile_number' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            Log::error('RegisterController: Generic error sending OTP during registration: ' . $e->getMessage(), [
                'mobile_number' => maskForLog($mobileNumber, 'phone'),
                'exception' => $e->getTraceAsString()
            ]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'], 500);
            }
            return back()->withErrors(['mobile_number' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'])->withInput();
        }
        // --- پایان تغییر کلیدی ---


        if ($request->expectsJson()) {
            return response()->json(['message' => 'کد تایید به شماره شما ارسال شد.'], 200);
        }

        // هدایت کاربر به صفحه تأیید OTP
        // شماره موبایل از طریق query parameter ارسال می‌شود.
        return redirect()->route('auth.verify-otp-form', ['mobile_number' => $mobileNumber])
                         ->with('status', 'کد تایید به شماره شما ارسال شد. لطفاً آن را وارد کنید.');
    }
}
