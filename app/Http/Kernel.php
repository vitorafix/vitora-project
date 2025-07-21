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
            \App\Http\Middleware\GuestUuidMiddleware::class, // اضافه شدن: برای مدیریت guest_uuid در کوکی
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // اگر از Sanctum استفاده می‌کنید (برای session-based SPA)
        ],

        'api' => [
            // 'throttle:api', // این را می‌توان به صورت جداگانه در مسیرها اعمال کرد
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // Sanctum و Session-based auth را برای APIهای JWT غیرفعال می‌کنیم
            // اگر API شما کاملاً Stateless است و فقط از JWT استفاده می‌کنید، این خطوط باید کامنت شده باشند.
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            // \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            // \Illuminate\Auth\Middleware\AuthenticateSession::class,

            // افزودن میدل‌ویر JWTAuth برای احراز هویت API
            \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
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
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class, // اطمینان از وجود این خط
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
    ];
}
