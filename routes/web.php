<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Auth\MobileAuthController; // ایمپورت کردن کنترلر جدید MobileAuthController

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home page route (مسیر صفحه اصلی فروشگاه شما)
Route::get('/', [PageController::class, 'home'])->name('home');

// Products routes (مسیرهای مربوط به محصولات)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// Cart routes (مسیرهای مربوط به سبد خرید)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
// اصلاح شد: متد از POST به PUT تغییر یافت تا با درخواست جاوااسکریپت مطابقت داشته باشد
Route::put('/cart/update/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/contents', [CartController::class, 'getContents'])->name('cart.contents');

// Order and Checkout Routes (مسیرهای مربوط به سفارش و تسویه حساب)
Route::get('/checkout', [OrderController::class, 'index'])->name('checkout.index'); // نمایش صفحه تسویه حساب
Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('orders.place'); // ثبت سفارش
Route::get('/order/confirmation/{order}', [OrderController::class, 'showConfirmation'])->name('orders.confirmation'); // صفحه تایید سفارش

// About, Contact, Blog, FAQ routes
Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Blog routes (مسیرهای مربوط به بلاگ) - اصلاح شده برای استفاده از کنترلر
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index'); // استفاده از کنترلر
Route::get('/blog/{id}', [BlogController::class, 'show'])->name('blog.show'); // استفاده از کنترلر

Route::get('/faq', function () {
    return view('faq');
})->name('faq');

// مسیر قبلی /complete-profile که به یک View ریدایرکت می‌شد حذف شده و با متد کنترلر جایگزین می‌شود.
// Route::get('/complete-profile', function () {
//     return view('complete-profile');
// })->name('complete-profile');

// اصلاح شد: اضافه کردن name('search') برای مسیر جستجو
Route::get('/search', [SearchController::class, 'search'])->name('search'); // Changed method name to 'search'

// Admin Panel Routes
// Example: Route::middleware(['auth', 'admin'])->group(function () {
//     Route.get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
// });
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');


// Breeze Auth Routes (مسیرهای احراز هویت Breeze)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// مسیرهایی که نیاز به احراز هویت دارند
Route::middleware('auth')->group(function () {
    // مسیرهای مربوط به پروفایل
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // مسیر برای تکمیل پروفایل پس از اولین خرید
    Route::get('/profile/complete', [ProfileController::class, 'completeProfileForm'])->name('profile.complete'); // متد جدید برای نمایش فرم تکمیل پروفایل
    Route::post('/profile/complete', [ProfileController::class, 'completeProfile'])->name('profile.complete.store'); // برای ذخیره اطلاعات تکمیل پروفایل
});

// مسیرهای جدید برای احراز هویت با شماره موبایل و OTP
Route::prefix('auth')->name('auth.')->group(function () {
    // نمایش فرم ورود/ثبت‌نام با موبایل
    Route::get('/mobile-login', [MobileAuthController::class, 'showMobileLoginForm'])->name('mobile-login-form');
    // ارسال کد تایید (OTP)
    Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');
    // نمایش فرم تایید کد
    Route::get('/verify-otp', [MobileAuthController::class, 'showVerifyOtpForm'])->name('verify-otp-form');
    // تایید کد و ورود/ثبت‌نام
    Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtp'])->name('verify-otp');
    // خروج کاربر
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
});


// require __DIR__.'/auth.php'; // این خط را حذف یا کامنت کنید تا احراز هویت پیش‌فرض لاراول غیرفعال شود.
