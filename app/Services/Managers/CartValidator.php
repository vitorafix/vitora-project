    <?php
    // File: app/Services/Managers/CartValidator.php
    namespace App\Services\Managers;

    use App\Models\Cart;
    use App\Models\User;
    use Illuminate\Support\Facades\Log;
    use App\Exceptions\CartInvalidArgumentException; // Custom exception
    use App\Exceptions\CartLimitExceededException; // Custom exception

    class CartValidator
    {
        private int $maxItemsPerCart;
        private int $maxQuantityPerItem;

        public function __construct()
        {
            $this->maxItemsPerCart = config('cart.max_items_per_cart', 100);
            $this->maxQuantityPerItem = config('cart.max_quantity_per_item', 999);
        }

        /**
         * Validates the quantity of a product.
         * تعداد یک محصول را اعتبارسنجی می‌کند.
         *
         * @param int $quantity
         * @return int The sanitized quantity.
         * @throws CartInvalidArgumentException
         */
        public function validateQuantity(int $quantity): int
        {
            if ($quantity < 0) {
                throw new CartInvalidArgumentException('تعداد محصول نمی‌تواند منفی باشد.'); // Quantity cannot be negative.
            }
            if ($quantity > $this->maxQuantityPerItem) {
                Log::warning('Requested quantity exceeds max quantity per item', ['quantity' => $quantity, 'max_quantity' => $this->maxQuantityPerItem]);
                throw new CartInvalidArgumentException('حداکثر تعداد مجاز برای یک محصول ' . $this->maxQuantityPerItem . ' عدد است.'); // Max quantity per item is X.
            }
            return $quantity;
        }

        /**
         * Validates if adding/updating items would exceed cart limits.
         * اعتبارسنجی می‌کند که آیا افزودن/به‌روزرسانی آیتم‌ها از محدودیت‌های سبد خرید فراتر می‌رود یا خیر.
         *
         * @param Cart $cart The current cart.
         * @param int $quantityChange The net change in total quantity (can be negative for removal).
         * @throws CartLimitExceededException
         */
        public function validateCartLimits(Cart $cart, int $quantityChange): void
        {
            $currentUniqueItems = $cart->items->count();
            $currentTotalQuantity = $cart->items->sum('quantity');

            // Check max unique items (if adding a new item)
            if ($quantityChange > 0 && $currentUniqueItems >= $this->maxItemsPerCart && !$cart->items->where('product_id', $cart->product_id ?? null)->first()) {
                // This check is more complex as it needs to know if product_id is new or existing.
                // For simplicity, we assume this is called before adding a *new* unique item.
                // A more robust check would involve passing the specific product_id to this method.
                Log::warning('Cart unique item limit exceeded', ['cart_id' => $cart->id, 'current_unique_items' => $currentUniqueItems, 'max_items' => $this->maxItemsPerCart]);
                throw new CartLimitExceededException('تعداد آیتم‌های منحصر به فرد در سبد خرید نمی‌تواند بیشتر از ' . $this->maxItemsPerCart . ' باشد.'); // Max unique items in cart is X.
            }

            // Check max total quantity (sum of all quantities)
            if (($currentTotalQuantity + $quantityChange) > $this->maxQuantityPerItem * $this->maxItemsPerCart) {
                // This is a very high limit, usually maxQuantityPerItem is for a single item.
                // Re-evaluating this limit based on common use cases.
                // For now, using it as a general safeguard against excessively large carts.
                Log::warning('Cart total quantity limit exceeded', ['cart_id' => $cart->id, 'current_total_quantity' => $currentTotalQuantity, 'quantity_change' => $quantityChange]);
                throw new CartLimitExceededException('تعداد کل محصولات در سبد خرید بیش از حد مجاز است.'); // Total products in cart exceed limit.
            }
        }


        /**
         * Validates that either a user or a session ID is provided.
         * اعتبارسنجی می‌کند که یا شناسه کاربر یا شناسه سشن ارائه شده باشد.
         *
         * @param User|null $user
         * @param string|null $sessionId
         * @throws CartInvalidArgumentException
         */
        public function validateUserOrSession(?User $user = null, ?string $sessionId = null): void
        {
            if (is_null($user) && is_null($sessionId)) {
                Log::error('Attempted to get or create cart without user or session ID');
                throw new CartInvalidArgumentException('برای دریافت یا ایجاد سبد خرید، شناسه کاربر یا شناسه سشن الزامی است.'); // User or session ID is required to get or create a cart.
            }
        }

        /**
         * Performs a health check for the cart validator.
         * یک بررسی سلامت برای اعتبارسنج سبد خرید انجام می‌دهد.
         *
         * @return array
         */
        public function healthCheck(): array
        {
            // Simple check to ensure config values are loaded correctly
            // یک بررسی ساده برای اطمینان از بارگذاری صحیح مقادیر پیکربندی
            if ($this->maxItemsPerCart > 0 && $this->maxQuantityPerItem > 0) {
                return ['status' => 'ok', 'message' => 'Cart validator is configured correctly.'];
            }
            return ['status' => 'failed', 'message' => 'Cart validator configuration error.'];
        }
    }
    