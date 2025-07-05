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
use App\Repositories\Eloquent\CartRepository; // Corrected namespace
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository; // Corrected namespace

class CartServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind the interface to the concrete implementation
        $this->app->bind(CartServiceInterface::class, ImprovedCartService::class);

        // Bind repository interfaces to their concrete implementations
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);

        // Register managers as singletons to ensure only one instance exists
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

        $this->app->singleton(CartMetricsManager::class); // Metrics manager might not need specific constructor args
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../../config/cart.php' => config_path('cart.php'),
        ], 'cart-config');

        // Register the command
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\CleanupExpiredCartsCommand::class,
            ]);
        }
    }
}
