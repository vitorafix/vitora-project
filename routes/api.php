<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController;
// use App\Http\Controllers\CartController; // نیازی به ایمپورت CartController در اینجا نیست اگر هیچ مسیر کارتی وجود ندارد
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

// مسیرهای مربوط به عملیات سبد خرید به routes/web.php منتقل شده‌اند.
// اگر نیاز به APIهای دیگری دارید که به Session نیاز ندارند، می‌توانید آن‌ها را اینجا اضافه کنید.

// اضافه کردن مسیر placeOrder اگر در api.php باشد
// اگر placeOrder در web.php است، نیازی به این بخش نیست.
// با توجه به اینکه PlaceOrderRequest و OrderController برای placeOrder استفاده می شوند،
// و درخواست placeOrder در cart.js به /order/place میرود،
// باید مطمئن شویم که این مسیر در routes/web.php یا routes/api.php تعریف شده باشد.
// اگر از AJAX برای placeOrder استفاده می کنید، معمولاً در api.php تعریف می شود.
// Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('order.place')->middleware('api');
