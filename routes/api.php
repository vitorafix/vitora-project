<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Api\ApiCartController; // مطمئن شوید این ایمپورت شده است
use App\Http\Controllers\OrderController; // مطمئن شوید این ایمپورت شده است

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

// مسیر برای ورود به سشن با استفاده از JWT (برای صفحات وب)
// این مسیر نیازی به middleware 'jwt.auth' ندارد زیرا وظیفه آن احراز هویت JWT دریافتی
// و سپس لاگین کردن کاربر در سشن وب لاراول است.
Route::post('auth/jwt-login', [MobileAuthController::class, 'jwtLogin'])->name('api.auth.jwt-login');

// مسیر محتویات سبد خرید برای همه (بدون نیاز به JWT)
// این مسیر به مهمان‌ها اجازه می‌دهد تا محتویات سبد خرید خود را مشاهده کنند.
Route::get('/cart/contents', [ApiCartController::class, 'getContents'])->name('api.cart.getContents');

// گروه مسیرهای API که از میدل‌ویر 'jwt.auth' (با درایور JWT) استفاده می‌کنند.
// تمام مسیرهای داخل این گروه نیاز به یک توکن JWT معتبر دارند.
Route::middleware('jwt.auth')->group(function () {
    
    // مسیر خروج کاربر از API (با JWT)
    Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->name('api.auth.logout');

    // مسیرهای محافظت شده سبد خرید برای کاربران لاگین شده
    // این مسیرها عملیات حساس سبد خرید را شامل می‌شوند که نیاز به احراز هویت دارند.
    Route::prefix('cart')->name('api.cart.')->group(function () {
        Route::post('/apply-coupon', [ApiCartController::class, 'applyCoupon'])->name('applyCoupon');
        Route::post('/remove-coupon', [ApiCartController::class, 'removeCoupon'])->name('removeCoupon');
        Route::post('/add/{product}', [ApiCartController::class, 'add'])->name('add');
        Route::post('/update-quantity/{cartItem}', [ApiCartController::class, 'updateQuantity'])->name('updateQuantity');
        Route::post('/remove-item/{cartItem}', [ApiCartController::class, 'removeCartItem'])->name('removeItem');
        Route::post('/clear', [ApiCartController::class, 'clearCart'])->name('clear');
    });

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
