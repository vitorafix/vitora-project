<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Import interfaces and implementations
use App\Contracts\ProductServiceInterface;
use App\Services\ProductService;

use App\Contracts\Repositories\ProductVariantRepositoryInterface;
use App\Repositories\ProductVariantRepository;

// اضافه کردن اینترفیس و پیاده‌سازی CartService
use App\Services\Contracts\CartServiceInterface;
use App\Services\CartService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * این متد برای ثبت سرویس‌ها و اتصال آن‌ها به کانتینر سرویس لاراول استفاده می‌شود.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind ProductServiceInterface to ProductService
        $this->app->bind(ProductServiceInterface::class, ProductService::class);

        // Bind ProductVariantRepositoryInterface to ProductVariantRepository
        $this->app->bind(ProductVariantRepositoryInterface::class, ProductVariantRepository::class);

        // Bind CartServiceInterface to CartService
        // این اتصال تضمین می‌کند که هر زمان CartServiceInterface درخواست شود،
        // یک نمونه از CartService ارائه خواهد شد. این برای عملکرد صحیح Listener و کنترلرها حیاتی است.
        $this->app->bind(CartServiceInterface::class, CartService::class);

        // سایر Binding ها را اینجا اضافه کنید
    }

    /**
     * Bootstrap any application services.
     *
     * این متد پس از ثبت تمام سرویس‌ها فراخوانی می‌شود و برای بوت‌استرپ کردن سرویس‌ها استفاده می‌شود.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}

