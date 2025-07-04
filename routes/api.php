<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController; // اگر نیاز به API برای ثبت‌نام دارید

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Example API route for authenticated users
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for mobile authentication (OTP)
Route::prefix('auth')->name('api.auth.')->group(function () {
    // Send OTP via API
    Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');

    // Verify OTP via API
    Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtp'])->name('verify-otp');

    // Optional: API route for mobile registration
    // Route::post('/register', [RegisterController::class, 'register'])->name('register');
});
