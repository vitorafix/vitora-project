    <?php
    // File: app/Services/Managers/StockManager.php
    namespace App\Services\Managers;

    use App\Models\Product;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Cache; // For stock reservation
    use App\Exceptions\InsufficientStockException; // Custom exception

    class StockManager
    {
        private bool $stockCheckEnabled;
        private int $stockReservationMinutes;

        public function __construct()
        {
            $this->stockCheckEnabled = config('cart.stock_check_enabled', true);
            $this->stockReservationMinutes = config('cart.stock_reservation_minutes', 15);
        }

        /**
         * Validates if enough stock is available for a product.
         * اعتبارسنجی می‌کند که آیا موجودی کافی برای یک محصول موجود است یا خیر.
         *
         * @param Product $product
         * @param int $quantity
         * @throws InsufficientStockException
         */
        public function validateStock(Product $product, int $quantity): void
        {
            if (!$this->stockCheckEnabled) {
                return;
            }

            if ($quantity <= 0) {
                throw new InsufficientStockException('تعداد محصول باید مثبت باشد.'); // Quantity must be positive.
            }

            // Check actual stock
            if ($product->stock < $quantity) {
                Log::warning('Insufficient stock for product', ['product_id' => $product->id, 'requested_quantity' => $quantity, 'current_stock' => $product->stock]);
                throw new InsufficientStockException('موجودی محصول "' . $product->name . '" کافی نیست.'); // Insufficient stock for product.
            }

            // Check against reserved stock (if any)
            $reserved = $this->getReservedStock($product->id);
            if (($product->stock - $reserved) < $quantity) {
                Log::warning('Stock unavailable due to reservation', ['product_id' => $product->id, 'requested_quantity' => $quantity, 'current_stock' => $product->stock, 'reserved_stock' => $reserved]);
                throw new InsufficientStockException('این محصول در حال حاضر موجود نیست یا برای سفارشات دیگر رزرو شده است.'); // This product is currently out of stock or reserved for other orders.
            }
        }

        /**
         * Reserves a certain quantity of stock for a product for a limited time.
         * مقدار مشخصی از موجودی یک محصول را برای مدت زمان محدودی رزرو می‌کند.
         *
         * @param Product $product
         * @param int $quantity
         * @param int|null $minutes The duration in minutes for which the stock should be reserved.
         * @return bool
         */
        public function reserveStock(Product $product, int $quantity, ?int $minutes = null): bool
        {
            if (!$this->stockCheckEnabled) {
                return true;
            }

            $minutes = $minutes ?? $this->stockReservationMinutes;
            $cacheKey = 'reserved_stock_' . $product->id;

            // Use Cache::increment/decrement for atomic operations on reserved stock
            // This is a simple in-memory cache reservation. For distributed systems, use Redis or similar.
            try {
                $currentReserved = Cache::get($cacheKey, 0);
                if (($product->stock - $currentReserved) < $quantity) {
                    Log::warning('Attempt to reserve stock failed due to insufficient available stock after current reservations', ['product_id' => $product->id, 'requested_quantity' => $quantity, 'current_stock' => $product->stock, 'current_reserved' => $currentReserved]);
                    throw new InsufficientStockException('موجودی کافی برای رزرو محصول "' . $product->name . '" وجود ندارد.'); // Insufficient stock to reserve product.
                }

                Cache::increment($cacheKey, $quantity);
                Cache::put($cacheKey, Cache::get($cacheKey), Carbon::now()->addMinutes($minutes)); // Refresh TTL
                Log::info('Stock reserved', ['product_id' => $product->id, 'quantity' => $quantity, 'reserved_until' => Carbon::now()->addMinutes($minutes)->toDateTimeString()]);
                return true;
            } catch (\Exception $e) {
                Log::error('Error reserving stock: ' . $e->getMessage(), ['product_id' => $product->id, 'quantity' => $quantity]);
                return false;
            }
        }

        /**
         * Releases reserved stock for a product.
         * موجودی رزرو شده برای یک محصول را آزاد می‌کند.
         *
         * @param Product $product
         * @param int $quantity
         * @return bool
         */
        public function releaseStock(Product $product, int $quantity): bool
        {
            if (!$this->stockCheckEnabled) {
                return true;
            }

            $cacheKey = 'reserved_stock_' . $product->id;
            try {
                Cache::decrement($cacheKey, $quantity);
                // Ensure reserved stock doesn't go below zero
                if (Cache::get($cacheKey) < 0) {
                    Cache::put($cacheKey, 0);
                }
                Log::info('Stock released', ['product_id' => $product->id, 'quantity' => $quantity, 'new_reserved' => Cache::get($cacheKey)]);
                return true;
            } catch (\Exception $e) {
                Log::error('Error releasing stock: ' . $e->getMessage(), ['product_id' => $product->id, 'quantity' => $quantity]);
                return false;
            }
        }

        /**
         * Gets the currently reserved stock for a product.
         * موجودی فعلی رزرو شده برای یک محصول را دریافت می‌کند.
         *
         * @param int $productId
         * @return int
         */
        public function getReservedStock(int $productId): int
        {
            return Cache::get('reserved_stock_' . $productId, 0);
        }

        /**
         * Performs a health check for the stock manager.
         * یک بررسی سلامت برای مدیر موجودی انجام می‌دهد.
         *
         * @return array
         */
        public function healthCheck(): array
        {
            // In a real scenario, you might check database connectivity for product stock.
            // در یک سناریوی واقعی، ممکن است اتصال پایگاه داده را برای موجودی محصول بررسی کنید.
            try {
                Product::first(); // Simple check to see if Product model can access DB
                return ['status' => 'ok', 'message' => 'Stock manager is operational.'];
            } catch (\Exception $e) {
                return ['status' => 'failed', 'message' => 'Stock manager error: ' . $e->getMessage()];
            }
        }
    }
    