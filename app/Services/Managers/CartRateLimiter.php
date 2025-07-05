    <?php
    // File: app/Services/Managers/CartRateLimiter.php
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
            $this->cooldownSeconds = config('cart.rate_limit_cooldown', 2);
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
            $key = $user ? 'add_to_cart_user_' . $user->id : 'add_to_cart_session_' . ($sessionId ?? session()->getId());

            if (!RateLimiter::attempt($key, 1, function() {
                // This callback is executed only if the attempt passes
            }, $this->cooldownSeconds)) {
                $availableIn = RateLimiter::availableIn($key);
                Log::warning('Rate limit exceeded for cart operation', ['key' => $key, 'available_in' => $availableIn]);
                throw new CartOperationException('لطفاً کمی صبر کنید و دوباره تلاش کنید. درخواست‌های شما بیش از حد مجاز است. (' . $availableIn . ' ثانیه)', 429); // Too Many Requests
            }
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
            return ['status' => 'failed', 'message' => 'Rate limiter facade not found.'];
        }
    }
    