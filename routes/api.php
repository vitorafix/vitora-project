<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Api\CartController as ApiCartController; // تغییر: استفاده از نام مستعار برای جلوگیری از تداخل نام
use App\Http\Controllers\OrderController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Example API route for authenticated users
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for mobile authentication (OTP)
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');
    Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtp'])->name('verify-otp');
    // Route::post('/register', [RegisterController::class, 'register'])->name('register');
});

// مسیرهای API مربوط به عملیات سبد خرید (بدون نیاز به احراز هویت)
Route::prefix('cart')->name('api.cart.')->group(function () {
    // دریافت محتویات سبد خرید
    Route::get('/contents', [ApiCartController::class, 'getContents'])->name('getContents'); // اصلاح شده: نام متد به 'getContents' تغییر یافت
    // افزودن محصول به سبد خرید
    Route::post('/add/{product}', [ApiCartController::class, 'add'])->name('add');
    // به‌روزرسانی تعداد آیتم در سبد خرید
    Route::post('/update-quantity/{cartItem}', [ApiCartController::class, 'updateQuantity'])->name('updateQuantity');
    // حذف آیتم از سبد خرید
    Route::post('/remove-item/{cartItem}', [ApiCartController::class, 'removeCartItem'])->name('removeItem');
    // پاک کردن کامل سبد خرید
    Route::post('/clear', [ApiCartController::class, 'clearCart'])->name('clear');
    // اعمال کد تخفیف
    Route::post('/apply-coupon', [ApiCartController::class, 'applyCoupon'])->name('applyCoupon');
    // حذف کد تخفیف
    Route::post('/remove-coupon', [ApiCartController::class, 'removeCoupon'])->name('removeCoupon');
});


// اضافه کردن مسیر placeOrder اگر در api.php باشد
// این مسیر همچنان به middleware 'api' نیاز دارد.
Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('order.place')->middleware('api');

