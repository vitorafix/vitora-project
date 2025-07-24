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
    // نام کوکی را از GuestService می‌خوانیم تا یکپارچگی حفظ شود
    private const COOKIE_NAME = GuestService::GUEST_UUID_COOKIE;

    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('GuestUuidMiddleware: --- START ---');
        Log::debug('GuestUuidMiddleware: Request URL: ' . $request->fullUrl());
        Log::debug('GuestUuidMiddleware: Is user authenticated? ' . (auth()->check() ? 'Yes' : 'No'));

        // اگر کاربر لاگین نکرده باشد، از GuestService برای مدیریت UUID استفاده می‌کنیم.
        if (!auth()->check()) {
            // GuestService::getOrCreateGuestUuid هم UUID را در سشن قرار می‌دهد و هم کوکی را تنظیم/تمدید می‌کند.
            $finalGuestUuid = GuestService::getOrCreateGuestUuid($request);
            Log::debug('GuestUuidMiddleware: Guest UUID managed by GuestService: ' . $finalGuestUuid);

            // ذخیره guest_uuid در Request attributes برای دسترسی آسان‌تر در کنترلرها و سایر میدل‌ویرها
            $request->attributes->set('guest_uuid', $finalGuestUuid);
            Log::debug('GuestUuidMiddleware: Guest UUID set in request attributes: ' . $request->attributes->get('guest_uuid'));

        } else {
            // اگر کاربر لاگین کرده است، guest_uuid را از کوکی یا هدر می‌خوانیم و در attributes قرار می‌دهیم
            // این بخش از منطق شما حفظ می‌شود تا guest_uuid قدیمی کاربر لاگین شده همچنان در دسترس باشد
            $initialGuestUuidFromCookie = $request->cookie(self::COOKIE_NAME);
            $initialGuestUuidFromHeader = $request->header('X-Guest-UUID');

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
        }

        $response = $next($request);

        Log::debug('GuestUuidMiddleware: --- END ---');
        return $response;
    }
}
