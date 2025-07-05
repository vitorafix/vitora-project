<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\CartServiceInterface; // تصحیح شد: استفاده از فضای نام صحیح
use App\Services\ImprovedCartService;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Repositories\Eloquent\CartRepository;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository;
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\StockManager;
use App\Services\Managers\CartValidator;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;
use Illuminate\Contracts\Events\Dispatcher;

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
                $app->make(ProductRepositoryInterface::class),
                $app->make(CartCacheManager::class),
                $app->make(StockManager::class),
                $app->make(CartValidator::class),
                $app->make(CartRateLimiter::class),
                $app->make(CartMetricsManager::class),
                $app->make(Dispatcher::class)
            );
        });

        // Binding برای ProductRepositoryInterface به ProductRepository
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

        // Binding برای CartRepositoryInterface به CartRepository
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);

        // Binding Managerها به صورت Singleton
        $this->app->singleton(CartCacheManager::class, function ($app) {
            return new CartCacheManager();
        });

        $this->app->singleton(StockManager::class, function ($app) {
            return new StockManager();
        });

        $this->app->singleton(CartValidator::class, function ($app) {
            return new CartValidator();
        });

        $this->app->singleton(CartRateLimiter::class, function ($app) {
            return new CartRateLimiter();
        });

        $this->app->singleton(CartMetricsManager::class, function ($app) {
            return new CartMetricsManager();
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
