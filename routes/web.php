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
use App\Http\Controllers\Editor\EditorDashboardController; // اضافه کردن کنترلر داشبورد ویرایشگر
use App\Http\Controllers\Editor\PostController as EditorPostController; // اضافه کردن کنترلر پست ویرایشگر
// اگر CommentController و CategoryController مخصوص ویرایشگر دارید، آنها را نیز اضافه کنید
// use App\Http\Controllers\Editor\CommentController as EditorCommentController;
// use App\Http\Controllers\Editor\CategoryController as EditorCategoryController;


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

// محصولات (قابل مشاهده برای همه)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// سبد خرید (مسیرهای مربوط به نمایش صفحه و عملیات از طریق فرم‌های وب)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

// صفحات ثابت
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
Route::view('/faq', 'faq')->name('faq');
Route::get('/rules', function () {
    return view('rules');
})->name('rules');

// بلاگ
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{id}', [BlogController::class, 'show'])->name('blog.show');

// جستجو
Route::get('/search', [SearchController::class, 'search'])->name('search');

// روت‌های احراز هویت موبایلی و OTP
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('/mobile-login', [MobileAuthController::class, 'showMobileLoginForm'])->name('mobile-login-form');
    Route::get('/login', [MobileAuthController::class, 'showMobileLoginForm'])->name('login');
    Route::post('/send-otp', [MobileAuthController::class, 'sendOtp'])->name('send-otp');
    Route::get('/verify-otp', [MobileAuthController::class, 'showVerifyOtpForm'])->name('verify-otp-form');
    Route::post('/verify-otp', [MobileAuthController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/logout', [MobileAuthController::class, 'logout'])->name('logout');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register-form');
    Route::post('/register', [RegisterController::class, 'register'])->name('register');
});

// روت‌هایی که نیاز به احراز هویت دارند (برای همه کاربران لاگین شده)
Route::middleware('auth')->group(function () {

    Route::view('/dashboard', 'dashboard')->name('dashboard'); // این داشبورد عمومی است

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/personal', [ProfileController::class, 'updateProfile'])->name('profile.personal_update');
    Route::post('/profile/legal-info', [ProfileController::class, 'storeLegalInfo'])->name('profile.legal-info.store');
    Route::put('/profile/birth-date', [ProfileController::class, 'updateBirthDate'])->name('profile.birth-date.update');
    Route::put('/profile/mobile', [ProfileController::class, 'updateMobile'])->name('profile.mobile.update');
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
    Route::post('/profile/addresses/{address}/set-default', [AddressController::class, 'setDefault'])->name('profile.addresses.set-default');

    Route::get('/profile/complete', [ProfileCompletionController::class, 'showCompletionForm'])->name('profile.complete');
    Route::post('/profile/complete', [ProfileCompletionController::class, 'storeCompletionForm'])->name('profile.complete.store');

    Route::middleware([EnsureProfileIsCompleted::class])->group(function () {
        Route::get('/checkout', [OrderController::class, 'index'])->name('checkout.index');
        Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('orders.place');
    });

    // --- اعمال میدل‌ورهای Spatie برای مدیریت دسترسی‌ها ---

    // پنل ادمین: فقط کاربران با نقش 'admin' دسترسی دارند
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        // مسیرهای مدیریت محصولات: نیاز به نقش 'admin' یا مجوزهای خاص
        Route::prefix('admin/products')->name('products.')->group(function () {
            // این مسیرها نیاز به مجوز 'create post' دارند
            Route::get('/create', [ProductController::class, 'create'])->name('create')->middleware('permission:create post');
            Route::post('/', [ProductController::class, 'store'])->name('store')->middleware('permission:create post');

            // این مسیرها نیاز به مجوز 'edit post' دارند
            Route::get('/{product:slug}/edit', [ProductController::class, 'edit'])->name('edit')->middleware('permission:edit post');
            Route::put('/{product:slug}', [ProductController::class, 'update'])->name('update')->middleware('permission:edit post');

            // این مسیر نیاز به مجوز 'delete post' دارد
            Route::delete('/{product:slug}', [ProductController::class, 'destroy'])->name('destroy')->middleware('permission:delete post');
        });

        // مثال: مسیرهای مدیریت بلاگ برای ادمین (اگر وجود دارند)
        // Route::prefix('admin/blog')->name('admin.blog.')->group(function () {
        //     Route::get('/', [BlogController::class, 'adminIndex'])->name('index')->middleware('permission:view posts');
        //     Route::get('/create', [BlogController::class, 'adminCreate'])->name('create')->middleware('permission:create post');
        // });
    });

    // پنل ویرایشگر: فقط کاربران با نقش 'editor' دسترسی دارند
    Route::middleware(['role:editor'])->prefix('editor')->name('editor.')->group(function () {
        Route::get('/dashboard', [EditorDashboardController::class, 'index'])->name('dashboard');

        // مسیرهای مدیریت پست‌ها برای ویرایشگر
        // از EditorPostController استفاده می‌کنیم
        Route::resource('/posts', EditorPostController::class);
        // اگر CommentController و CategoryController مخصوص ویرایشگر دارید، روت‌های آنها را اینجا اضافه کنید
        // Route::resource('/comments', EditorCommentController::class)->only(['index', 'destroy']);
        // Route::resource('/categories', EditorCategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    });

});
