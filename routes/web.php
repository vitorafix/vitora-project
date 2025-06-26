<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BlogController; // جدید: اضافه کردن BlogController

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home page route (مسیر صفحه اصلی فروشگاه شما)
Route::get('/', [PageController::class, 'home'])->name('home');

// Products routes (مسیرهای مربوط به محصولات)
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// Cart routes (مسیرهای مربوط به سبد خرید)
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/contents', [CartController::class, 'getContents'])->name('cart.contents');

// Order and Checkout Routes (مسیرهای مربوط به سفارش و تسویه حساب)
Route::get('/checkout', [OrderController::class, 'index'])->name('checkout.index');
Route::post('/order/place', [OrderController::class, 'placeOrder'])->name('orders.place');
Route::get('/order-confirmation/{order}', [OrderController::class, 'confirmation'])->name('orders.confirmation');

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

Route::get('/complete-profile', function () {
    return view('complete-profile');
})->name('complete-profile');

Route::get('/search', [SearchController::class, 'index']);

// Admin Panel Routes
// Example: Route::middleware(['auth', 'admin'])->group(function () {
//     Route.get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
// });
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');


// Breeze Auth Routes (مسیرهای احراز هویت Breeze)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
