<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Services\CartServiceInterface;
use App\Services\ImprovedCartService; // ایمپورت ImprovedCartService
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Repositories\Eloquent\CartRepository; // ایمپورت کردن پیاده‌سازی CartRepository
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository; // ایمپورت کردن پیاده‌سازی ProductRepository
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\StockManager;
use App\Services\Managers\CartValidator;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;
use Illuminate\Contracts\Events\Dispatcher; // برای تزریق Dispatcher

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * سرویس‌های برنامه را ثبت می‌کند.
     */
    public function register(): void
    {
        // Binding CartServiceInterface به ImprovedCartService
        // این تضمین می‌کند که هرجا CartServiceInterface درخواست شود، نمونه‌ای از ImprovedCartService تزریق شود.
        $this->app->bind(CartServiceInterface::class, function ($app) {
            return new ImprovedCartService(
                $app->make(CartRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class), // تزریق ProductRepositoryInterface
                $app->make(CartCacheManager::class),
                $app->make(StockManager::class),
                $app->make(CartValidator::class),
                $app->make(CartRateLimiter::class),
                $app->make(CartMetricsManager::class),
                $app->make(Dispatcher::class) // تزریق Dispatcher
            );
        });

        // Binding برای ProductRepositoryInterface به ProductRepository
        // این خط از فایل قبلی شما حفظ شده است.
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

        // Binding برای CartRepositoryInterface به CartRepository
        // این خط از فایل قبلی شما حفظ شده است.
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);

        // Binding Managerها به صورت Singleton
        // این تضمین می‌کند که تنها یک نمونه از هر Manager در طول چرخه حیات درخواست ایجاد شود.
        // وابستگی‌های Managerها (مانند 'cache' یا 'cache.store') نیز تزریق می‌شوند.
        $this->app->singleton(CartCacheManager::class, function ($app) {
            return new CartCacheManager(); // Constructor آن نیازی به تزریق مستقیم Cache ندارد، خودش از Facade استفاده می‌کند.
        });

        $this->app->singleton(StockManager::class, function ($app) {
            return new StockManager(); // Constructor آن نیازی به تزریق مستقیم Cache ندارد، خودش از Facade استفاده می‌کند.
        });

        $this->app->singleton(CartValidator::class, function ($app) {
            return new CartValidator(); // Constructor ساده و بدون وابستگی پیچیده
        });

        $this->app->singleton(CartRateLimiter::class, function ($app) {
            return new CartRateLimiter(); // Constructor ساده و بدون وابستگی پیچیده
        });

        $this->app->singleton(CartMetricsManager::class, function ($app) {
            return new CartMetricsManager(); // Constructor ساده و بدون وابستگی پیچیده
        });
    }

    /**
     * Bootstrap any application services.
     * سرویس‌های برنامه را راه‌اندازی می‌کند.
     */
    public function boot(): void
    {
        //
    }
}
