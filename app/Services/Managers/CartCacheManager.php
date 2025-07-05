    <?php
    // File: app/Services/Managers/CartCacheManager.php
    namespace App\Services\Managers;

    use App\Models\User;
    use App\Models\Cart;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Log;

    class CartCacheManager
    {
        private int $cacheTtl; // Time to live for cache in seconds

        public function __construct()
        {
            $this->cacheTtl = config('cart.cache_ttl', 3600); // Read from config/cart.php
        }

        /**
         * Generates a unique cache key for a cart based on user or session ID.
         * یک کلید کش منحصر به فرد برای سبد خرید بر اساس شناسه کاربر یا سشن تولید می‌کند.
         *
         * @param User|null $user
         * @param string|null $sessionId
         * @return string
         */
        public function getCacheKey(?User $user = null, ?string $sessionId = null): string
        {
            if ($user) {
                return 'cart_user_' . $user->id;
            }
            return 'cart_session_' . ($sessionId ?? session()->getId());
        }

        /**
         * Stores cart data in cache.
         * داده‌های سبد خرید را در کش ذخیره می‌کند.
         *
         * @param string $key The cache key.
         * @param Cart $cart The cart object to cache.
         * @return bool
         */
        public function put(string $key, Cart $cart): bool
        {
            try {
                return Cache::put($key, $cart, $this->cacheTtl);
            } catch (\Exception $e) {
                Log::error('Failed to put cart in cache: ' . $e->getMessage(), ['key' => $key]);
                return false;
            }
        }

        /**
         * Retrieves cart data from cache.
         * داده‌های سبد خرید را از کش بازیابی می‌کند.
         *
         * @param string $key The cache key.
         * @return Cart|null
         */
        public function get(string $key): ?Cart
        {
            try {
                return Cache::get($key);
            } catch (\Exception $e) {
                Log::error('Failed to get cart from cache: ' . $e->getMessage(), ['key' => $key]);
                return null;
            }
        }

        /**
         * Removes cart data from cache.
         * داده‌های سبد خرید را از کش حذف می‌کند.
         *
         * @param User|null $user
         * @param string|null $sessionId
         * @return bool
         */
        public function clearCache(?User $user = null, ?string $sessionId = null): bool
        {
            try {
                $key = $this->getCacheKey($user, $sessionId);
                return Cache::forget($key);
            } catch (\Exception $e) {
                Log::error('Failed to clear cart cache: ' . $e->getMessage(), ['user_id' => $user?->id, 'session_id' => $sessionId]);
                return false;
            }
        }

        /**
         * Retrieves data from cache or stores it if not found.
         * داده‌ها را از کش بازیابی می‌کند یا در صورت عدم وجود، آن را ذخیره می‌کند.
         *
         * @param string $key The cache key.
         * @param \Closure $callback The callback to execute if data is not in cache.
         * @return mixed
         */
        public function remember(string $key, \Closure $callback): mixed
        {
            try {
                return Cache::remember($key, $this->cacheTtl, $callback);
            } catch (\Exception $e) {
                Log::error('Failed to remember cart in cache: ' . $e->getMessage(), ['key' => $key]);
                // Fallback to direct execution if caching fails
                return $callback();
            }
        }

        /**
         * Performs a health check for the cache manager.
         * یک بررسی سلامت برای مدیر کش انجام می‌دهد.
         *
         * @return array
         */
        public function healthCheck(): array
        {
            // In a real application, you might try to put/get a dummy value to check cache connectivity.
            // در یک برنامه واقعی، ممکن است سعی کنید یک مقدار ساختگی را برای بررسی اتصال کش قرار دهید/دریافت کنید.
            try {
                Cache::put('health_check_test', 'ok', 10);
                $status = Cache::get('health_check_test') === 'ok' ? 'ok' : 'failed';
                return ['status' => $status, 'message' => 'Cache manager is operational.'];
            } catch (\Exception $e) {
                return ['status' => 'failed', 'message' => 'Cache manager error: ' . $e->getMessage()];
            }
        }
    }
    