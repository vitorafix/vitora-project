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
use App\Http\Controllers\Editor\EditorDashboardController;
use App\Http\Controllers\Editor\PostController as EditorPostController;

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

// صفحه درباره ما ✅
Route::get('/about', [PageController::class, 'about'])->name('about');

// صفحه تماس ✅ - اضافه شده برای رفع خطا
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

// محصولات (قابل مشاهده برای همه)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/search', [SearchController::class, 'search'])->name('search');

// مسیرهای احراز هویت با OTP
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/login', [MobileAuthController::class, 'showMobileLoginForm'])->name('mobile-login-form');
    Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');
    Route::get('/verify-otp-form', [MobileAuthController::class, 'showOtpVerifyForm'])->name('verify-otp-form');
    Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register-form');
    Route::post('/register', [RegisterController::class, 'register'])->name('register');
});

// مسیرهای عمومی که نیاز به احراز هویت دارند
Route::middleware(['auth', 'profile.completed'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/complete-profile', [ProfileCompletionController::class, 'showCompletionForm'])->name('profile.completion.form');
    Route::post('/complete-profile', [ProfileCompletionController::class, 'completeProfile'])->name('profile.completion.store');

    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
        Route::post('/update/{product}', [CartController::class, 'update'])->name('update');
        Route::delete('/remove/{product}', [CartController::class, 'remove'])->name('remove');
        Route::post('/clear', [CartController::class, 'clear'])->name('clear');
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

// مسیرهای پنل‌های مدیریتی
Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
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

    Route::middleware(['role:editor'])->prefix('editor')->name('editor.')->group(function () {
        Route::get('/dashboard', [EditorDashboardController::class, 'index'])->name('dashboard');
        Route::resource('/posts', EditorPostController::class);
    });
});
