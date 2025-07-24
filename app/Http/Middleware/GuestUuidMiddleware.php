<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use App\Services\GuestService; // اضافه کردن GuestService

class GuestUuidMiddleware
{
    private const COOKIE_NAME = GuestService::GUEST_UUID_COOKIE;

    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('GuestUuidMiddleware: --- START ---');
        Log::debug('GuestUuidMiddleware: Request URL: ' . $request->fullUrl());
        Log::debug('GuestUuidMiddleware: Is user authenticated? ' . (auth()->check() ? 'Yes' : 'No'));

        // اگر کاربر لاگین نکرده باشد، از GuestService برای مدیریت UUID استفاده می‌کنیم.
        if (!auth()->check()) {
            // ابتدا درخواست را به کنترلر بعدی ارسال می‌کنیم تا پاسخ اولیه تولید شود.
            // این تضمین می‌کند که سشن (در صورت وجود) و سایر میدل‌ورها اجرا شده‌اند.
            $response = $next($request);

            // حالا GuestService را فراخوانی می‌کنیم و شیء Response را به آن پاس می‌دهیم.
            // این به GuestService اجازه می‌دهد کوکی guest_uuid را مستقیماً روی پاسخ تنظیم کند.
            $finalGuestUuid = GuestService::getOrCreateGuestUuid($request, $response);
            Log::debug('GuestUuidMiddleware: Guest UUID managed by GuestService (with response): ' . $finalGuestUuid);

            // ذخیره guest_uuid در Request attributes برای دسترسی آسان‌تر در کنترلرها
            $request->attributes->set('guest_uuid', $finalGuestUuid);
            Log::debug('GuestUuidMiddleware: Guest UUID set in request attributes: ' . $request->attributes->get('guest_uuid'));

            return $response;

        } else {
            // اگر کاربر لاگین کرده است، guest_uuid را از کوکی یا هدر می‌خوانیم و در attributes قرار می‌دهیم
            // این بخش از منطق شما حفظ می‌شود تا guest_uuid قدیمی کاربر لاگین شده همچنان در دسترس باشد
            $initialGuestUuidFromCookie = $request->cookie(self::COOKIE_NAME);
            $initialGuestUuidFromHeader = $request->header(GuestService::GUEST_UUID_HEADER);

            $guestUuidForAuthenticated = null;
            if ($initialGuestUuidFromHeader && GuestService::isValidUuid($initialGuestUuidFromHeader)) {
                $guestUuidForAuthenticated = $initialGuestUuidFromHeader;
                Log::debug('GuestUuidMiddleware: Authenticated user has guest_uuid in header: ' . $guestUuidForAuthenticated . '. Set in attributes.');
            } else if ($initialGuestUuidFromCookie && GuestService::isValidUuid($initialGuestUuidFromCookie)) {
                $guestUuidForAuthenticated = $initialGuestUuidFromCookie;
                Log::debug('GuestUuidMiddleware: Authenticated user has guest_uuid cookie: ' . $guestUuidForAuthenticated . '. Set in attributes.');
            } else {
                Log::debug('GuestUuidMiddleware: Authenticated user has no valid guest_uuid cookie or header.');
            }

            if ($guestUuidForAuthenticated) {
                 $request->attributes->set('guest_uuid', $guestUuidForAuthenticated);
            }

            $response = $next($request);
            return $response;
        }
    }
}
