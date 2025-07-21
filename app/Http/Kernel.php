<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Http\Middleware\HandleCors::class, // اضافه شدن: برای فعال کردن پشتیبانی CORS داخلی لاراول
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\GuestUuidMiddleware::class, // اضافه شدن: برای مدیریت UUID مهمان
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // اگر از Sanctum استفاده نمی‌کنید، این را حذف کنید یا کامنت کنید
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // توجه: Middleware JWT به صورت جداگانه در routeMiddleware تعریف می‌شود
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or individual routes.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'profile.completed' => \App\Http\Middleware\EnsureProfileIsCompleted::class,

        // --- اضافه کردن میدل‌ورهای Spatie Laravel Permission ---
        'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
        // --- پایان اضافه کردن میدل‌ورهای Spatie ---

        // --- اضافه کردن میدل‌ورهای JWT (Tymon/JWT-Auth) ---
        'jwt.auth' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class, // این خط را بررسی کنید
        'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class, // این خط را بررسی کنید
        // --- پایان اضافه کردن میدل‌ورهای JWT ---
    ];

    /**
     * The priority-sorted list of HTTP middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array<int, class-string|string>
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class, // اضافه شدن به اولویت
        \App\Http\Middleware\EnsureProfileIsCompleted::class, // اضافه شدن به اولویت
        \App\Http\Middleware\OptimizeResourceForMobile::class, // اضافه شدن به اولویت
        \App\Http\Middleware\GuestUuidMiddleware::class, // اضافه شدن به اولویت
    ];
}
