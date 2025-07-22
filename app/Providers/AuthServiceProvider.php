<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
// use Illuminate\Auth\AuthManager; // این خط دیگر نیازی نیست
// use Tymon\JWTAuth\Facades\JWTAuth; // این خط دیگر نیازی نیست اگر فقط برای extend استفاده می شد

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        // Example: Product::class => ProductPolicy::class, // If you decide to use Policies
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define a Gate for managing products.
        // This Gate checks if the authenticated user has the 'is_admin' attribute set to true.
        // You can customize this logic based on your application's role/permission system.
        Gate::define('manage-products', function (User $user) {
            return $user->is_admin ?? false; // Assuming 'is_admin' is a boolean column on your User model
        });

        // حذف بلوک extend('jwt', ...)
        // پکیج tymon/jwt-auth به صورت خودکار گارد 'jwt' را ثبت می کند.
        // تلاش برای extend کردن دستی آن باعث خطای "Class not found" می شود.

        // You can define other Gates here as needed.
    }
}
