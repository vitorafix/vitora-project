<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CartRepositoryInterface;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // New import for DB facade

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
     * Find a cart by guest UUID.
     * سبد خرید را بر اساس شناسه یکتای مهمان (guest UUID) پیدا می‌کند.
     *
     * @param string $guestUuid
     * @return Cart|null
     */
    public function findByGuestUuid(string $guestUuid): ?Cart
    {
        return Cart::where('guest_uuid', $guestUuid)->first();
    }

    /**
     * Find a cart by user or session ID.
     * سبد خرید را بر اساس شناسه کاربر یا شناسه سشن پیدا می‌کند.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @return Cart|null
     */
    public function findByUserOrSession(?User $user = null, ?string $sessionId = null): ?Cart
    {
        if ($user) {
            return $this->findByUserId($user->id);
        }

        if ($sessionId) {
            return $this->findBySessionId($sessionId);
        }

        return null;
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
     * Assigns a cart to a user.
     * یک سبد خرید را به یک کاربر اختصاص می‌دهد.
     *
     * @param Cart $cart
     * @param User $user
     * @return bool
     */
    public function assignCartToUser(Cart $cart, User $user): bool
    {
        $cart->user_id = $user->id;
        $cart->session_id = null; // Clear session ID as it's now owned by a user
        // اگر guest_uuid وجود دارد، آن را حفظ می‌کنیم. در غیر این صورت، آن را null می‌کنیم.
        $cart->guest_uuid = $cart->guest_uuid ?? null; 
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
        // Eager load 'items' and their 'product' and 'productVariant' relations
        // بارگذاری eagerly 'items' و روابط 'product' و 'productVariant' آنها
        return Cart::with(['items.product', 'items.productVariant'])->find($cartId);
    }

    /**
     * Clear all items from a cart.
     * تمام آیتم‌ها را از یک سبد خرید پاک می‌کند.
     *
     * @param Cart $cart
     * @return bool
     */
    public function clearCart(Cart $cart): bool
    {
        return $cart->items()->delete();
    }

    /**
     * Find a cart by ID.
     * سبد خرید را بر اساس شناسه آن پیدا می‌کند.
     *
     * @param int $cartId
     * @return Cart|null
     */
    public function findById(int $cartId): ?Cart
    {
        return Cart::find($cartId);
    }

    /**
     * Bulk update multiple cart items by their IDs.
     * چندین آیتم سبد خرید را به صورت انبوه بر اساس شناسه‌های آنها به‌روزرسانی می‌کند.
     *
     * @param array $updates An array of arrays, where each inner array contains 'id' and the fields to update (e.g., 'price').
     * @return int The number of affected rows.
     */
    public function bulkUpdateCartItems(array $updates): int
    {
        $affectedRows = 0;
        foreach ($updates as $update) {
            if (isset($update['id']) && is_array($update)) {
                $id = $update['id'];
                unset($update['id']); // Remove ID from update data
                $affectedRows += CartItem::where('id', $id)->update($update);
            }
        }
        return $affectedRows;
        // A more optimized bulk update might use a single query if all updates are for the same column,
        // but for varying columns or complex conditions, iterating is simpler.
        // برای به‌روزرسانی‌های پیچیده‌تر، ممکن است به یک کوئری واحد نیاز باشد، اما برای سادگی، از حلقه استفاده می‌شود.
    }
}
