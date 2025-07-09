<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
        $this->configureRateLimiting();

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
        // تعریف Rate Limiter برای عملیات افزودن به سبد خرید
        // تعداد درخواست‌ها در هر دقیقه به 500 افزایش یافت تا کاربر بتواند تند تند کالا اضافه کند.
        RateLimiter::for('add_to_cart', function (Request $request) {
            return Limit::perMinute(500)->by($request->user()?->id ?: $request->ip());
        });

        // تعریف Rate Limiter برای عملیات به‌روزرسانی مقدار سبد خرید
        // تعداد درخواست‌ها در هر دقیقه به 600 افزایش یافت.
        RateLimiter::for('update_cart', function (Request $request) {
            return Limit::perMinute(600)->by($request->user()?->id ?: $request->ip());
        });
    }
}
