<?php

namespace App\Services\Managers;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CartOperationException; // Custom exception

class CartRateLimiter
{
    private int $cooldownSeconds;

    public function __construct()
    {
        // مقدار cooldownSeconds را به 0.5 ثانیه تغییر دهید
        // Change the cooldownSeconds value to 0.5 seconds
        $this->cooldownSeconds = config('cart.rate_limit_cooldown', 0.5); // مقدار پیش‌فرض را به 0.5 تغییر دادیم
    }

    /**
     * Checks and applies rate limiting for cart operations.
     * بررسی و اعمال محدودیت نرخ برای عملیات سبد خرید.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @throws CartOperationException If rate limit is exceeded.
     */
    public function checkRateLimit(?User $user = null, ?string $sessionId = null): void
    {
        // Determine the unique key for rate limiting based on user or session
        $key = $user ? 'add_to_cart_user_' . $user->id : 'add_to_cart_session_' . ($sessionId ?? session()->getId());

        // Attempt to pass the rate limit. The second argument (1) means 1 attempt per cooldown period.
        if (!RateLimiter::attempt($key, 1, function() {
            // This callback is executed only if the attempt passes
        }, $this->cooldownSeconds)) {
            $availableIn = RateLimiter::availableIn($key);
            Log::warning('Rate limit exceeded for cart operation', ['key' => $key, 'available_in' => $availableIn]);
            throw new CartOperationException('لطفاً کمی صبر کنید و دوباره تلاش کنید. درخواست‌های شما بیش از حد مجاز است. (' . $availableIn . ' ثانیه)', 429); // Too Many Requests
        }
        // نکته: برای بهبود تجربه کاربری و جلوگیری از رسیدن به محدودیت نرخ در سمت سرور،
        // توصیه می‌شود در سمت فرانت‌اند (جاوا اسکریپت) از مکانیزم‌های debounce یا throttle
        // برای فراخوانی‌های API مربوط به سبد خرید استفاده کنید.
        // Note: To improve user experience and prevent hitting server-side rate limits,
        // it is recommended to use debounce or throttle mechanisms in the frontend (JavaScript)
        // for cart-related API calls.
    }

    /**
     * Performs a health check for the rate limiter.
     * یک بررسی سلامت برای محدودکننده نرخ انجام می‌دهد.
     *
     * @return array
     */
    public function healthCheck(): array
    {
        // Simple check if RateLimiter facade is available
        // یک بررسی ساده برای اطمینان از در دسترس بودن RateLimiter facade
        if (class_exists(\Illuminate\Support\Facades\RateLimiter::class)) {
            return ['status' => 'ok', 'message' => 'Rate limiter is available.'];
        }
        return ['status' => 'failed', 'message' => 'Rate limiter facade is not available.'];
    }
}
