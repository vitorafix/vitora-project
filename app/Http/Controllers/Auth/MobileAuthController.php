<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

// Import new service classes and interfaces
use App\Contracts\Services\OtpServiceInterface;
use App\Contracts\Services\RateLimitServiceInterface;
use App\Contracts\Services\AuditServiceInterface;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\VerifyOtpRequest;

class MobileAuthController extends Controller
{
    // Constants for Rate Limiting (these can also be moved to config files)
    const OTP_SEND_RATE_LIMIT = 5;
    const OTP_SEND_COOLDOWN_MINUTES = 1;
    const OTP_VERIFY_RATE_LIMIT = 10;
    const OTP_VERIFY_COOLDOWN_MINUTES = 5;
    const OTP_IP_MAX_ATTEMPTS = 10;
    const OTP_IP_COOLDOWN_MINUTES = 60;

    // Constants for Session & Cache Keys
    const SESSION_MOBILE_FOR_OTP = 'mobile_number_for_otp';
    const SESSION_MOBILE_FOR_REGISTRATION = 'mobile_number_for_registration';
    const CACHE_PENDING_REGISTRATION_PREFIX = 'pending_registration_';
    const PENDING_REGISTRATION_CACHE_TTL_MINUTES = 10;

    // Dependency Injection for services
    protected OtpServiceInterface $otpService;
    protected RateLimitServiceInterface $rateLimitService; // Keep injecting RateLimitService here
    protected AuditServiceInterface $auditService;

    public function __construct(
        OtpServiceInterface $otpService,
        RateLimitServiceInterface $rateLimitService, // Keep injecting RateLimitService here
        AuditServiceInterface $auditService
    ) {
        $this->otpService = $otpService;
        $this->rateLimitService = $rateLimitService; // Assign injected service
        $this->auditService = $auditService;
    }

    /**
     * Show the mobile login form.
     *
     * @return View
     */
    public function showMobileLoginForm(): View
    {
        // Controller's responsibility: Render the view for mobile login.
        // If user is already authenticated, redirect them to dashboard.
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }
        return view('login'); // Assuming 'resources/views/login.blade.php'
    }

    /**
     * Show the OTP verification form.
     * This method is called after sending OTP or when redirected to verify.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function showOtpVerifyForm(Request $request): View|RedirectResponse
    {
        // Controller's responsibility: Manage session state for view rendering.
        $encryptedMobileNumber = $request->session()->get(self::SESSION_MOBILE_FOR_OTP);
        $mobileNumber = null;

        if (!$encryptedMobileNumber) {
            return redirect()->route('auth.mobile-login-form')->withErrors(['mobile_number' => 'شماره موبایل برای تأیید کد یافت نشد. لطفاً دوباره وارد شوید.']);
        }

        try {
            $mobileNumber = Crypt::decryptString($encryptedMobileNumber);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::error('Could not decrypt mobile number from session: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return redirect()->route('auth.mobile-login-form')->with('error', 'خطا در بازیابی شماره موبایل. لطفاً دوباره تلاش کنید.');
        }

        $attemptCount = $request->session()->get('otp_attempt_count', 0);

        // Render the OTP verification view.
        return view('auth.verify-otp', compact('mobileNumber', 'attemptCount')); // Assuming 'resources/views/auth/verify-otp.blade.php'
    }


    /**
     * Send OTP to the provided mobile number.
     *
     * @param SendOtpRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function sendOtp(SendOtpRequest $request)
    {
        $mobileNumber = $this->normalizeMobileNumber($request->mobile_number);
        $ipAddress = $request->ip();

        try {
            // Controller delegates the core OTP sending logic to the OtpService.
            // Pass RateLimitService and a callable auditLogger to the service.
            $this->otpService->sendOtpForMobile(
                $mobileNumber,
                $ipAddress,
                $request->session(),
                $this->rateLimitService, // Pass the injected RateLimitService
                function ($message, $level = 'info', $userId = null, $userType = null, $objectId = null) use ($request, $mobileNumber) {
                    // This closure allows the service to log audits without direct dependency on AuditService
                    $this->auditService->log(
                        'otp_send_event',
                        $message,
                        $request,
                        ['mobile_number' => $mobileNumber],
                        hash('sha256', $mobileNumber),
                        $userId, $userType, $objectId, $level
                    );
                }
            );

            // If the service call is successful, decide response based on request type.
            if ($request->expectsJson()) {
                return response()->json(['message' => 'کد تأیید با موفقیت به شماره جدید ارسال شد.']);
            }

            return redirect()->route('auth.verify-otp-form')->with('status', 'کد تأیید با موفقیت ارسال شد.');

        } catch (\Exception $e) {
            // Controller catches exceptions from the service and responds appropriately.
            // Log the critical error via AuditService.
            $this->auditService->log(
                'otp_send_failed_exception',
                'Failed to send OTP for mobile number: ' . $mobileNumber . ' Error: ' . $e->getMessage(),
                $request,
                ['mobile_number' => $mobileNumber, 'error' => $e->getMessage()],
                hash('sha256', $mobileNumber),
                null, null, null, 'error'
            );

            // Use the respondWithError helper for consistent error handling.
            return $this->respondWithError(
                $e->getMessage(), // Use the exception message for user feedback
                $e->getCode() ?: 500, // Use exception code or default to 500
                $request,
                'mobile_number'
            );
        }
    }

    /**
     * Verify OTP and log in the user or redirect to registration.
     *
     * @param VerifyOtpRequest $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function verifyOtp(VerifyOtpRequest $request)
    {
        $mobileNumber = $this->normalizeMobileNumber($request->mobile_number);
        $otp = $this->normalizeOtp($request->otp);
        $ipAddress = $request->ip();

        try {
            // Controller delegates verification logic to OtpService.
            // Pass RateLimitService and a callable auditLogger to the service.
            $user = $this->otpService->verifyOtpForMobile(
                $mobileNumber,
                $otp,
                $ipAddress,
                $request->session(),
                $this->rateLimitService, // Pass the injected RateLimitService
                function ($message, $level = 'info', $userId = null, $userType = null, $objectId = null) use ($request, $mobileNumber) {
                    // This closure allows the service to log audits
                    $this->auditService->log(
                        'otp_verify_event',
                        $message,
                        $request,
                        ['mobile_number' => $mobileNumber],
                        hash('sha256', $mobileNumber),
                        $userId, $userType, $objectId, $level
                    );
                }
            );

            // If verification is successful, log in the user.
            Auth::login($user, $request->boolean('remember'));

            // Log user login via audit service.
            $this->auditService->log(
                'user_logged_in_via_otp',
                'User logged in via OTP: ' . $mobileNumber,
                $request,
                ['user_id' => $user->id, 'mobile_number' => $mobileNumber],
                hash('sha256', $mobileNumber),
                $user->id, 'User', $user->id, 'info'
            );

            // Clear session data used for OTP verification.
            $request->session()->forget(self::SESSION_MOBILE_FOR_OTP);
            $request->session()->forget(self::SESSION_MOBILE_FOR_REGISTRATION);

            // Redirect to intended URL or dashboard.
            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            // Controller catches exceptions from the service and responds appropriately.
            $this->auditService->log(
                'otp_verify_failed_exception',
                'Failed to verify OTP for mobile number: ' . $mobileNumber . ' Error: ' . $e->getMessage(),
                $request,
                ['mobile_number' => $mobileNumber, 'error' => $e->getMessage()],
                hash('sha256', $mobileNumber),
                null, null, null, 'error'
            );

            return $this->respondWithError(
                $e->getMessage(),
                $e->getCode() ?: 401, // Default to 401 for verification errors
                $request,
                'otp'
            );
        }
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        $mobileNumber = Auth::user() ? Auth::user()->mobile_number : null;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->auditService->log(
            'user_logged_out',
            'User logged out.',
            $request,
            ['user_id' => $userId, 'mobile_number' => $mobileNumber],
            $mobileNumber ? hash('sha256', $mobileNumber) : null,
            $userId, 'User', $userId, 'info'
        );

        return redirect('/');
    }

    /**
     * Helper method to normalize mobile number (remove spaces, convert Persian/Arabic digits, ensure 09 prefix).
     * This is a utility function, and can remain in the controller or be moved to a dedicated helper/utility class.
     *
     * @param string $mobileNumber
     * @return string
     * @throws \InvalidArgumentException If the mobile number is invalid after normalization.
     */
    private function normalizeMobileNumber(string $mobileNumber): string
    {
        $normalizedNumber = str_replace([' ', '-'], '', $mobileNumber);
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $normalizedNumber = str_replace($persianDigits, range(0, 9), $normalizedNumber);
        $normalizedNumber = str_replace($arabicDigits, range(0, 9), $normalizedNumber);

        if (Str::startsWith($normalizedNumber, '9') && strlen($normalizedNumber) === 10) {
            $normalizedNumber = '0' . $normalizedNumber;
        }

        if (!preg_match('/^09\d{9}$/', $normalizedNumber)) {
            Log::warning('Invalid mobile number after normalization: ' . $mobileNumber . ' -> ' . $normalizedNumber);
            throw new \InvalidArgumentException('شماره موبایل پس از نرمال‌سازی نامعتبر است.');
        }

        return $normalizedNumber;
    }

    /**
     * Helper method to normalize OTP input (remove spaces, convert Persian/Arabic digits).
     * Similar to normalizeMobileNumber, this can be a utility function.
     *
     * @param string $otp
     * @return string
     */
    private function normalizeOtp(string $otp): string
    {
        $normalizedOtp = str_replace([' ', '-'], '', $otp);
        $persianDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabicDigits = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $normalizedOtp = str_replace($persianDigits, range(0, 9), $normalizedOtp);
        $normalizedOtp = str_replace($arabicDigits, range(0, 9), $normalizedOtp);

        return $normalizedOtp;
    }

    /**
     * Helper method to respond with error, either JSON or redirect.
     * This method is a controller-level utility for consistent error responses.
     *
     * @param string $message
     * @param int $code
     * @param Request $request
     * @param string $errorField
     * @param string|null $redirectRoute
     * @param bool $showRegisterLink
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function respondWithError(
        string $message,
        int $code,
        Request $request,
        string $errorField = 'general',
        ?string $redirectRoute = null,
        bool $showRegisterLink = false
    ) {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'error_field' => $errorField], $code);
        }
        $redirect = back()->withErrors([$errorField => $message])->withInput();
        if ($redirectRoute) {
            $redirect = redirect()->route($redirectRoute)->with('status', $message);
            if ($showRegisterLink) {
                $redirect->with('show_register_link', true);
            }
        }
        return $redirect;
    }
}
