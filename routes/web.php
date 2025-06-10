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

// Home page route
Route::get('/', [PageController::class, 'home'])->name('home');

// Example routes for other pages mentioned in the navigation/footer
// You would create corresponding methods in PageController for these
Route::get('/products', function () {
    return view('products'); // Assuming you'll create products.blade.php later
})->name('products');

Route::get('/about', function () {
    return view('about'); // Assuming you'll create about.blade.php later
})->name('about');

Route::get('/contact', function () {
    return view('contact'); // Assuming you'll create contact.blade.php later
})->name('contact');

Route::get('/blog', function () {
    return view('blog'); // Assuming you'll create blog.blade.php later
})->name('blog');

Route::get('/cart', function () {
    return view('cart'); // Assuming you'll create cart.blade.php later
})->name('cart');

Route::get('/faq', function () {
    return view('faq'); // Assuming you'll create faq.blade.php later
})->name('faq');

// Route for checkout page
Route::get('/checkout', function () {
    return view('checkout'); // Make sure you have checkout.blade.php
})->name('checkout');

// New route for the complete profile page (replaces old register route)
Route::get('/complete-profile', function () {
    return view('complete-profile'); // This assumes you have renamed register.blade.php to complete-profile.blade.php
})->name('complete-profile');

Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index']);

// Admin Panel Routes
// In a real application, you would use middleware to protect this route.
// Example: Route::middleware(['auth', 'admin'])->group(function () {
//     Route.get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
// });
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

