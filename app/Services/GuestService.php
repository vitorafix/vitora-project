<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GuestService
{
    public const GUEST_UUID_COOKIE = 'guest_uuid';
    public const GUEST_UUID_HEADER = 'X-Guest-UUID';
    public const GUEST_UUID_SESSION_KEY = 'guest_uuid';

    /**
     * Get or create a guest UUID.
     * For web requests, it uses session and cookie.
     * For API requests, it primarily uses cookie/header and sets cookie in response.
     *
     * @param Request $request
     * @param Response|null $response Optional: Pass a Response object to set the cookie on it.
     * @return string The guest UUID.
     */
    public static function getOrCreateGuestUuid(Request $request, ?Response $response = null): string
    {
        $currentGuestUuid = null;

        // 1. Check from request attributes (set by middleware)
        if ($request->attributes->has(self::GUEST_UUID_SESSION_KEY)) {
            $currentGuestUuid = $request->attributes->get(self::GUEST_UUID_SESSION_KEY);
            Log::debug('GuestService: UUID from attributes: ' . $currentGuestUuid);
        }

        // 2. Check from header (common for API)
        if (!$currentGuestUuid && $request->hasHeader(self::GUEST_UUID_HEADER)) {
            $headerUuid = $request->header(self::GUEST_UUID_HEADER);
            if (self::isValidUuid($headerUuid)) {
                $currentGuestUuid = $headerUuid;
                Log::debug('GuestService: UUID from header: ' . $currentGuestUuid);
            }
        }

        // 3. Check from cookie
        if (!$currentGuestUuid && $request->hasCookie(self::GUEST_UUID_COOKIE)) {
            $cookieUuid = $request->cookie(self::GUEST_UUID_COOKIE);
            if (self::isValidUuid($cookieUuid)) {
                $currentGuestUuid = $cookieUuid;
                Log::debug('GuestService: UUID from cookie: ' . $currentGuestUuid);
            }
        }

        // 4. Check from session (primarily for web, if middleware hasn't set it yet)
        // This part will only be attempted if session is available.
        if (!$currentGuestUuid && $request->hasSession() && $request->session()->has(self::GUEST_UUID_SESSION_KEY)) {
            $sessionUuid = $request->session()->get(self::GUEST_UUID_SESSION_KEY);
            if (self::isValidUuid($sessionUuid)) {
                $currentGuestUuid = $sessionUuid;
                Log::debug('GuestService: UUID from session: ' . $currentGuestUuid);
            }
        }

        // If no valid UUID found, generate a new one
        if (!$currentGuestUuid) {
            $currentGuestUuid = self::createNewGuestUuid($request);
            Log::info('GuestService: Generated new Guest UUID: ' . $currentGuestUuid);
        } else {
            Log::info('GuestService: Existing Guest UUID retrieved: ' . $currentGuestUuid);
        }

        // Set UUID in session (if session is available)
        if ($request->hasSession()) {
            $request->session()->put(self::GUEST_UUID_SESSION_KEY, $currentGuestUuid);
            Log::debug('GuestService: UUID set in session: ' . $currentGuestUuid);
        }

        // Set/update cookie (for both web and API persistence)
        // Ensure the cookie is set on the response for API calls if a response object is provided.
        $cookieExpiration = 60 * 24 * 30; // 30 days
        $cookie = Cookie::make(
            self::GUEST_UUID_COOKIE,
            $currentGuestUuid,
            $cookieExpiration,
            '/', // path
            env('SESSION_DOMAIN'), // domain from .env
            $request->secure(), // secure only if HTTPS
            true, // httpOnly
            false, // raw
            $request->secure() ? 'None' : 'Lax' // SameSite: 'None' for secure, 'Lax' for HTTP
        );

        if ($response) {
            $response->headers->setCookie($cookie);
            Log::debug('GuestService: Guest UUID cookie set on response object.');
        } else {
            // For web requests where middleware handles response, or if no response object provided
            Cookie::queue($cookie);
            Log::debug('GuestService: Guest UUID cookie queued.');
        }

        // Ensure it's in request attributes for immediate use in current request cycle
        $request->attributes->set(self::GUEST_UUID_SESSION_KEY, $currentGuestUuid);

        return $currentGuestUuid;
    }

    /**
     * Generates a new UUID.
     *
     * @param Request $request
     * @return string
     */
    private static function createNewGuestUuid(Request $request): string
    {
        $newUuid = (string) Str::uuid();
        // No need to set it in session here, as getOrCreateGuestUuid handles it.
        // No need to set cookie here, as getOrCreateGuestUuid handles it.
        return $newUuid;
    }

    /**
     * Validates if a string is a valid UUID.
     *
     * @param string|null $uuid
     * @return bool
     */
    public static function isValidUuid(?string $uuid): bool
    {
        return !empty($uuid) && Str::isUuid($uuid);
    }
}
