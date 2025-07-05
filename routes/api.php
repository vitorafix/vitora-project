<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController; // اضافه شده: برای استفاده از CartController

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
Route::middleware(['api', 'throttle:cart_add'])->group(function () { // 'api' middleware for API routes, 'throttle' for rate limiting
    Route::post('/cart/add', [CartController::class, 'add']); // Example add route
    // Add other cart routes here (update, remove, clear, contents)
    // Route::get('/cart/contents', [CartController::class, 'getContents']);
    // Route::put('/cart/update/{cartItem}', [CartController::class, 'updateQuantity']);
    // Route::delete('/cart/remove/{cartItem}', [CartController::class, 'removeCartItem']);
    // Route::post('/cart/clear', [CartController::class, 'clearCart']);
});

// Define the 'cart_add' throttle in App\Providers\RouteServiceProvider.php
// protected function configureRateLimiting(): void
// {
//     RateLimiter::for('cart_add', function (Request $request) {
//         return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
//     });
// }
