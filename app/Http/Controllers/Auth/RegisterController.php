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

    // اگر سرویس‌ها را در اینجا تزریق می‌کنید، مطمئن شوید که در constructor انجام شود.
    // protected $otpService;
    // protected $auditService;
    // public function __construct(OtpServiceInterface $otpService, AuditServiceInterface $auditService)
    // {
    //     $this->otpService = $otpService;
    //     $this->auditService = $auditService;
    // }

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
        // تلاش برای دریافت شماره موبایل از فلش سشن (اگر از MobileAuthController هدایت شده باشد)
        // REMOVED: No longer relying on session for mobile number in registration flow
        // $mobileNumber = $request->session()->get('user_not_found_mobile');

        // اگر شماره موبایل از سشن نیامده بود، تلاش می‌کنیم از پارامتر URL بگیریم (برای لینک مستقیم ثبت‌نام از mobile-login)
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

        // تولید کلید کش برای اطلاعات ثبت‌نام در حال انتظار (با استفاده از هش)
        $pendingRegistrationCacheKey = self::CACHE_PENDING_REGISTRATION_PREFIX . hashForCache($mobileNumber, 'pending_reg_cache_key');

        // ذخیره موقت اطلاعات ثبت‌نام در کش قبل از ارسال OTP
        // این اطلاعات پس از تایید OTP در MobileAuthController@verifyOtpAndLogin استفاده خواهند شد.
        $registrationData = [
            'name' => $validatedData['name'],
            'lastname' => $validatedData['lastname'] ?? null,
            'mobile_number' => $mobileNumber,
        ];
        // ذخیره با کلید هش شده
        Cache::put($pendingRegistrationCacheKey, json_encode($registrationData), now()->addMinutes(self::PENDING_REGISTRATION_CACHE_TTL_MINUTES));
        Log::debug('RegisterController: Stored pending registration data in cache with key: ' . $pendingRegistrationCacheKey . ' and data: ' . json_encode($registrationData));


        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT); // تولید یک کد 6 رقمی
        // کلید کش OTP باید با OtpService سازگار باشد
        $otpCacheKey = 'otp_' . preg_replace('/[^0-9]/', '', $mobileNumber); // کلید ساده برای OTP

        // ذخیره OTP در کش به مدت 2 دقیقه
        // توجه: این بخش باید با منطق OtpService::generateAndStoreOtp هماهنگ باشد.
        // در حالت ایده‌آل، ارسال OTP باید از طریق OtpService انجام شود تا منطق تکراری نباشد.
        // برای فعلاً، ما این را با فرض اینکه OtpService از این کلید استفاده می‌کند، هماهنگ می‌کنیم.
        $otpData = [
            'otp' => $otp,
            'timestamp' => time(),
            'mobile_hash' => hashForCache($mobileNumber, 'otp_data_hash')
        ];
        Cache::put($otpCacheKey, Crypt::encryptString(json_encode($otpData)), now()->addMinutes(self::OTP_EXPIRY_MINUTES));
        Log::debug('RegisterController: Stored OTP in cache with key: ' . $otpCacheKey . ' and encrypted data.');


        // استفاده از سرویس MelipayamakSmsService برای ارسال پیامک
        $smsService = new MelipayamakSmsService();
        $patternCode = config('services.melipayamak.pattern_code');

        try {
            if ($patternCode) {
                $smsService->sendByPattern($mobileNumber, $patternCode, ['verification-code' => $otp]);
            } else {
                $smsService->send($mobileNumber, "کد تایید شما: " . $otp . "\nفروشگاه چای");
            }
            Log::info("RegisterController: SMS sent to {$mobileNumber} with OTP: {$otp}");
        } catch (\Exception $e) {
            // لاگ کردن خطا برای اشکال‌زدایی
            Log::error('Error sending OTP during registration: ' . $e->getMessage(), [
                'mobile_number' => maskForLog($mobileNumber, 'phone'),
                'exception' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'], 500);
            }
            return back()->withErrors(['mobile_number' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'کد تایید به شماره شما ارسال شد.'], 200);
        }

        // REMOVED: Session-based storage for mobile number and registration flag
        // این خطوط حذف شدند زیرا ما دیگر از سشن برای این منظور استفاده نمی‌کنیم.
        // $request->session()->put(MobileAuthController::SESSION_MOBILE_FOR_OTP, Crypt::encryptString($mobileNumber));
        // $request->session()->put(MobileAuthController::SESSION_MOBILE_FOR_REGISTRATION, true);

        // هدایت کاربر به صفحه تأیید OTP
        // شماره موبایل از طریق query parameter ارسال می‌شود.
        return redirect()->route('auth.verify-otp-form', ['mobile_number' => $mobileNumber])
                         ->with('status', 'کد تایید به شماره شما ارسال شد. لطفاً آن را وارد کنید.');
    }
}
