<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Contracts\CartServiceInterface;
use App\Services\ImprovedCartService;
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\StockManager;
use App\Services\Managers\CartValidator;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Repositories\Eloquent\CartRepository;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository;
use App\Contracts\ProductServiceInterface; // اضافه شده: برای تزریق به ImprovedCartService
use Illuminate\Contracts\Events\Dispatcher;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CartServiceInterface::class, function ($app) {
            return new ImprovedCartService(
                $app->make(CartRepositoryInterface::class),
                $app->make(ProductRepositoryInterface::class),
                $app->make(ProductServiceInterface::class), // اضافه شده: تزریق ProductServiceInterface
                $app->make(CartCacheManager::class),
                $app->make(StockManager::class),
                $app->make(CartValidator::class),
                $app->make(CartRateLimiter::class),
                $app->make(CartMetricsManager::class),
                $app->make(Dispatcher::class)
            );
        });

        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

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
