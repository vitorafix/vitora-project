<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse; // ایمپورت RedirectResponse
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Services\MelipayamakSmsService; // ایمپورت کردن سرویس جدید
use Illuminate\View\View; // ایمپورت View

class MobileAuthController extends Controller
{
    /**
     * نمایش فرم ورود/ثبت‌نام با شماره موبایل.
     *
     * @return \Illuminate\View\View
     */
    public function showMobileLoginForm(): \Illuminate\View\View
    {
        return view('auth.mobile-login');
    }

    /**
     * ارسال کد تأیید (OTP) به شماره موبایل.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'digits:11'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;
        $otp = random_int(100000, 999999); // تولید یک کد 6 رقمی
        $cacheKey = 'otp_' . $mobileNumber;

        // ذخیره OTP در کش به مدت 2 دقیقه
        Cache::put($cacheKey, $otp, now()->addMinutes(2));

        // استفاده از سرویس جدید MelipayamakSmsService برای ارسال پیامک
        $smsService = new MelipayamakSmsService();
        $patternCode = config('services.melipayamak.pattern_code'); // از فایل config/services.php دریافت می شود

        if ($patternCode) {
            // اگر کد پترن تعریف شده بود، از sendByPattern استفاده کن
            $smsService->sendByPattern($mobileNumber, $patternCode, ['verification-code' => $otp]);
            // 'verification-code' باید نام پارامتر تعریف شده در پترن شما در پنل ملی پیامک باشد
        } else {
            // در غیر این صورت، پیامک ساده ارسال کن
            $smsService->send($mobileNumber, "کد تایید شما: " . $otp . "\nفروشگاه چای");
        }


        if ($request->expectsJson()) {
            return response()->json(['message' => 'کد تایید به شماره شما ارسال شد.'], 200);
        }

        return redirect()->route('auth.verify-otp-form')->with('mobile_number', $mobileNumber)->with('status', 'کد تایید به شماره شما ارسال شد.');
    }

    /**
     * نمایش فرم تأیید کد (OTP).
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showVerifyOtpForm(Request $request): View|RedirectResponse
    {
        $mobileNumber = $request->session()->get('mobile_number');
        if (!$mobileNumber) {
            return redirect()->route('auth.mobile-login-form');
        }
        return view('auth.verify-otp', compact('mobileNumber'));
    }

    /**
     * تأیید کد تأیید (OTP) و ورود/ثبت‌نام کاربر.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'digits:11'],
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;
        $otp = $request->otp;
        $cacheKey = 'otp_' . $mobileNumber;

        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || $storedOtp != $otp) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'کد تایید اشتباه یا منقضی شده است.'], 400);
            }
            throw ValidationException::withMessages([
                'otp' => ['کد تایید اشتباه یا منقضی شده است.'],
            ]);
        }

        Cache::forget($cacheKey);

        $user = User::firstOrCreate(
            ['mobile_number' => $mobileNumber],
            [
                'name' => 'کاربر ' . Str::random(5), // اضافه کردن یک نام پیش‌فرض تصادفی
                'password' => Hash::make(Str::random(10)),
                'email' => $mobileNumber . '@example.com', // اضافه کردن یک ایمیل پیش‌فرض ساختگی
                'email_verified_at' => now(), // می توانید این را نیز تنظیم کنید
            ]
        );

        Auth::login($user);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'ورود با موفقیت انجام شد.', 'user' => $user], 200);
        }

        if (!$user->profile_completed) {
            return redirect()->route('profile.complete');
        }

        return redirect()->intended('/')->with('status', 'ورود با موفقیت انجام شد.');
    }

    /**
     * خروج کاربر.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
