<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit; // اضافه شده
use Illuminate\Support\Facades\RateLimiter; // اضافه شده
use Illuminate\Http\Request; // اضافه شده

class RouteServiceProvider extends ServiceProvider
{
    /**
     * این مسیر Home URL برای redirect های auth است.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * ثبت همه‌ی روت‌های برنامه.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configureRateLimiting(); // اضافه شده: فراخوانی متد تعریف Rate Limiting

        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        });
    }

    /**
     * Rate Limiting های برنامه را پیکربندی می‌کند.
     *
     * @return void
     */
    protected function configureRateLimiting(): void
    {
        // تعریف Rate Limiter برای عملیات سبد خرید (افزودن)
        // 10 درخواست در هر دقیقه به ازای هر کاربر یا IP
        RateLimiter::for('cart_add', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // می‌توانید Rate Limiterهای دیگری را در اینجا تعریف کنید
    }
}
