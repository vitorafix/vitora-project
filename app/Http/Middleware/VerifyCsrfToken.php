<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'auth/send-otp',
        'auth/verify-otp',
        'auth/change-mobile-number',
    ];
}
