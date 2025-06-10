<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminController; // این خط برای وارد کردن AdminController است

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

// مسیر صفحه اصلی
Route::get('/', [PageController::class, 'home'])->name('home');

// مسیرهای مربوط به صفحات دیگر (محصولات، درباره ما، تماس با ما و ...)
Route::get('/products', function () {
    return view('products');
})->name('products');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/blog', function () {
    return view('blog');
})->name('blog');

Route::get('/cart', function () {
    return view('cart');
})->name('cart');

Route::get('/faq', function () {
    return view('faq');
})->name('faq');

// مسیر صفحه تسویه حساب
Route::get('/checkout', function () {
    return view('checkout');
})->name('checkout');

// مسیر صفحه ثبت نام
Route::get('/register', function () {
    return view('register');
})->name('register');

// مسیر جستجو
Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index']);

// مسیرهای پنل مدیریت
// در یک برنامه واقعی، برای محافظت از این مسیر از middleware استفاده می‌کنید.
// مثال: Route::middleware(['auth', 'admin'])->group(function () {
//     Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
// });
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
