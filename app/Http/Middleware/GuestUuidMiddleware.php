<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class GuestUuidMiddleware
{
    private const COOKIE_NAME = 'guest_uuid';

    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('GuestUuidMiddleware: --- START ---');
        Log::debug('GuestUuidMiddleware: Request URL: ' . $request->fullUrl());
        Log::debug('GuestUuidMiddleware: Is user authenticated? ' . (auth()->check() ? 'Yes' : 'No'));

        // این منطق فقط برای کاربران مهمان (لاگین نشده) اجرا می‌شود.
        if (!auth()->check()) {
            $guestUuidFromCookie = $request->cookie(self::COOKIE_NAME);
            Log::debug('GuestUuidMiddleware: Raw guest_uuid from $request->cookie(): ' . ($guestUuidFromCookie ?? 'NULL'));

            // بررسی می‌کنیم که آیا کوکی 'guest_uuid' وجود دارد و آیا مقدار آن یک UUID معتبر است.
            if (!$guestUuidFromCookie || !Str::isUuid($guestUuidFromCookie)) {
                $newGuestUuid = (string) Str::uuid();
                Log::info('GuestUuidMiddleware: Guest UUID is missing or invalid. Generating new UUID: ' . $newGuestUuid);

                // تعیین اینکه کوکی باید 'secure' باشد یا خیر، بر اساس محیط برنامه.
                $secure = app()->environment('production');

                // کوکی را در صف قرار می‌دهیم تا به پاسخ HTTP اضافه شود.
                cookie()->queue(
                    cookie(
                        self::COOKIE_NAME,
                        $newGuestUuid,
                        60 * 24 * 30, // 30 روز
                        '/',
                        null,
                        $secure,
                        false, // httpOnly: false به جاوااسکریپت اجازه دسترسی می‌دهد
                        false,
                        'Lax'
                    )
                );
                Log::debug('GuestUuidMiddleware: Queued new guest_uuid cookie: ' . $newGuestUuid);
                $finalGuestUuid = $newGuestUuid; // از UUID جدید استفاده می‌کنیم
            } else {
                Log::debug('GuestUuidMiddleware: Valid guest_uuid found from cookie: ' . $guestUuidFromCookie);
                $finalGuestUuid = $guestUuidFromCookie; // از UUID موجود استفاده می‌کنیم
            }

            // ذخیره guest_uuid در Request attributes برای دسترسی آسان‌تر در کنترلرها
            $request->attributes->set('guest_uuid', $finalGuestUuid);
            Log::debug('GuestUuidMiddleware: Guest UUID set in request attributes: ' . $request->attributes->get('guest_uuid'));

        } else {
            Log::debug('GuestUuidMiddleware: User is authenticated. Skipping guest_uuid generation/check.');
            // اگر کاربر لاگین کرده است، guest_uuid را از کوکی می‌خوانیم و در attributes قرار می‌دهیم
            // این برای مواقعی است که کاربر لاگین می‌کند اما هنوز guest_uuid در کوکی دارد
            $guestUuidFromCookie = $request->cookie(self::COOKIE_NAME);
            if ($guestUuidFromCookie && Str::isUuid($guestUuidFromCookie)) {
                $request->attributes->set('guest_uuid', $guestUuidFromCookie);
                Log::debug('GuestUuidMiddleware: Authenticated user has guest_uuid cookie: ' . $guestUuidFromCookie . '. Set in attributes.');
            } else {
                Log::debug('GuestUuidMiddleware: Authenticated user has no valid guest_uuid cookie.');
            }
        }

        $response = $next($request);

        Log::debug('GuestUuidMiddleware: --- END ---');
        return $response;
    }
}
