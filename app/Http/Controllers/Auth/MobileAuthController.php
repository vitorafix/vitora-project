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
use Illuminate\Support\Str;
use App\Services\MelipayamakSmsService;
use Illuminate\View\View;

class MobileAuthController extends Controller
{
    /**
     * نمایش فرم ورود فقط با شماره موبایل.
     *
     * @return \Illuminate\View\View
     */
    public function showMobileLoginForm(): View
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

    /**
     * نمایش فرم تأیید کد (OTP).
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showVerifyOtpForm(Request $request): View|RedirectResponse
    {
        // شماره موبایل را از سشن دریافت می‌کنیم، نه از درخواست مستقیم
        $mobileNumber = $request->session()->get('mobile_number_for_otp');
        if (!$mobileNumber) {
            // اگر شماره موبایل در سشن نبود، کاربر را به صفحه ورود هدایت می‌کنیم
            return redirect()->route('auth.mobile-login-form')->withErrors(['mobile_number' => 'ابتدا شماره موبایل خود را وارد کنید.']);
        }
        return view('auth.verify-otp', compact('mobileNumber'));
    }

    /**
     * تأیید کد تأیید (OTP) و ورود/ثبت‌نام کاربر.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verifyOtp(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
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

        // کد OTP با موفقیت تایید شد، آن را از کش حذف می‌کنیم.
        Cache::forget($cacheKey);

        // بررسی می‌کنیم که آیا کاربری با این شماره موبایل وجود دارد یا خیر
        $user = User::where('mobile_number', $mobileNumber)->first();

        if ($user) {
            // کاربر موجود است، او را لاگین می‌کنیم.
            Auth::login($user);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'ورود با موفقیت انجام شد.', 'user' => $user], 200);
            }

            // اگر پروفایل کامل است یا قرار نیست بلافاصله به تکمیل پروفایل هدایت شود،
            // کاربر را به مسیر اصلی یا مقصد قبلی هدایت می‌کنیم.
            // منطق هدایت به تکمیل پروفایل توسط میدل‌ور در مسیرهای حساس انجام خواهد شد.
            return redirect()->intended('/')->with('status', 'ورود با موفقیت انجام شد.');

        } else {
            // کاربر جدید است. به صفحه ورود با موبایل برمی‌گردیم و پیام مناسب را نمایش می‌دهیم.
            // شماره موبایل را در سشن موقت نگه می‌داریم تا در صورت کلیک روی لینک ثبت‌نام،
            // به صورت خودکار در فرم ثبت‌نام پر شود.
            $request->session()->flash('user_not_found_mobile', $mobileNumber); // Flash mobile number
            if ($request->expectsJson()) {
                return response()->json(['message' => 'کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.'], 404);
            }
            // Redirect back to the mobile login form with a specific status
            return redirect()->route('auth.mobile-login-form')->with('status', 'کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.')->with('show_register_link', true);
        }
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

