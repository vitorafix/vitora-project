<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Api\CartController as WebCartController; // اصلاح شده: استفاده از نام مستعار برای CartController موجود در پوشه Api
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Controllers\Editor\EditorDashboardController;
use App\Http\Controllers\Editor\PostController as EditorPostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CheckoutController;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
// مسیر را برای استفاده از ID محصول تغییر دهید
// Change the route to use product ID
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show'); // حذف ":slug"

Route::get('/search', [SearchController::class, 'search'])->name('search');

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/login', [MobileAuthController::class, 'showMobileLoginForm'])->name('mobile-login-form');
    Route::post('/send-otp', [MobileAuthController::class, 'send-otp'])->name('send-otp');
    Route::get('/verify-otp-form', [MobileAuthController::class, 'showOtpVerifyForm'])->name('verify-otp-form');
    Route::post('/verify-otp', [MobileAuthController::class, 'verify-otp'])->name('verify-otp');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register-form');
    Route::post('/register', [RegisterController::class, 'register'])->name('register');
    // اضافه شدن روت logout برای رفع خطای RouteNotFoundException در ویوها
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
});

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [WebCartController::class, 'index'])->name('index'); // اصلاح شده: استفاده از WebCartController
    Route::get('/contents', [WebCartController::class, 'getContents'])->name('contents'); // اصلاح شده: استفاده از WebCartController
    Route::post('/add/{product}', [WebCartController::class, 'add'])->name('add'); // اصلاح شده: استفاده از WebCartController
    Route::put('/update/{cartItem}', [WebCartController::class, 'updateQuantity'])->name('update'); // اصلاح شده: استفاده از WebCartController
    Route::delete('/remove/{cartItem}', [WebCartController::class, 'removeCartItem'])->name('remove'); // اصلاح شده: استفاده از WebCartController
    Route::post('/clear', [WebCartController::class, 'clearCart'])->name('clear'); // اصلاح شده: استفاده از WebCartController
    Route::post('/apply-coupon', [WebCartController::class, 'applyCoupon'])->name('apply-coupon'); // اصلاح شده: استفاده از WebCartController
    // اصلاح نام متد از remove-coupon به removeCoupon برای مطابقت با نام متد در کنترلر
    Route::post('/remove-coupon', [WebCartController::class, 'removeCoupon'])->name('remove-coupon'); // اصلاح شده: استفاده از WebCartController
});

Route::middleware(['auth', 'profile.completed'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/complete-profile', [ProfileCompletionController::class, 'showCompletionForm'])->name('profile.completion.form');
    Route::post('/complete-profile', [ProfileCompletionController::class, 'completeProfile'])->name('profile.completion.store');

    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/place-order', [CheckoutController::class, 'placeOrder'])->name('placeOrder');
    });

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/', [OrderController::class, 'store'])->name('store');
    });

    Route::resource('addresses', AddressController::class);

    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/{post:slug}', [BlogController::class, 'show'])->name('show');
    });
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'adminIndex'])->name('index')->middleware('permission:view posts');
        Route::get('/create', [ProductController::class, 'create'])->name('create')->middleware('permission:create post');
        Route::post('/', [ProductController::class, 'store'])->name('store')->middleware('permission:create post');
        Route::get('/{product:slug}/edit', [ProductController::class, 'edit'])->name('edit')->middleware('permission:edit post');
        Route::put('/{product:slug}', [ProductController::class, 'update'])->name('update')->middleware('permission:edit post');
        Route::delete('/{product:slug}', [ProductController::class, 'destroy'])->name('destroy')->middleware('permission:delete post');
    });
});

Route::middleware(['auth', 'role:editor'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/dashboard', [EditorDashboardController::class, 'index'])->name('dashboard');
    Route::resource('/posts', EditorPostController::class);
});
