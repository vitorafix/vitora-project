<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Api\ApiCartController;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Example API route for authenticated users (can be removed if not using Sanctum for API)
// این مسیر اگر فقط از JWT برای API استفاده می‌کنید، می‌تواند حذف شود.
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// مسیرهای سبد خرید که نیاز به احراز هویت ندارند (قابل دسترسی برای کاربران مهمان و لاگین شده)
Route::prefix('cart')->name('api.cart.')->group(function () {
    // کاربر می‌تواند محصولات را ببیند، اضافه کند، کم و زیاد کند و پاک کند بدون نیاز به لاگین.
    Route::get('/contents', [ApiCartController::class, 'getContents'])->name('getContents');
    Route::post('/add/{product}', [ApiCartController::class, 'add'])->name('add');
    Route::post('/update-quantity/{cartItem}', [ApiCartController::class, 'updateQuantity'])->name('updateQuantity');
    Route::post('/remove-item/{cartItem}', [ApiCartController::class, 'removeCartItem'])->name('removeItem');
    Route::post('/clear', [ApiCartController::class, 'clearCart'])->name('clear');
});


// گروه مسیرهای API که از میدل‌ویر 'api' (با درایور JWT) استفاده می‌کنند.
// این گروه برای APIهای Stateless مناسب است و از سشن استفاده نمی‌کند.
Route::middleware('api')->group(function () {
    // مسیرهای احراز هویت با موبایل (OTP) برای API
    Route::post('/auth/send-otp', [MobileAuthController::class, 'sendOtp'])->name('api.auth.send-otp');
    // OTP verification route for API, handles JWT token issuance
    Route::post('/auth/verify-otp', [MobileAuthController::class, 'verifyOtpAndLogin'])->name('api.auth.verify-otp');
    // مسیر لاگین با OTP که توکن JWT را برمی‌گرداند (همان verify-otp است)
    Route::post('/auth/login', [MobileAuthController::class, 'verifyOtpAndLogin'])->name('api.auth.login'); // Changed to verifyOtpAndLogin

    // مسیرهای سبد خرید که نیاز به احراز هویت JWT دارند (مثلاً اعمال کوپن)
    Route::prefix('cart')->name('api.cart.')->group(function () {
        // ادامه عملیات سبد خرید که نیاز به لاگین کاربر دارد.
        Route::post('/apply-coupon', [ApiCartController::class, 'applyCoupon'])->name('applyCoupon');
        Route::post('/remove-coupon', [ApiCartController::class, 'removeCoupon'])->name('removeCoupon');
    });

    // مسیر خروج کاربر از API (با JWT)
    Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->name('api.auth.logout');

    // مسیر placeOrder که نیاز به احراز هویت JWT و تکمیل پروفایل دارد
    Route::post('/order/place', [OrderController::class, 'placeOrder'])
        ->name('api.order.place')
        ->middleware('profile.completed'); // اضافه شدن میدل‌ویر profile.completed
});
