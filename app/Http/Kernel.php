<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     * این میدل‌ویرها در هر درخواست به برنامه شما اجرا می‌شوند.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class, // در صورت نیاز به مدیریت Trust Hosts فعال شود
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \Illuminate\Http\Middleware\HandleCors::class, // فعال‌سازی پشتیبانی داخلی CORS لاراول
    ];

    /**
     * The application's route middleware groups.
     * گروه‌های میدل‌ویر مسیرهای برنامه.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class, // این باید قبل از GuestUuidMiddleware باشد
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\GuestUuidMiddleware::class, // مدیریت UUID مهمان - جابجا شده به اینجا
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // در صورت استفاده از Sanctum فعال شود
            'throttle:api', // محدودیت نرخ برای APIها
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Session\Middleware\StartSession::class, // اضافه شده برای API
            \App\Http\Middleware\GuestUuidMiddleware::class, // در اینجا هم باید باشد
            // \App\Http\Middleware\AuthenticateOnceWithBasicAuth::class, // حذف شد: این میدل‌ویر با JWTAuth اصلی تداخل داشت.
            // توجه: میدل‌ویر 'jwt.auth' به صورت مستقیم در فایل routes/api.php برای مسیرهای محافظت شده اعمال می‌شود.
        ],
    ];

    /**
     * The application's route middleware.
     * میدل‌ویرهای مسیر برنامه.
     * این میدل‌ویرها می‌توانند به گروه‌ها یا مسیرهای جداگانه اختصاص داده شوند.
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
        'profile.completed' => \App\Http\Middleware\EnsureProfileIsCompleted::class, // میدل‌ویر برای اطمینان از تکمیل پروفایل

        // --- میدل‌ویرهای Spatie Laravel Permission ---
        'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
        // --- پایان میدل‌ویرهای Spatie ---

        // --- میدل‌ویرهای JWT (Tymon/JWT-Auth) ---
        'jwt.auth' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class, // میدل‌ویر اصلی احراز هویت JWT
        'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class, // میدل‌ویر برای تجدید توکن JWT
        // --- پایان میدل‌ویرهای JWT ---
    ];

    /**
     * The priority-sorted list of HTTP middleware.
     * لیست میدل‌ویرهای HTTP با اولویت مرتب شده.
     * این تضمین می‌کند که میدل‌ویرهای غیرگلوبال همیشه به ترتیب مشخص شده اجرا شوند.
     *
     * @var array<int, class-string|string>
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\GuestUuidMiddleware::class, // جابجا شده به اینجا
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
        \App\Http\Middleware\EnsureProfileIsCompleted::class,
        \App\Http\Middleware\OptimizeResourceForMobile::class, // در صورت وجود و نیاز به بهینه‌سازی منابع برای موبایل
    ];
}
