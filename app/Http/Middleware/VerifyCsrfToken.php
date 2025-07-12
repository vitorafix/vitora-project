<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'auth/send-otp',
        'auth/verify-otp',
        'auth/change-mobile-number',
        'api/auth/send-otp', // این خط اضافه شد تا مسیر API نیز مستثنی شود.
    ];
}

