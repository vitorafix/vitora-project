<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\MelipayamakSmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * نمایش فرم ثبت‌نام (نام، نام خانوادگی، شماره موبایل).
     *
     * @param \Illuminate\Http\Request $request
     * @return View
     */
    public function showRegistrationForm(\Illuminate\Http\Request $request): View
    {
        $mobileNumber = $request->session()->get('user_not_found_mobile') ?? $request->query('mobile_number');
        return view('auth.register', compact('mobileNumber'));
    }

    /**
     * ثبت‌نام کاربر جدید و ارسال کد OTP.
     *
     * @param RegisterRequest $request
     * @return JsonResponse|RedirectResponse
     */
    public function register(RegisterRequest $request)
    {
        $mobileNumber = $request->mobile_number;

        // ذخیره موقت اطلاعات ثبت‌نام در کش
        $registrationData = [
            'name' => $request->name,
            'lastname' => $request->lastname,
            'mobile_number' => $mobileNumber,
        ];
        Cache::put('pending_registration_' . $mobileNumber, $registrationData, now()->addMinutes(5));

        // تولید و ذخیره OTP
        $otp = random_int(100000, 999999);
        Cache::put('otp_' . $mobileNumber, $otp, now()->addMinutes(config('auth.otp.expiry_minutes', 2)));

        // ارسال پیامک
        $smsService = new MelipayamakSmsService();
        $patternCode = config('services.melipayamak.pattern_code');

        try {
            if ($patternCode) {
                $smsService->sendByPattern($mobileNumber, $patternCode, ['verification-code' => $otp]);
            } else {
                $smsService->send($mobileNumber, "کد تایید شما: {$otp}\nفروشگاه چای");
            }
        } catch (\Exception $e) {
            \Log::error('Error sending OTP during registration: ' . $e->getMessage(), [
                'mobile_number' => $mobileNumber,
                'exception' => $e,
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'], 500);
            }

            return back()->withErrors([
                'mobile_number' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.',
            ])->withInput();
        }

        // ذخیره شماره موبایل به‌صورت رمزگذاری‌شده در سشن
        $request->session()->put('mobile_number_for_otp', Crypt::encryptString($mobileNumber));

        // پاسخ
        if ($request->expectsJson()) {
            return response()->json(['message' => 'کد تایید به شماره شما ارسال شد.'], 200);
        }

        return redirect()
            ->route('auth.verify-otp-form', ['mobile_number' => $mobileNumber])
            ->with('status', 'کد تایید به شماره شما ارسال شد. لطفاً آن را وارد کنید.');
    }
}
