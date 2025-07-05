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
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProfileCompletionController;
use App\Http\Middleware\EnsureProfileIsCompleted;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| اینجا روت‌های وب‌سایت ثبت می‌شوند.
| همه روت‌ها در گروه میدل‌ور "web" قرار دارند.
|
*/

// صفحه اصلی
Route::get('/', [PageController::class, 'home'])->name('home');

// محصولات
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// سبد خرید
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/update/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/contents', [CartController::class, 'getContents'])->name('cart.contents');

// مسیر قبلی برای به‌روزرسانی تعداد آیتم سبد خرید که اکنون استفاده نمی‌شود، حذف شد.
// Route::post('/api/cart/update-quantity', [CartController::class, 'updateQuantity']);


// صفحات ثابت
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
Route::view('/faq', 'faq')->name('faq');
// مسیر جدید برای صفحه قوانین و مقررات
Route::get('/rules', function () {
    return view('rules');
})->name('rules');


// بلاگ
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{id}', [BlogController::class, 'show'])->name('blog.show');

// جستجو
Route::get('/search', [SearchController::class, 'search'])->name('search');

// پنل ادمین
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

// روت‌های احراز هویت موبایلی و OTP
Route::prefix('auth')->name('auth.')->group(function () {
    // فرم ورود موبایل
    Route::get('/mobile-login', [MobileAuthController::class, 'showMobileLoginForm'])->name('mobile-login-form');

    // اضافه کردن روت /login به همین متد، برای رفع خطای ویو auth.login
    Route::get('/login', [MobileAuthController::class, 'showMobileLoginForm'])->name('login');

    // ارسال OTP - Rate Limiting اکنون توسط RateLimitService در کنترلر مدیریت می‌شود
    Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');

    // فرم تایید OTP
    Route::get('/verify-otp', [MobileAuthController::class, 'showVerifyOtpForm'])->name('verify-otp-form');

    // تایید OTP - Rate Limiting اکنون توسط RateLimitService در کنترلر مدیریت می‌شود
    Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtp'])->name('verify-otp');

    // خروج
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');

    // فرم ثبت‌نام
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register-form');

    // ثبت‌نام
    Route::post('/register', [RegisterController::class, 'register'])->name('register');
});

// توجه: مشکل "Class "App\Http\Controllers\Auth\RegisteredUserController" does not exist"
// به احتمال زیاد از فایل 'auth.php' نشأت می‌گیرد.
// اگر از Laravel Breeze یا Jetstream استفاده نمی‌کنید و تمام مسیرهای احراز هویت را
// در همین فایل 'web.php' مدیریت می‌کنید، می‌توانید خط زیر را کامنت کنید.
// در غیر این صورت، باید فایل 'auth.php' را باز کرده و هرگونه ارجاع به
// 'RegisteredUserController' را به 'RegisterController' تغییر دهید.
require __DIR__.'/auth.php';

// روت‌هایی که نیاز به احراز هویت دارند
Route::middleware('auth')->group(function () {

    // داشبورد را از داخل گروه EnsureProfileIsCompleted خارج می‌کنیم
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // پروفایل کاربر (روت نمایش صفحه پروفایل جدید)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show'); // روت نمایش صفحه پروفایل

    // روت‌های به‌روزرسانی اطلاعات پروفایل سفارشی (برای جلوگیری از تداخل با Breeze/Jetstream)
    Route::put('/profile/personal', [ProfileController::class, 'updateProfile'])->name('profile.personal_update'); // نام روت و مسیر تغییر یافت
    Route::post('/profile/legal-info', [ProfileController::class, 'storeLegalInfo'])->name('profile.legal-info.store');
    Route::put('/profile/birth-date', [ProfileController::class, 'updateBirthDate'])->name('profile.birth-date.update');
    Route::put('/profile/mobile', [ProfileController::class, 'updateMobile'])->name('profile.mobile.update');

    // روت‌های پیش‌فرض پروفایل Breeze/Jetstream (حفظ شده‌اند)
    // این روت‌ها معمولاً برای به‌روزرسانی اطلاعات اصلی حساب (مثل ایمیل و پسورد) استفاده می‌شوند
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    Route::get('/profile/orders', [OrderController::class, 'index'])->name('profile.orders.index');

    Route::resource('profile/addresses', AddressController::class)->except(['show'])->names([
        'index' => 'profile.addresses.index',
        'create' => 'profile.addresses.create',
        'store' => 'profile.addresses.store',
        'edit' => 'profile.addresses.edit',
        'update' => 'profile.addresses.update',
        'destroy' => 'profile.addresses.destroy',
    ]);

    // مسیر جدید برای تنظیم آدرس پیش‌فرض
    Route::post('/profile/addresses/{address}/set-default', [AddressController::class, 'setDefault'])->name('profile.addresses.set-default');

    // تکمیل پروفایل
    Route::get('/profile/complete', [ProfileCompletionController::class, 'showCompletionForm'])->name('profile.complete');
    Route::post('/profile/complete', [ProfileCompletionController::class, 'storeCompletionForm'])->name('profile.complete.store');

    // مسیرهایی که نیاز به تکمیل پروفایل دارند (برای مثال، صفحه پرداخت)
    Route::middleware([EnsureProfileIsCompleted::class])->group(function () {
        Route::get('/checkout', [OrderController::class, 'index'])->name('checkout.index');
        Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('orders.place');
    });
});
