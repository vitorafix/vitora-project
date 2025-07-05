<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ProductService; // Import the ProductService
use App\Contracts\ProductServiceInterface; // Import the ProductServiceInterface

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind the ProductServiceInterface to the ProductService implementation
        $this->app->bind(ProductServiceInterface::class, ProductService::class);

        // Removed the temporary test binding for stdClass
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

