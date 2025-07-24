<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Config; // اضافه کردن Facade Config
use Illuminate\Support\Facades\Log; // اضافه کردن Facade Log برای ثبت خطاها

class GuestService
{
    // کلید مورد استفاده برای ذخیره UUID مهمان در سشن (از فایل کانفیگ خوانده می‌شود)
    const GUEST_UUID_KEY = 'guest_uuid';
    // نام کوکی مورد استفاده برای ذخیره UUID مهمان (از فایل کانفیگ خوانده می‌شود)
    const GUEST_UUID_COOKIE = 'guest_uuid';

    /**
     * UUID مهمان را دریافت می‌کند یا در صورت عدم وجود، آن را ایجاد می‌کند.
     *
     * @param Request $request شیء درخواست HTTP
     * @return string UUID مهمان
     */
    public static function getOrCreateGuestUuid(Request $request): string
    {
        try {
            // 1. ابتدا سعی می‌کنیم UUID مهمان را از سشن بخوانیم.
            $guestUuid = $request->session()->get(Config::get('guest.uuid_session_key', self::GUEST_UUID_KEY));

            // 2. اگر UUID از سشن معتبر نبود یا وجود نداشت، سعی می‌کنیم از کوکی بخوانیم.
            if (!$guestUuid || !self::isValidUuid($guestUuid)) {
                $guestUuid = $request->cookie(Config::get('guest.uuid_cookie_name', self::GUEST_UUID_COOKIE));

                // 3. اگر UUID از کوکی معتبر بود، آن را در سشن هم ذخیره می‌کنیم.
                if ($guestUuid && self::isValidUuid($guestUuid)) {
                    $request->session()->put(Config::get('guest.uuid_session_key', self::GUEST_UUID_KEY), $guestUuid);
                } else {
                    // 4. اگر نه در سشن و نه در کوکی یک UUID معتبر پیدا نشد، یک UUID جدید ایجاد می‌کنیم.
                    $guestUuid = self::createNewGuestUuid($request);
                }
            }

            return $guestUuid;
        } catch (\Exception $e) {
            // ثبت خطا در لاگ‌ها
            Log::error('Error managing guest UUID: ' . $e->getMessage());
            // در صورت بروز خطا، یک UUID جدید به عنوان fallback ایجاد می‌کنیم.
            return self::createNewGuestUuid($request);
        }
    }

    /**
     * یک UUID جدید برای مهمان ایجاد کرده و آن را در سشن و کوکی ذخیره می‌کند.
     *
     * @param Request $request شیء درخواست HTTP
     * @return string UUID جدید مهمان
     */
    private static function createNewGuestUuid(Request $request): string
    {
        $guestUuid = (string) Str::uuid();

        // ذخیره UUID جدید در سشن
        $request->session()->put(Config::get('guest.uuid_session_key', self::GUEST_UUID_KEY), $guestUuid);

        // ذخیره UUID جدید در کوکی
        self::setGuestUuidCookie($guestUuid);

        return $guestUuid;
    }

    /**
     * UUID مهمان را در کوکی تنظیم می‌کند.
     *
     * @param string $uuid UUID مهمان
     * @return void
     */
    private static function setGuestUuidCookie(string $uuid): void
    {
        // عمر کوکی را از فایل کانفیگ می‌خوانیم، پیش‌فرض 30 روز
        $cookieLifetimeDays = Config::get('guest.cookie_lifetime_days', 30);
        $cookieSecure = Config::get('guest.cookie_secure', request()->secure()); // تشخیص خودکار HTTPS
        $cookieHttpOnly = Config::get('guest.cookie_http_only', true); // httpOnly = true برای امنیت بیشتر

        // کوکی با عمر مشخص ایجاد و به صف می‌اندازیم.
        Cookie::queue(
            Config::get('guest.uuid_cookie_name', self::GUEST_UUID_COOKIE),
            $uuid,
            60 * 24 * $cookieLifetimeDays, // تبدیل روز به دقیقه
            '/',
            null,
            $cookieSecure,
            $cookieHttpOnly
        );
    }

    /**
     * بررسی می‌کند که آیا رشته داده شده یک UUID معتبر است یا خیر.
     *
     * @param string $uuid رشته UUID برای اعتبارسنجی
     * @return bool اگر UUID معتبر باشد true، در غیر این صورت false
     */
    private static function isValidUuid(string $uuid): bool
    {
        return Str::isUuid($uuid);
    }

    /**
     * بررسی می‌کند که آیا یک UUID مهمان معتبر در سشن یا کوکی وجود دارد یا خیر.
     *
     * @param Request $request شیء درخواست HTTP
     * @return bool اگر UUID مهمان معتبر وجود داشته باشد true، در غیر این صورت false
     */
    public static function hasGuestUuid(Request $request): bool
    {
        $uuid = $request->session()->get(Config::get('guest.uuid_session_key', self::GUEST_UUID_KEY));

        if (!$uuid || !self::isValidUuid($uuid)) {
            $uuid = $request->cookie(Config::get('guest.uuid_cookie_name', self::GUEST_UUID_COOKIE));
        }

        return $uuid && self::isValidUuid($uuid);
    }

    /**
     * UUID مهمان را از سشن و کوکی پاک کرده و یک UUID جدید ایجاد می‌کند.
     *
     * @param Request $request شیء درخواست HTTP
     * @return string UUID جدید مهمان
     */
    public static function refreshGuestUuid(Request $request): string
    {
        self::clearGuestUuid($request);
        return self::createNewGuestUuid($request);
    }

    /**
     * UUID مهمان را از سشن و کوکی پاک می‌کند.
     *
     * @param Request $request شیء درخواست HTTP
     * @return void
     */
    public static function clearGuestUuid(Request $request): void
    {
        $request->session()->forget(Config::get('guest.uuid_session_key', self::GUEST_UUID_KEY));
        Cookie::queue(Cookie::forget(Config::get('guest.uuid_cookie_name', self::GUEST_UUID_COOKIE)));
    }
}
