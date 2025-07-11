<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Api\ApiCartController; // Changed: Use the renamed ApiCartController
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
