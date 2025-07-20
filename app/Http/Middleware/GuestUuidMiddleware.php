<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GuestUuidMiddleware
{
    // نام کوکی به صورت ثابت برای جلوگیری از اشتباه و افزایش خوانایی.
    private const COOKIE_NAME = 'guest_uuid';

    /**
     * Handle an incoming request.
     *
     * این Middleware مسئول ایجاد و نگهداری یک UUID پایدار (guest_uuid) در کوکی کاربر مهمان است.
     * اگر کاربر لاگین نکرده باشد و کوکی guest_uuid وجود نداشته باشد یا نامعتبر باشد،
     * یک UUID جدید تولید کرده و آن را در کوکی ذخیره می‌کند.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // این منطق فقط برای کاربران مهمان (لاگین نشده) اجرا می‌شود.
        if (!auth()->check()) {
            $guestUuid = $request->cookie(self::COOKIE_NAME);

            // بررسی می‌کنیم که آیا کوکی 'guest_uuid' وجود دارد و آیا مقدار آن یک UUID معتبر است.
            // اگر وجود نداشت یا نامعتبر بود، یک UUID جدید ایجاد می‌کنیم.
            if (!$guestUuid || !Str::isUuid($guestUuid)) {
                $guestUuid = (string) Str::uuid();

                // تعیین اینکه کوکی باید 'secure' باشد یا خیر، بر اساس محیط برنامه.
                // در محیط 'production' (که انتظار می‌رود HTTPS باشد)، secure=true خواهد بود.
                $secure = app()->environment('production');

                // کوکی را در صف قرار می‌دهیم تا به پاسخ HTTP اضافه شود.
                cookie()->queue(
                    cookie(
                        self::COOKIE_NAME, // نام کوکی از ثابت استفاده می‌شود.
                        $guestUuid,        // مقدار UUID جدید.
                        60 * 24 * 30,      // مدت زمان انقضا: ۳۰ روز (بر حسب دقیقه).
                        '/',               // مسیر: کوکی برای تمام مسیرهای سایت قابل دسترسی است.
                        null,              // دامنه: null به معنای دامنه فعلی درخواست است.
                        $secure,           // secure: فقط روی HTTPS ارسال شود اگر true باشد.
                        false,             // httpOnly: false به جاوااسکریپت اجازه دسترسی به کوکی را می‌دهد.
                        false,             // raw: false به معنای URL-encode کردن مقدار کوکی است.
                        'Lax'              // SameSite: 'Lax' برای امنیت Cross-Site Request Forgery (CSRF).
                    )
                );
            }
        }

        return $next($request);
    }
}

