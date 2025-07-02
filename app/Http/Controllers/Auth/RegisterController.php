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

class RegisterController extends Controller
{
    /**
     * نمایش فرم ثبت‌نام (نام، نام خانوادگی، شماره موبایل).
     * این فرم هم برای کاربران جدیدی که مستقیماً به صفحه ثبت‌نام می‌آیند
     * و هم برای کاربرانی که از MobileAuthController هدایت می‌شوند (شماره موبایل در سشن است) نمایش داده می‌شود.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showRegistrationForm(Request $request): View|RedirectResponse
    {
        // تلاش برای دریافت شماره موبایل از سشن (اگر از MobileAuthController هدایت شده باشد)
        $mobileNumberFromSession = $request->session()->get('new_registration_mobile');

        // اگر شماره موبایل از سشن موجود باشد، آن را به ویو ارسال می‌کنیم.
        // در غیر این صورت، فیلد شماره موبایل در ویو قابل ویرایش خواهد بود.
        return view('auth.register', ['mobileNumber' => $mobileNumberFromSession]);
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
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'digits:11', 'unique:users'],
            // ایمیل در این مرحله حذف شد
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;

        // ذخیره موقت اطلاعات ثبت‌نام در سشن/کش قبل از ارسال OTP
        // این اطلاعات پس از تایید OTP در MobileAuthController استفاده خواهند شد.
        $registrationData = [
            'name' => $request->name,
            'lastname' => $request->lastname,
            'mobile_number' => $mobileNumber,
            // 'email' => $request->email, // اگر ایمیل را اضافه کردید، اینجا ذخیره کنید
        ];
        Cache::put('pending_registration_' . $mobileNumber, $registrationData, now()->addMinutes(5)); // ذخیره به مدت 5 دقیقه

        $otp = random_int(100000, 999999); // تولید یک کد 6 رقمی
        $cacheKey = 'otp_' . $mobileNumber;

        // ذخیره OTP در کش به مدت 2 دقیقه
        Cache::put($cacheKey, $otp, now()->addMinutes(2));

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
            if ($request->expectsJson()) {
                return response()->json(['message' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'], 500);
            }
            return back()->withErrors(['mobile_number' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'کد تایید به شماره شما ارسال شد.'], 200);
        }

        // ذخیره شماره موبایل در سشن برای استفاده در صفحه تایید OTP
        $request->session()->put('mobile_number_for_otp', $mobileNumber);
        return redirect()->route('auth.verify-otp-form')->with('status', 'کد تایید به شماره شما ارسال شد.');
    }
}

