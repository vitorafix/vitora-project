<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController; // اضافه شده: برای استفاده از CartController
use App\Http\Controllers\OrderController; // اضافه شده: برای استفاده از OrderController (اگر مسیر placeOrder اینجا باشد)


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

// API routes for Cart operations
// از middleware 'api' استفاده کنید. 'throttle:cart_add' فقط برای add مناسب است،
// برای سایر عملیات سبد خرید شاید نیاز به throttle متفاوتی باشد یا اصلا نباشد.
Route::middleware('api')->group(function () {
    // نام مسیر 'cart.add' به 'api.cart.add' تغییر یافت تا از تداخل جلوگیری شود.
    Route::post('/cart/add', [CartController::class, 'add'])->name('api.cart.add')->middleware('throttle:cart_add');
    // نام مسیر 'cart.update' به 'api.cart.update' تغییر یافت.
    Route::put('/cart/update/{cartItem}', [CartController::class, 'updateQuantity'])->name('api.cart.update');
    // نام مسیر 'cart.remove' به 'api.cart.remove' تغییر یافت.
    Route::delete('/cart/remove/{cartItem}', [CartController::class, 'removeCartItem'])->name('api.cart.remove');
    // نام مسیر 'cart.clear' به 'api.cart.clear' تغییر یافت.
    Route::post('/cart/clear', [CartController::class, 'clearCart'])->name('api.cart.clear');
    
    // اضافه شدن مسیر جدید برای دریافت محتویات سبد خرید
    Route::get('/cart/contents', [CartController::class, 'getContents'])->name('api.cart.contents');
});

// اضافه کردن مسیر placeOrder اگر در api.php باشد
// اگر placeOrder در web.php است، نیازی به این بخش نیست.
// با توجه به اینکه PlaceOrderRequest و OrderController برای placeOrder استفاده می شوند،
// و درخواست placeOrder در cart.js به /order/place میرود،
// باید مطمئن شویم که این مسیر در routes/web.php یا routes/api.php تعریف شده باشد.
// اگر از AJAX برای placeOrder استفاده می کنید، معمولاً در api.php تعریف می شود.
// Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('order.place')->middleware('api');
