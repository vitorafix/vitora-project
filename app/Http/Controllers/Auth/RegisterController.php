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
// اگر از Form Request استفاده می‌کنید، آن را اینجا ایمپورت کنید
// use App\Http\Requests\RegisterRequest;

class RegisterController extends Controller
{
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
        $mobileNumber = $request->session()->get('user_not_found_mobile');
        
        // اگر شماره موبایل از سشن نیامده بود، تلاش می‌کنیم از پارامتر URL بگیریم (برای لینک مستقیم ثبت‌نام از mobile-login)
        if (empty($mobileNumber)) {
            $mobileNumber = $request->query('mobile_number');
        }

        // شماره موبایل را به ویو ارسال می‌کنیم.
        return view('auth.register', compact('mobileNumber'));
    }

    /**
     * ثبت‌نام کاربر جدید با نام، نام خانوادگی و شماره موبایل و ارسال OTP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request)
    {
        // اعتبارسنجی داده‌ها
        // اگر از RegisterRequest استفاده می‌کنید، این بخش را حذف کنید و RegisterRequest را در امضای متد قرار دهید.
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'digits:11', 'unique:users'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;

        // ذخیره موقت اطلاعات ثبت‌نام در کش قبل از ارسال OTP
        // این اطلاعات پس از تایید OTP در MobileAuthController@verifyOtp استفاده خواهند شد.
        $registrationData = [
            'name' => $request->name,
            'lastname' => $request->lastname,
            'mobile_number' => $mobileNumber,
        ];
        Cache::put('pending_registration_' . $mobileNumber, $registrationData, now()->addMinutes(5)); // ذخیره به مدت 5 دقیقه

        $otp = random_int(100000, 999999); // تولید یک کد 6 رقمی
        $cacheKey = 'otp_' . $mobileNumber;

        // ذخیره OTP در کش به مدت 2 دقیقه (یا هر مدت زمان دیگری که در config/auth.php تنظیم کرده‌اید)
        Cache::put($cacheKey, $otp, now()->addMinutes(config('auth.otp.expiry_minutes', 2)));

        // استفاده از سرویس MelipayamakSmsService برای ارسال پیامک
        $smsService = new MelipayamakSmsService();
        $patternCode = config('services.melipayamak.pattern_code');

        try {
            if ($patternCode) {
                $smsService->sendByPattern($mobileNumber, $patternCode, ['verification-code' => $otp]);
            } else {
                $smsService->send($mobileNumber, "کد تایید شما: " . $otp . "\nفروشگاه چای");
            }
        } catch (\Exception $e) {
            // لاگ کردن خطا برای اشکال‌زدایی
            \Log::error('Error sending OTP during registration: ' . $e->getMessage(), [
                'mobile_number' => $mobileNumber,
                'exception' => $e
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'], 500);
            }
            return back()->withErrors(['mobile_number' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'کد تایید به شماره شما ارسال شد.'], 200);
        }

        // تغییر مهم: ذخیره شماره موبایل به صورت رمزگذاری شده در سشن
        $request->session()->put('mobile_number_for_otp', Crypt::encryptString($mobileNumber));
        
        // هدایت کاربر به صفحه تأیید OTP
        return redirect()->route('auth.verify-otp-form', ['mobile_number' => $mobileNumber])
                         ->with('status', 'کد تایید به شماره شما ارسال شد. لطفاً آن را وارد کنید.');
    }
}
