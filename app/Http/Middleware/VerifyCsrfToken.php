<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * مسیرهایی که باید از CSRF بررسی مستثنا شوند.
     *
     * @var array<int, string>
     */
    protected $except = [
        // اینجا مسیرهایی که نیاز به CSRF ندارند اضافه کن:
        '/auth/send-otp',
        '/auth/change-mobile-number',
    ];
}
