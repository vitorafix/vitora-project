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
     * @param string $userId
     * @return Cart|null
     */
    public function findByUserId(string $userId): ?Cart
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
            return $this->findByUserId((string) $user->id); // Cast to string
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
        // این متد فقط user_id را به user->id (عددی) تغییر می دهد.
        // تغییرات guest_uuid و session_id باید در CartService مدیریت شوند.
        $cart->user_id = (string) $user->id; // Cast to string for consistency with findByUserId
        $cart->session_id = null; // Clear session ID as it's now owned by a user
        $cart->guest_uuid = null; // Clear guest_uuid as it's now owned by a user
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
        // The parent cart will be touched automatically by the CartItem model's $touches property.
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
        // The parent cart will be touched automatically by the CartItem model's $touches property.
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
        // The parent cart will be touched automatically by the CartItem model's $touches property.
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
        $deletedCount = $cart->items()->whereIn('product_id', $productIds)->delete();
        // The parent cart will be touched automatically by the CartItem model's $touches property
        // for each deleted item.
        return $deletedCount;
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
        $affectedRows = CartItem::upsert($itemsData, $uniqueBy, $update);
        // If items were upserted, and assuming they belong to a single cart,
        // the parent cart's 'updated_at' will be touched by the CartItem model's $touches property.
        // However, for bulk upserts, explicit touching might be needed if $touches doesn't cover all scenarios
        // or if items belong to multiple carts. For simplicity, we rely on $touches here.
        // اگر آیتم‌ها upsert شده‌اند و فرض بر این است که به یک سبد خرید تعلق دارند،
        // updated_at سبد خرید والد به صورت خودکار توسط ویژگی $touches مدل CartItem به‌روز می‌شود.
        // با این حال، برای upsert های انبوه، اگر $touches همه سناریوها را پوشش ندهد یا آیتم‌ها به چندین سبد خرید تعلق داشته باشند،
        // ممکن است نیاز به touch صریح باشد. برای سادگی، در اینجا به $touches تکیه می‌کنیم.
        return $affectedRows;
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
        // اگر user_id در جدول carts برای مهمانان واقعاً NULL ذخیره می‌شود، این خط صحیح است.
        // اگر user_id در جدول carts برای مهمانان رشته 'guest' ذخیره می‌شود، باید از where('user_id', 'guest') استفاده کنید.
        return Cart::where('updated_at', '<', $cutoffDate)
                   ->whereNull('user_id') // فرض بر این است که user_id برای مهمانان NULL است.
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
        $deleted = $cart->items()->delete();
        // The parent cart will be touched automatically by the CartItem model's $touches property
        // for each deleted item.
        return $deleted;
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
        // Collect cart IDs that need to be touched.
        // This is still necessary for bulk operations as $touches might not cover all scenarios
        // when items are updated directly via query builder or in a loop like this.
        $cartIdsToTouch = []; 

        foreach ($updates as $update) {
            if (isset($update['id']) && is_array($update)) {
                $id = $update['id'];
                unset($update['id']); // Remove ID from update data
                
                // Get the cart item before updating to retrieve its cart_id
                $cartItem = CartItem::find($id);
                if ($cartItem) {
                    $affectedRows += CartItem::where('id', $id)->update($update);
                    // Add the cart_id to the list if it's not already there
                    if (!in_array($cartItem->cart_id, $cartIdsToTouch)) {
                        $cartIdsToTouch[] = $cartItem->cart_id;
                    }
                }
            }
        }

        // Explicitly touch all affected carts after the bulk operation.
        // This ensures the parent cart's 'updated_at' is updated,
        // as individual item updates in a loop might not trigger $touches reliably for the parent.
        foreach ($cartIdsToTouch as $cartId) {
            $cart = Cart::find($cartId);
            if ($cart) {
                $cart->touch();
            }
        }

        return $affectedRows;
    }
}
