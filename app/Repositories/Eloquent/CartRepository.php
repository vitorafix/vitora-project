    <?php
    // File: app/Repositories/Eloquent/CartRepository.php
    namespace App\Repositories\Eloquent;

    use App\Contracts\Repositories\CartRepositoryInterface;
    use App\Models\Cart;
    use App\Models\CartItem;
    use App\Models\User;
    use Illuminate\Database\Eloquent\Collection;
    use Carbon\Carbon;

    class CartRepository implements CartRepositoryInterface
    {
        /**
         * Find a cart by user ID.
         * سبد خرید را بر اساس شناسه کاربر پیدا می‌کند.
         *
         * @param int $userId
         * @return Cart|null
         */
        public function findByUserId(int $userId): ?Cart
        {
            return Cart::where('user_id', $userId)->first();
        }

        /**
         * Find a cart by session ID.
         * سبد خرید را بر اساس شناسه سشن پیدا می‌کند.
         *
         * @param string $sessionId
         * @return Cart|null
         */
        public function findBySessionId(string $sessionId): ?Cart
        {
            return Cart::where('session_id', $sessionId)->first();
        }

        /**
         * Create a new cart.
         * یک سبد خرید جدید ایجاد می‌کند.
         *
         * @param array $data
         * @return Cart
         */
        public function create(array $data): Cart
        {
            return Cart::create($data);
        }

        /**
         * Save a cart (create or update).
         * یک سبد خرید را ذخیره می‌کند (ایجاد یا به‌روزرسانی).
         *
         * @param Cart $cart
         * @return bool
         */
        public function save(Cart $cart): bool
        {
            return $cart->save();
        }

        /**
         * Delete a cart.
         * یک سبد خرید را حذف می‌کند.
         *
         * @param Cart $cart
         * @return bool
         */
        public function delete(Cart $cart): bool
        {
            return $cart->delete();
        }

        /**
         * Find a cart item by cart ID and product ID.
         * یک آیتم سبد خرید را بر اساس شناسه سبد و شناسه محصول پیدا می‌کند.
         *
         * @param int $cartId
         * @param int $productId
         * @return CartItem|null
         */
        public function findCartItem(int $cartId, int $productId): ?CartItem
        {
            return CartItem::where('cart_id', $cartId)
                           ->where('product_id', $productId)
                           ->first();
        }

        /**
         * Create a new cart item.
         * یک آیتم سبد خرید جدید ایجاد می‌کند.
         *
         * @param array $data
         * @return CartItem
         */
        public function createCartItem(array $data): CartItem
        {
            return CartItem::create($data);
        }

        /**
         * Update a cart item.
         * یک آیتم سبد خرید را به‌روزرسانی می‌کند.
         *
         * @param CartItem $cartItem
         * @param array $data
         * @return bool
         */
        public function updateCartItem(CartItem $cartItem, array $data): bool
        {
            return $cartItem->update($data);
        }

        /**
         * Delete a cart item.
         * یک آیتم سبد خرید را حذف می‌کند.
         *
         * @param CartItem $cartItem
         * @return bool
         */
        public function deleteCartItem(CartItem $cartItem): bool
        {
            return $cartItem->delete();
        }

        /**
         * Get all cart items for a given cart.
         * تمام آیتم‌های سبد خرید را برای یک سبد مشخص دریافت می‌کند.
         *
         * @param Cart $cart
         * @return \Illuminate\Database\Eloquent\Collection<CartItem>
         */
        public function getCartItems(Cart $cart): Collection
        {
            return $cart->items()->get();
        }

        /**
         * Delete multiple cart items by product IDs for a given cart.
         * چندین آیتم سبد خرید را بر اساس شناسه‌های محصول برای یک سبد مشخص حذف می‌کند.
         *
         * @param Cart $cart
         * @param array $productIds
         * @return int The number of items deleted.
         */
        public function deleteCartItemsByProductIds(Cart $cart, array $productIds): int
        {
            return $cart->items()->whereIn('product_id', $productIds)->delete();
        }

        /**
         * Upsert multiple cart items.
         * چندین آیتم سبد خرید را به صورت Upsert (به‌روزرسانی یا درج) می‌کند.
         *
         * @param array $itemsData
         * @param array $uniqueBy
         * @param array $update
         * @return int The number of rows affected.
         */
        public function upsertCartItems(array $itemsData, array $uniqueBy, array $update): int
        {
            return CartItem::upsert($itemsData, $uniqueBy, $update);
        }

        /**
         * Get carts that are expired (guest carts without user_id and updated_at older than cutoff).
         * سبدهای خرید منقضی شده (سبدهای مهمان بدون user_id و با updated_at قدیمی‌تر از تاریخ مشخص) را دریافت می‌کند.
         *
         * @param Carbon $cutoffDate
         * @return \Illuminate\Database\Eloquent\Collection<Cart>
         */
        public function getExpiredGuestCarts(Carbon $cutoffDate): Collection
        {
            return Cart::where('updated_at', '<', $cutoffDate)
                       ->whereNull('user_id')
                       ->get();
        }

        /**
         * Get a cart with its items loaded.
         * سبد خرید را به همراه آیتم‌های آن دریافت می‌کند.
         *
         * @param int $cartId
         * @return Cart|null
         */
        public function findCartWithItems(int $cartId): ?Cart
        {
            return Cart::with('items.product')->find($cartId);
        }
    }
    