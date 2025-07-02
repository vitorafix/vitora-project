<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart; // لازم است اگر در متدهای CartController به طور مستقیم پاس داده شود
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
use App\Services\CartService; // اضافه شد: برای استفاده از سرویس سبد خرید
use Illuminate\Support\Facades\Log; // اضافه شد: برای لاگ‌گذاری

class MobileAuthController extends Controller
{
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
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'digits:11'],
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for sendOtp', ['errors' => $validator->errors()->toArray()]);
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;
        $otp = random_int(100000, 999999);
        $cacheKey = 'otp_' . $mobileNumber;

        Cache::put($cacheKey, $otp, now()->addMinutes(2));
        Log::info('OTP generated and cached', ['mobile' => $mobileNumber, 'otp' => $otp]);


        $smsService = new MelipayamakSmsService();
        $patternCode = config('services.melipayamak.pattern_code');

        try {
            if ($patternCode) {
                $smsService->sendByPattern($mobileNumber, $patternCode, ['verification-code' => $otp]);
            } else {
                $smsService->send($mobileNumber, "کد تایید شما: " . $otp . "\nفروشگاه چای");
            }
            Log::info('OTP SMS sent successfully', ['mobile' => $mobileNumber]);
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
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'digits:11'],
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for verifyOtp', ['errors' => $validator->errors()->toArray()]);
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
            Log::warning('Invalid or expired OTP', ['mobile' => $mobileNumber, 'entered_otp' => $otp, 'stored_otp' => $storedOtp]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'کد تایید اشتباه یا منقضی شده است.'], 400);
            }
            throw ValidationException::withMessages([
                'otp' => ['کد تایید اشتباه یا منقضی شده است.'],
            ]);
        }

        Cache::forget($cacheKey);
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
