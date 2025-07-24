<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // اضافه کردن 'guest_uuid' به لیست کوکی‌هایی که نباید رمزگذاری شوند
        'guest_uuid',
    ];
}
