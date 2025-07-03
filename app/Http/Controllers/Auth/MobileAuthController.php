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
use Illuminate\Support\Facades\Hash; // Added: For hashing OTP (but no longer used for OTP, only for other hashes)

class MobileAuthController extends Controller
{
    // Maximum number of OTP sending attempts within a specified period
    const MAX_ATTEMPTS = 3;
    // Cooldown period for OTP attempts in minutes
    const ATTEMPT_COOLDOWN_MINUTES = 15;
    // OTP expiry time in minutes
    const OTP_EXPIRY_MINUTES = 2;

    // CartService dependency injection
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the mobile number login form.
     *
     * @return \Illuminate\View\View
     */
    public function showMobileLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Send OTP to the mobile number.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'size:11'], // Changed from digits to size
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for sendOtp', ['errors' => $validator->errors()->toArray()]);
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;

        // --- START: Added check for user existence before sending OTP ---
        $user = User::where('mobile_number', $mobileNumber)->first();

        if (!$user) {
            Log::info('Mobile number not found in database, preventing OTP send.', ['mobile' => $mobileNumber]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.'], 404);
            }
            return back()->withErrors(['mobile_number' => 'کاربری با این شماره یافت نشد. لطفاً ثبت‌نام کنید.'])->withInput();
        }
        // --- END: Added check for user existence before sending OTP ---

        $attemptsCacheKey = 'sms_attempts_' . $mobileNumber;

        // Check recent attempts for this mobile number
        $attempts = Cache::get($attemptsCacheKey, 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Log::warning('Too many OTP requests for mobile number', ['mobile' => $mobileNumber, 'attempts' => $attempts]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً پس از ' . self::ATTEMPT_COOLDOWN_MINUTES . ' دقیقه دیگر تلاش کنید.'], 429);
            }
            return back()->withErrors(['mobile_number' => 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً پس از ' . self::ATTEMPT_COOLDOWN_MINUTES . ' دقیقه دیگر تلاش کنید.'])->withInput();
        }

        $otp = random_int(100000, 999999);
        // Generate a simpler OTP cache key to align with Laravel's internal prefixing
        $otpCacheKey = 'otp_' . $mobileNumber; // Change here
        
        // Store OTP as plain text in cache (without hashing with Hash::make)
        Cache::put($otpCacheKey, $otp, now()->addMinutes(self::OTP_EXPIRY_MINUTES));
        
        // --- START: Added logs for debugging ---
        Log::debug('OTP generated and cached', [
            'mobile' => $mobileNumber,
            'otp_cache_key' => $otpCacheKey, // Log the cache key
            'expiry_time' => now()->addMinutes(self::OTP_EXPIRY_MINUTES)->toDateTimeString() // Log the expiry time
        ]);
        // --- END: Added logs for debugging ---

        $smsService = new MelipayamakSmsService();
        $patternCode = config('services.melipayamak.pattern_code');

        try {
            if ($patternCode) {
                $smsService->sendByPattern($mobileNumber, $patternCode, ['verification-code' => $otp]);
            } else {
                $smsService->send($mobileNumber, "کد تایید شما: " . $otp . "\nفروشگاه چای");
            }
            Log::info('OTP SMS sent successfully', ['mobile' => $mobileNumber]);

            // Manage TTL for attempt counter: if it's the first attempt, set TTL, otherwise just increment.
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
     * Display the OTP verification form.
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
     * Verify OTP and log in/register the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verifyOtp(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/', 'size:11'], // Changed from digits to size
            'otp' => ['required', 'string', 'digits:6', 'numeric'], // Added: 'numeric' for frontend compatibility
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for verifyOtp', ['errors' => $validator->errors()->toArray()]);
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $mobileNumber = $request->mobile_number;
        // Generate a simpler OTP cache key to align with Laravel's internal prefixing
        $otpCacheKey = 'otp_' . $mobileNumber; // Change here

        // Retrieve OTP as plain text from cache
        $storedOtp = Cache::get($otpCacheKey); // Renamed variable for clarity

        // --- START: Added logs for debugging ---
        Log::debug('Verifying OTP', [
            'mobile' => $mobileNumber,
            'otp_cache_key' => $otpCacheKey, // Log the cache key
            'stored_otp_exists_before_check' => (bool)$storedOtp, // Does the stored OTP exist?
            'entered_otp_value' => $request->otp, // Log the entered OTP value (for debugging only)
            'stored_otp_value' => $storedOtp // Log the stored OTP value (for debugging only)
        ]);
        // --- END: Added logs for debugging ---

        // Verify OTP by direct comparison and type casting to integer to ensure correct comparison
        if (!$storedOtp || intval($request->otp) !== intval($storedOtp)) { // Changed from Hash::check to direct comparison with intval
            Log::warning('Invalid or expired OTP', [
                'mobile' => $mobileNumber,
                'stored_otp_exists' => (bool)$storedOtp,
                'stored_otp' => $storedOtp, // Log the stored value
                'entered_otp' => $request->otp // Log the entered value
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

            // Transfer guest cart (based on session_id) to the logged-in user's cart
            $this->cartService->transferGuestCartToUserCart($currentSessionId, $user);
            Log::info('Guest cart transferred to existing user', ['user_id' => $user->id, 'session_id' => $currentSessionId]);


            if ($request->expectsJson()) {
                return response()->json(['message' => 'ورود با موفقیت انجام شد.', 'user' => $user], 200);
            }

            return redirect()->intended('/')->with('status', 'ورود با موفقیت انجام شد.');

        } else {
            // Note: 'pending_registration_' should be stored in cache where the user first enters registration information,
            // with an appropriate TTL (e.g., 30 minutes).
            // Example: Cache::put('pending_registration_' . $mobileNumber, $data, now()->addMinutes(30));
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

                // Assign guest cart (based on session_id) to the new user
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
     * Log out the user.
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
