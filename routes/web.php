<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Web\CartController;
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
use Illuminate\Support\Facades\DB; // 🟢 NEW: Use the default Laravel DB Facade
use App\Models\AnalyticsEvent; // 🟢 NEW: Use the AnalyticsEvent model

// عمومی‌ترین مسیرها
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/search', [SearchController::class, 'search'])->name('search');

// گروه مسیرهای احراز هویت (با middleware 'web' برای پشتیبانی از Session و CSRF)
Route::middleware(['web'])->prefix('auth')->name('auth.')->group(function () {
    // مسیر نمایش فرم ورود با موبایل
    Route::get('/mobile-login', [MobileAuthController::class, 'showMobileLoginForm'])
        ->name('mobile-login-form')
        ->middleware(\App\Http\Middleware\AuthenticateOnceWithBasicAuth::class); // اضافه شدن این میدل‌ویر

    // مسیر send-otp به routes/api.php منتقل شده بود، حالا به web.php برگردانده می‌شود.
    Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');

    // مسیر نمایش فرم تأیید OTP
    Route::get('/verify-otp-form', [MobileAuthController::class, 'showOtpVerifyForm'])
        ->name('verify-otp-form')
        ->middleware(\App\Http\Middleware\AuthenticateOnceWithBasicAuth::class); // اضافه شدن این میدل‌ویر

    Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtpAndLogin'])->name('verify-otp'); // تغییر نام متد به verifyOtpAndLogin

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register-form');
    Route::post('/register', [RegisterController::class, 'register'])->name('register');
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
    Route::post('/change-mobile-number', [MobileAuthController::class, 'changeMobileNumber'])->name('change-mobile-number');
});

// مسیرهای سبد خرید وب (با middleware 'web' به صورت ضمنی)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{product}', [CartController::class, 'add'])->name('add');
    Route::put('/update/{cartItem}', [CartController::class, 'update'])->name('update');
    Route::delete('/remove/{cartItem}', [CartController::class, 'remove'])->name('remove');
    Route::post('/clear', [CartController::class, 'clear'])->name('clear');
    Route::post('/apply-coupon', [CartController::class, 'applyCoupon'])->name('apply-coupon');
    Route::post('/remove-coupon', [CartController::class, 'removeCoupon'])->name('remove-coupon');
});

// مسیرهای نیازمند احراز هویت و تکمیل پروفایل
Route::middleware(['auth', 'profile.completed'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/complete-profile', [ProfileCompletionController::class, 'showCompletionForm'])->name('profile.completion.form');
    Route::post('/complete-profile', [ProfileCompletionController::class, 'storeCompletionForm'])->name('profile.completion.store');

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

// مسیرهای پنل مدیریت (نیازمند احراز هویت و نقش admin)
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

// مسیرهای پنل ویرایشگر (نیازمند احراز هویت و نقش editor)
Route::middleware(['auth', 'role:editor'])->prefix('editor')->name('editor.')->group(function () {
    Route::get('/dashboard', [EditorDashboardController::class, 'index'])->name('dashboard');
    Route::resource('/posts', EditorPostController::class);
});

// Route to test MongoDB connection (نسخه بهینه شده)
Route::get('/test-mongo', function () {
    try {
        // تست اتصال پایه
        $connection = \DB::connection('mongodb');
        $result = $connection->getMongoDB()->command(['ping' => 1]);
        
        // استفاده از مدل درست:
        $count = AnalyticsEvent::count();

        return response()->json([
            'status' => 'success',
            'message' => 'MongoDB connection successful',
            'analytics_events_count' => $count, // تغییر نام کلید به analytics_events_count
            'ping' => $result->toArray()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine(), // اضافه شدن خط خطا
            'file' => $e->getFile() // اضافه شدن فایل خطا
        ], 500); // اضافه شدن کد وضعیت 500
    }
});
