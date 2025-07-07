<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Import interfaces and implementations
use App\Contracts\ProductServiceInterface;
use App\Services\ProductService;

use App\Contracts\Repositories\ProductVariantRepositoryInterface;
use App\Repositories\ProductVariantRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind ProductServiceInterface to ProductService
        $this->app->bind(ProductServiceInterface::class, ProductService::class);

        // Bind ProductVariantRepositoryInterface to ProductVariantRepository
        $this->app->bind(ProductVariantRepositoryInterface::class, ProductVariantRepository::class);

        // سایر Binding ها را اینجا اضافه کنید
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
