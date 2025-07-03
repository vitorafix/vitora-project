<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Services\MelipayamakSmsService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Session;
use App\Services\CartService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash; // اضافه شد: برای هش کردن OTP (اما دیگر برای OTP استفاده نمی‌شود، فقط برای سایر هش‌ها)

class MobileAuthController extends Controller
{
    // حداکثر تعداد تلاش برای ارسال OTP در یک بازه زمانی مشخص
    const MAX_ATTEMPTS = 3;
    // مدت زمان خنک شدن (cooldown) برای تلاش‌های OTP بر حسب دقیقه
    const ATTEMPT_COOLDOWN_MINUTES = 15;
    // مدت زمان انقضای OTP بر حسب دقیقه
    const OTP_EXPIRY_MINUTES = 2;

    // تزریق وابستگی CartService
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * نمایش فرم ورود فقط با شماره موبایل.
     *
     * @return \Illuminate\View\View
     */
    public function showMobileLoginForm(): View
    {
        return view('auth.login');
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
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'size:11'], // تغییر از digits به size
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for sendOtp', ['errors' => $validator->errors()->toArray()]);
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;
        $attemptsCacheKey = 'sms_attempts_' . $mobileNumber;

        // بررسی تعداد تلاش‌های اخیر برای این شماره موبایل
        $attempts = Cache::get($attemptsCacheKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Log::warning('Too many OTP requests for mobile number', ['mobile' => $mobileNumber, 'attempts' => $attempts]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً پس از ' . self::ATTEMPT_COOLDOWN_MINUTES . ' دقیقه دیگر تلاش کنید.'], 429);
            }
            return back()->withErrors(['mobile_number' => 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً پس از ' . self::ATTEMPT_COOLDOWN_MINUTES . ' دقیقه دیگر تلاش کنید.'])->withInput();
        }

        $otp = random_int(100000, 999999);
        // کلید کش OTP را به صورت ساده‌تر تولید می‌کنیم تا با پیشوند داخلی لاراول هماهنگ باشد
        $otpCacheKey = 'otp_' . $mobileNumber; // تغییر اینجا
        
        // ذخیره OTP به صورت متن ساده در کش (بدون هش کردن با Hash::make)
        Cache::put($otpCacheKey, $otp, now()->addMinutes(self::OTP_EXPIRY_MINUTES));
        
        // --- شروع لاگ‌های اضافه شده برای دیباگ ---
        Log::debug('OTP generated and cached', [
            'mobile' => $mobileNumber,
            'otp_cache_key' => $otpCacheKey, // لاگ کردن کلید کش
            'expiry_time' => now()->addMinutes(self::OTP_EXPIRY_MINUTES)->toDateTimeString() // لاگ کردن زمان انقضا
        ]);
        // --- پایان لاگ‌های اضافه شده برای دیباگ ---

        $smsService = new MelipayamakSmsService();
        $patternCode = config('services.melipayamak.pattern_code');

        try {
            if ($patternCode) {
                $smsService->sendByPattern($mobileNumber, $patternCode, ['verification-code' => $otp]);
            } else {
                $smsService->send($mobileNumber, "کد تایید شما: " . $otp . "\nفروشگاه چای");
            }
            Log::info('OTP SMS sent successfully', ['mobile' => $mobileNumber]);

            // مدیریت TTL برای شمارنده تلاش‌ها: اگر اولین تلاش است، TTL را تنظیم کن، در غیر این صورت فقط افزایش بده.
            if ($attempts === 0) {
                Cache::put($attemptsCacheKey, 1, now()->addMinutes(self::ATTEMPT_COOLDOWN_MINUTES));
                Log::info('OTP attempt count initialized with TTL', ['mobile' => $mobileNumber, 'new_attempts' => 1]);
            } else {
                Cache::increment($attemptsCacheKey);
                Log::info('OTP attempt count incremented', ['mobile' => $mobileNumber, 'new_attempts' => $attempts + 1]);
            }

        } catch (\Exception $e) {
            Log::error('Error sending OTP SMS', ['mobile' => $mobileNumber, 'error' => $e->getMessage()]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'], 500);
            }
            return back()->withErrors(['mobile_number' => 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.'])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'کد تایید به شماره شما ارسال شد.'], 200);
        }

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
        $mobileNumber = $request->session()->get('mobile_number_for_otp');
        if (!$mobileNumber) {
            Log::warning('Attempt to access verifyOtpForm without mobile number in session.');
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
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'size:11'], // تغییر از digits به size
            'otp' => ['required', 'string', 'digits:6', 'numeric'], // اضافه شد: 'numeric' برای هماهنگی با فرانت‌اند
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for verifyOtp', ['errors' => $validator->errors()->toArray()]);
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;
        // کلید کش OTP را به صورت ساده‌تر تولید می‌کنیم تا با پیشوند داخلی لاراول هماهنگ باشد
        $otpCacheKey = 'otp_' . $mobileNumber; // تغییر اینجا

        // بازیابی OTP به صورت متن ساده از کش
        $storedOtp = Cache::get($otpCacheKey); // تغییر نام متغیر برای وضوح بیشتر

        // --- شروع لاگ‌های اضافه شده برای دیباگ ---
        Log::debug('Verifying OTP', [
            'mobile' => $mobileNumber,
            'otp_cache_key' => $otpCacheKey, // لاگ کردن کلید کش
            'stored_otp_exists_before_check' => (bool)$storedOtp, // آیا OTP ذخیره شده وجود دارد؟
            'entered_otp_value' => $request->otp, // لاگ کردن مقدار OTP وارد شده (فقط برای دیباگ)
            'stored_otp_value' => $storedOtp // لاگ کردن مقدار OTP ذخیره شده (فقط برای دیباگ)
        ]);
        // --- پایان لاگ‌های اضافه شده برای دیباگ ---

        // بررسی OTP با مقایسه مستقیم و تبدیل نوع به integer برای اطمینان از مقایسه صحیح
        if (!$storedOtp || intval($request->otp) !== intval($storedOtp)) { // تغییر از Hash::check به مقایسه مستقیم با intval
            Log::warning('Invalid or expired OTP', [
                'mobile' => $mobileNumber,
                'stored_otp_exists' => (bool)$storedOtp,
                'stored_otp' => $storedOtp, // لاگ کردن مقدار ذخیره شده
                'entered_otp' => $request->otp // لاگ کردن مقدار وارد شده
            ]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'کد تایید اشتباه یا منقضی شده است.'], 400);
            }
            throw ValidationException::withMessages([
                'otp' => ['کد تایید اشتباه یا منقضی شده است.'],
            ]);
        }

        Cache::forget($otpCacheKey);
        Log::info('OTP verified and cleared from cache', ['mobile' => $mobileNumber]);

        $currentSessionId = Session::getId();

        $user = User::where('mobile_number', $mobileNumber)->first();

        if ($user) {
            Auth::login($user);
            Log::info('User logged in successfully', ['user_id' => $user->id, 'mobile' => $mobileNumber]);

            // انتقال سبد خرید مهمان (بر اساس session_id) به سبد خرید کاربر لاگین شده
            $this->cartService->transferGuestCartToUserCart($currentSessionId, $user);
            Log::info('Guest cart transferred to existing user', ['user_id' => $user->id, 'session_id' => $currentSessionId]);


            if ($request->expectsJson()) {
                return response()->json(['message' => 'ورود با موفقیت انجام شد.', 'user' => $user], 200);
            }

            return redirect()->intended('/')->with('status', 'ورود با موفقیت انجام شد.');

        } else {
            // نکته: 'pending_registration_' باید در جایی که کاربر برای اولین بار اطلاعات ثبت‌نام را وارد می‌کند،
            // با یک TTL مناسب (مثلاً 30 دقیقه) در کش ذخیره شود.
            // مثال: Cache::put('pending_registration_' . $mobileNumber, $data, now()->addMinutes(30));
            $registrationData = Cache::get('pending_registration_' . $mobileNumber);

            if ($registrationData) {
                $user = User::create([
                    'name' => $registrationData['name'],
                    'lastname' => $registrationData['lastname'],
                    'mobile_number' => $registrationData['mobile_number'],
                    'profile_completed' => false,
                ]);
                Log::info('New user registered successfully', ['user_id' => $user->id, 'mobile' => $mobileNumber]);

                Cache::forget('pending_registration_' . $mobileNumber);

                Auth::login($user);

                // انتقال سبد خرید مهمان (بر اساس session_id) به کاربر جدید
                $this->cartService->assignGuestCartToNewUser($currentSessionId, $user);
                Log::info('Guest cart assigned to new user', ['user_id' => $user->id, 'session_id' => $currentSessionId]);


                if ($request->expectsJson()) {
                    return response()->json(['message' => 'ثبت‌نام و ورود با موفقیت انجام شد.', 'user' => $user], 200);
                }
                return redirect()->intended('/')->with('status', 'ثبت‌نام و ورود با موفقیت انجام شد.');

            } else {
                Log::warning('User not found and no pending registration data', ['mobile' => $mobileNumber]);
                $request->session()->flash('user_not_found_mobile', $mobileNumber);
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.'], 404);
                }
                return redirect()->route('auth.mobile-login-form')->with('status', 'کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.')->with('show_register_link', true);
            }
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
        Log::info('User logged out', ['user_id' => Auth::id()]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
