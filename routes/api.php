<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Api\ApiCartController;
use App\Http\Controllers\OrderController; // اصلاح: از App->Http به App\Http تغییر یافت


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Example API route for authenticated users
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// مسیرهای API که به Session نیاز دارند، در اینجا با middleware 'web' نیز گروه بندی شده‌اند.
// This group applies both 'api' and 'web' middlewares to enable session support for these API routes.
Route::middleware(['api', 'web'])->group(function () {
    // مسیر ارسال OTP به اینجا منتقل شد و اکنون از Session پشتیبانی می‌کند.
    // The OTP send route has been moved here and now supports sessions.
    Route::post('/auth/send-otp', [MobileAuthController::class, 'sendOtp'])->name('api.auth.send-otp');

    // OTP routes moved to web.php to enable session middleware support (stateless API does not support sessions)
    // Route::prefix('auth')->name('api.auth.')->group(function () {
    //     Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');
    //     Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtp'])->name('verify-otp');
    //     // Route::post('/register', [RegisterController::class, 'register'])->name('register');
    //     // REMOVED: Route::post('/change-mobile-number', [MobileAuthController::class, 'changeMobileNumber'])->name('change-mobile-number');
    // });
});


// API routes for cart operations (no authentication required for now)
Route::prefix('cart')->name('api.cart.')->group(function () {
    // Get cart contents
    Route::get('/contents', [ApiCartController::class, 'getContents'])->name('getContents');
    // Add product to cart
    Route::post('/add/{product}', [ApiCartController::class, 'add'])->name('add');
    // Update item quantity in cart
    Route::post('/update-quantity/{cartItem}', [ApiCartController::class, 'updateQuantity'])->name('updateQuantity');
    // Remove item from cart
    Route::post('/remove-item/{cartItem}', [ApiCartController::class, 'removeCartItem'])->name('removeItem');
    // Clear entire cart
    Route::post('/clear', [ApiCartController::class, 'clearCart'])->name('clear');
    // Apply discount coupon
    Route::post('/apply-coupon', [ApiCartController::class, 'applyCoupon'])->name('applyCoupon');
    // Remove discount coupon
    Route::post('/remove-coupon', [ApiCartController::class, 'removeCoupon'])->name('removeCoupon');
});


// Add placeOrder route if it's in api.php
Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('order.place')->middleware('api');

