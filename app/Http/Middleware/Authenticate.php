<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // اگر درخواست AJAX نیست و کاربر احراز هویت نشده است،
        // او را به مسیر ورود با موبایل هدایت می‌کنیم.
        // این جایگزین مسیر پیش‌فرض 'login' لاراول می‌شود.
        return $request->expectsJson() ? null : route('auth.mobile-login-formlogin');
    }
}
