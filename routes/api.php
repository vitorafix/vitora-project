<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Api\ApiCartController; // مطمئن شوید این ایمپورت شده است
use App\Http\Controllers\OrderController; // مطمئن شوید این ایمپورت شده است
use App\Http\Controllers\Auth\RegisterController; // NEW: ایمپورت کردن RegisterController
use App\Http\Controllers\AnalyticsController; // NEW: Import AnalyticsController

// NEW: Imports for JWT/JWE/JWK implementation
use App\Http\Controllers\JwksController; // Import the JwksController
use App\Services\AuthTokenService; // Import AuthTokenService for test routes
use App\Models\User; // Import User model for test routes
use Illuminate\Support\Facades\Auth; // For accessing authenticated user in protected route
use Jose\Component\Core\JWK; // Required for JWE encryption test (if using direct test routes)


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| در اینجا می‌توانید مسیرهای API برنامه خود را ثبت کنید. این مسیرها توسط
| یک RouteServiceProvider در گروهی که به میدل‌ویر "api" اختصاص داده شده است
| بارگذاری می‌شوند. از ساخت API خود لذت ببرید!
|
*/

// مسیرهای احراز هویت با موبایل (OTP) برای API
// این مسیرها باید عمومی باشند تا کاربران بتوانند بدون احراز هویت اولیه OTP دریافت کنند.
Route::post('/auth/send-otp', [MobileAuthController::class, 'sendOtp'])->name('api.auth.send-otp');
Route::post('/auth/verify-otp', [MobileAuthController::class, 'verifyOtpAndLogin'])->name('api.auth.verify-otp');
Route::post('/auth/login', [MobileAuthController::class, 'verifyOtpAndLogin'])->name('api.auth.login');

// NEW: مسیر برای ثبت‌نام کاربر جدید
// این مسیر به RegisterController@register اشاره می‌کند که مسئول ذخیره موقت اطلاعات
// و ارسال OTP است. ایجاد نهایی کاربر پس از تأیید OTP در OtpService انجام می‌شود.
Route::post('/auth/register', [RegisterController::class, 'register'])->name('api.auth.register');

// NEW: مسیر اختصاصی برای ارسال مجدد OTP در زمان ثبت‌نام
// این مسیر برای ارسال OTP به شماره موبایلی است که هنوز در سیستم ثبت‌نام نکرده است.
Route::post('/auth/register/request-otp', [RegisterController::class, 'requestOtp'])->name('api.auth.register.request-otp');


// مسیر برای ورود به سشن با استفاده از JWT (برای صفحات وب)
// این مسیر نیازی به middleware 'jwt.auth' ندارد زیرا وظیفه آن احراز هویت JWT دریافتی
// و سپس لاگین کردن کاربر در سشن وب لاراول است.
Route::post('auth/jwt-login', [MobileAuthController::class, 'jwtLogin'])->name('api.auth.jwt-login');

// مسیرهای سبد خرید
// این مسیرها برای کاربران لاگین شده و همچنین کاربران مهمان (با استفاده از guest_uuid) قابل دسترسی هستند.
// بنابراین، نیازی به میدل‌ویر 'jwt.auth' ندارند.
Route::prefix('cart')->name('api.cart.')->group(function () {
    Route::get('/contents', [ApiCartController::class, 'getContents'])->name('getContents');
    Route::post('/add/{product}', [ApiCartController::class, 'add'])->name('add');
    Route::post('/update-quantity/{cartItem}', [ApiCartController::class, 'updateQuantity'])->name('updateQuantity');
    Route::post('/remove-item/{cartItem}', [ApiCartController::class, 'removeCartItem'])->name('removeItem');
    Route::post('/clear', [ApiCartController::class, 'clearCart'])->name('clear');
    Route::post('/apply-coupon', [ApiCartController::class, 'applyCoupon'])->name('applyCoupon');
    Route::post('/remove-coupon', [ApiCartController::class, 'removeCoupon'])->name('removeCoupon');
});

// گروه مسیرهای API که از میدل‌ویر 'jwt.auth' (با درایور JWT) استفاده می‌کنند.
// تمام مسیرهای داخل این گروه نیاز به یک توکن JWT معتبر دارند.
Route::middleware('jwt.auth')->group(function () {

    // مسیر خروج کاربر از API (با JWT)
    Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->name('api.auth.logout');

    // سایر مسیرهای لاگین شده مثل سفارش
    // این مسیر برای ثبت سفارش است و علاوه بر JWT نیاز به تکمیل پروفایل نیز دارد.
    Route::post('/order/place', [OrderController::class, 'placeOrder'])
        ->name('api.order.place')
        ->middleware('profile.completed'); // اضافه شدن میدل‌ویر profile.completed

    // مسیر نمونه برای کاربران احراز هویت شده با JWT
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// NEW: مسیر برای دریافت داده‌های تحلیلی
// از middleware 'web' استفاده کنید تا به کوکی‌ها دسترسی داشته باشید.
// اگر می‌خواهید اطلاعات کاربر لاگین شده را هم در analytics ذخیره کنید، می‌توانید middleware('jwt.auth') را هم اضافه کنید.
Route::post('/analytics/track', [AnalyticsController::class, 'track'])->name('api.analytics.track');

// NEW: مسیر Health Check برای سیستم آنالیتیکس
Route::get('/analytics/health', [AnalyticsController::class, 'health'])->name('api.analytics.health');


// --- NEW: Routes for JWKS and JWT+JWE Implementation ---

// Define the JWKS endpoint.
// This route should be publicly accessible as clients need to fetch the public key for JWE encryption.
Route::get('/jwks.json', [JwksController::class, 'index']);

// Route to generate a JWE token containing a JWT for a user.
// In a real application, this would be called after a successful login/registration
// where you have a user object.
Route::get('/generate-token', function (Request $request, AuthTokenService $authTokenService) {
    // For testing, let's assume a user with ID 1 exists.
    // IMPORTANT: Replace this with actual user retrieval logic (e.g., from login).
    $user = User::find(1);

    if (!$user) {
        return response()->json(['error' => 'Test user not found. Please create a user with ID 1 or adjust the code.'], 404);
    }

    try {
        // You can add custom claims here if needed. These will be part of the inner JWT.
        $customClaims = [
            'user_role' => 'admin',
            'app_version' => '1.0'
        ];
        $token = $authTokenService->createToken($user, $customClaims);
        return response()->json(['message' => 'Token generated successfully', 'token' => $token]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to generate token: ' . $e->getMessage()], 500);
    }
});

// Route to test the validation of a JWE token (protected route).
// This route will be protected by the 'jwt_jwe.auth' middleware (which we will define in Kernel.php).
Route::middleware('jwt_jwe.auth')->get('/protected-resource', function (Request $request) {
    // If the 'jwt_jwe.auth' middleware passes, the authenticated user object will be available
    // via Laravel's Auth facade.
    $user = Auth::user();

    return response()->json([
        'message' => 'Access granted to protected resource!',
        'authenticated_user_id' => $user->id,
        'authenticated_user_name' => $user->name,
        // You can add more user details as needed.
    ]);
});

// --- Optional: Direct JWT/JWE test routes for debugging (uncomment if needed) ---
// These routes are for isolated testing of JWE encryption/decryption,
// and are not typically part of the main application flow.
/*
use App\Services\JweService; // Uncomment for direct JWE testing

Route::post('/test-jwe-encrypt', function (Request $request, JweService $jweService, JwkService $jwkService) {
    $payload = $request->input('payload'); // This would typically be a JWT
    if (!$payload) {
        return response()->json(['error' => 'Payload not provided'], 400);
    }
    try {
        $publicJwkArray = $jwkService->getJwk();
        $publicJwk = JWK::create($publicJwkArray);
        $jwe = $jweService->encrypt($payload, $publicJwk);
        return response()->json(['message' => 'JWE encrypted', 'jwe' => $jwe]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to encrypt JWE: ' . $e->getMessage()], 500);
    }
});

Route::post('/test-jwe-decrypt', function (Request $request, JweService $jweService) {
    $jwe = $request->input('jwe');
    if (!$jwe) {
        return response()->json(['error' => 'JWE not provided'], 400);
    }
    try {
        $decrypted = $jweService->decrypt($jwe);
        return response()->json(['message' => 'JWE decrypted', 'decrypted_payload' => $decrypted]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to decrypt JWE: ' . $e->getMessage()], 401);
    }
});
*/
