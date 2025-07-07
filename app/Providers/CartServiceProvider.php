<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\CartServiceInterface;
use App\Services\ImprovedCartService;
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\StockManager; // Corrected: Used StockManager
use App\Services\Managers\CartValidator; // Corrected: Used CartValidator
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Repositories\Eloquent\CartRepository;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository;
use App\Contracts\ProductServiceInterface; // Added: For injection into ImprovedCartService
use App\Services\Contracts\CartCleanupServiceInterface; // Added: For injection
use App\Services\Contracts\CartItemManagementServiceInterface; // Added: For injection
use App\Services\Contracts\CartBulkUpdateServiceInterface; // Added: For injection
use App\Services\Contracts\CartClearServiceInterface; // Added: For injection
use App\Contracts\Services\CouponService; // New: Import CouponService contract
use App\Services\CartMergeService; // Added: For injection
use Illuminate\Contracts\Events\Dispatcher;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind CartServiceInterface to ImprovedCartService
        $this->app->bind(CartServiceInterface::class, function ($app) {
            return new ImprovedCartService(
                $app->make(CartRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class),
                $app->make(ProductServiceInterface::class), // Added: Inject ProductServiceInterface
                $app->make(CartCacheManager::class),
                $app->make(StockManager::class), // Corrected: Use StockManager
                $app->make(CartValidator::class), // Corrected: Use CartValidator
                $app->make(CartRateLimiter::class),
                $app->make(CartMetricsManager::class),
                $app->make(Dispatcher::class),
                // New: Inject other delegated services
                $app->make(CartMergeService::class),
                $app->make(CartCleanupServiceInterface::class),
                $app->make(CartItemManagementServiceInterface::class),
                $app->make(CartBulkUpdateServiceInterface::class),
                $app->make(CartClearServiceInterface::class),
                $app->make(CouponService::class) // New: Inject CouponService
            );
        });

        // Bind Repositories
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

        // Singleton Managers
        $this->app->singleton(CartCacheManager::class, function ($app) {
            return new CartCacheManager(config('cart.cache_ttl', 3600));
        });

        $this->app->singleton(StockManager::class, function ($app) {
            return new StockManager(
                config('cart.stock_check_enabled', true),
                config('cart.stock_reservation_minutes', 15)
            );
        });

        $this->app->singleton(CartValidator::class, function ($app) {
            return new CartValidator(
                config('cart.max_items_per_cart', 100),
                config('cart.max_quantity_per_item', 999)
            );
        });

        $this->app->singleton(CartRateLimiter::class, function ($app) {
            return new CartRateLimiter(config('cart.rate_limit_cooldown', 2));
        });

        $this->app->singleton(CartMetricsManager::class);

        // New: Bind CouponService
        $this->app->bind(CouponService::class, \App\Services\CouponService::class);

        // New: Bind other delegated services (if not already bound elsewhere)
        $this->app->bind(CartCleanupServiceInterface::class, \App\Services\CartCleanupService::class);
        $this->app->bind(CartItemManagementServiceInterface::class, \App\Services\CartItemManagementService::class);
        $this->app->bind(CartBulkUpdateServiceInterface::class, \App\Services\CartBulkUpdateService::class);
        $this->app->bind(CartClearServiceInterface::class, \App\Services\CartClearService::class);
        $this->app->bind(CartMergeService::class, \App\Services\CartMergeService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/cart.php' => config_path('cart.php'),
        ], 'cart-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\CleanupExpiredCartsCommand::class,
            ]);
        }
    }
}
