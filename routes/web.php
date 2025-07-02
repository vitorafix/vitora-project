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
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\RegisterController; // ایمپورت صحیح
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProfileCompletionController; // ایمپورت صحیح
use App\Http\Middleware\EnsureProfileIsCompleted;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the ServiceServiceProvider and all of them will
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
Route::put('/cart/update/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/contents', [CartController::class, 'getContents'])->name('cart.contents');

// About, Contact, Blog, FAQ routes
Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Blog routes (مسیرهای مربوط به بلاگ)
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{id}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/faq', function () {
    return view('faq');
})->name('faq');

// مسیر جستجو
Route::get('/search', [SearchController::class, 'search'])->name('search');

// Admin Panel Routes
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');


// مسیرهای احراز هویت با شماره موبایل و OTP
Route::prefix('auth')->name('auth.')->group(function () {
    // نمایش فرم ورود/ثبت‌نام با موبایل
    Route::get('/mobile-login', [MobileAuthController::class, 'showMobileLoginForm'])->name('mobile-login-form');
    // ارسال کد تایید (OTP)
    Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');
    // نمایش فرم تایید کد
    Route::get('/verify-otp', [MobileAuthController::class, 'showVerifyOtpForm'])->name('verify-otp-form');
    // تایید کد و ورود/ثبت‌نام (این متد در MobileAuthController، کاربران جدید را به register-form هدایت می‌کند)
    Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtp'])->name('verify-otp');
    // خروج کاربر
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');

    // مسیرهای جدید ثبت‌نام (برای کاربران جدید)
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register-form'); // نمایش فرم ثبت‌نام نام و نام خانوادگی
    Route::post('/register', [RegisterController::class, 'register'])->name('register'); // ثبت‌نام نهایی کاربر
});


// مسیرهایی که نیاز به احراز هویت دارند
Route::middleware('auth')->group(function () {
    // مسیرهای مربوط به پروفایل
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // مسیر جدید برای به روز رسانی رمز عبور (اگرچه با OTP، ممکن است برای تغییر ایمیل/رمز عبور ثانویه باشد)
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // مسیر برای نمایش سفارشات کاربر
    Route::get('/profile/orders', [OrderController::class, 'index'])->name('profile.orders.index');

    // مسیرهای منابع برای آدرس‌های کاربر
    Route::resource('profile/addresses', AddressController::class)->except(['show'])->names([
        'index' => 'profile.addresses.index',
        'create' => 'profile.addresses.create',
        'store' => 'profile.addresses.store',
        'edit' => 'profile.addresses.edit',
        'update' => 'profile.addresses.update',
        'destroy' => 'profile.addresses.destroy',
    ]);

    // مسیر تکمیل پروفایل (این مسیرها نباید تحت میدل‌ور EnsureProfileIsCompleted باشند)
    // این مسیرها اکنون به ProfileCompletionController اشاره می‌کنند.
    Route::get('/profile/complete', [ProfileCompletionController::class, 'showCompletionForm'])->name('profile.complete');
    Route::post('/profile/complete', [ProfileCompletionController::class, 'storeCompletionForm'])->name('profile.complete.store');


    // مسیرهای حساس که نیاز به تکمیل پروفایل دارند
    // این مسیرها توسط میدل‌ور EnsureProfileIsCompleted محافظت می‌شوند.
    Route::middleware([EnsureProfileIsCompleted::class])->group(function () {
        // داشبورد
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        // مسیرهای مربوط به سفارش و تسویه حساب
        Route::get('/checkout', [OrderController::class, 'index'])->name('checkout.index');
        Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('orders.place');
    });
});

// require __DIR__.'/auth.php'; // این خط را در صورت استفاده از احراز هویت فقط با موبایل، کامنت یا حذف کنید.
