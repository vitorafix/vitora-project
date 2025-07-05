<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use stdClass; // برای تست یک کلاس ساده

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // هیچ binding مربوط به CartServiceInterface یا Managerهای Cart اینجا نباید باشد.
        // این بخش باید برای bindingهای عمومی برنامه شما استفاده شود.

        // --- شروع کد تست موقت ---
        $this->app->bind('test.service', function ($app) {
            return new stdClass();
        });
        // --- پایان کد تست موقت ---
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
