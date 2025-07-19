<?php
// File: app/Contracts/Repositories/CartRepositoryInterface.php
namespace App\Contracts\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Carbon\Carbon; // اضافه کردن ایمپورت Carbon

interface CartRepositoryInterface
{
    /**
     * Find a cart by user ID.
     * سبد خرید را بر اساس شناسه کاربر پیدا می‌کند.
     *
     * @param int $userId
     * @return Cart|null
     */
    public function findByUserId(int $userId): ?Cart;

    /**
     * Find a cart by session ID.
     * سبد خرید را بر اساس شناسه سشن پیدا می‌کند.
     *
     * @param string $sessionId
     * @return Cart|null
     */
    public function findBySessionId(string $sessionId): ?Cart;

    /**
     * Find a cart by guest UUID.
     * سبد خرید را بر اساس شناسه یکتای مهمان (guest UUID) پیدا می‌کند.
     *
     * @param string $guestUuid
     * @return Cart|null
     */
    public function findByGuestUuid(string $guestUuid): ?Cart;

    /**
     * Create a new cart.
     * یک سبد خرید جدید ایجاد می‌کند.
     *
     * @param array $data
     * @return Cart
     */
    public function create(array $data): Cart;

    /**
     * Save a cart (create or update).
     * یک سبد خرید را ذخیره می‌کند (ایجاد یا به‌روزرسانی).
     *
     * @param Cart $cart
     * @return bool
     */
    public function save(Cart $cart): bool;

    /**
     * Assigns a cart to a user.
     * یک سبد خرید را به یک کاربر اختصاص می‌دهد.
     *
     * @param Cart $cart
     * @param User $user
     * @return bool
     */
    public function assignCartToUser(Cart $cart, User $user): bool;

    /**
     * Delete a cart.
     * یک سبد خرید را حذف می‌کند.
     *
     * @param Cart $cart
     * @return bool
     */
    public function delete(Cart $cart): bool;

    /**
     * Find a cart item by cart ID and product ID.
     * یک آیتم سبد خرید را بر اساس شناسه سبد و شناسه محصول پیدا می‌کند.
     *
     * @param int $cartId
     * @param int $productId
     * @return CartItem|null
     */
    public function findCartItem(int $cartId, int $productId): ?CartItem;

    /**
     * Create a new cart item.
     * یک آیتم سبد خرید جدید ایجاد می‌کند.
     *
     * @param array $data
     * @return CartItem
     */
    public function createCartItem(array $data): CartItem;

    /**
     * Update a cart item.
     * یک آیتم سبد خرید را به‌روزرسانی می‌کند.
     *
     * @param CartItem $cartItem
     * @param array $data
     * @return bool
     */
    public function updateCartItem(CartItem $cartItem, array $data): bool;

    /**
     * Delete a cart item.
     * یک آیتم سبد خرید را حذف می‌کند.
     *
     * @param CartItem $cartItem
     * @return bool
     */
    public function deleteCartItem(CartItem $cartItem): bool;

    /**
     * Get all cart items for a given cart.
     * تمام آیتم‌های سبد خرید را برای یک سبد مشخص دریافت می‌کند.
     *
     * @param Cart $cart
     * @return \Illuminate\Database\Eloquent\Collection<CartItem>
     */
    public function getCartItems(Cart $cart): \Illuminate\Database\Eloquent\Collection;

    /**
     * Delete multiple cart items by product IDs for a given cart.
     * چندین آیتم سبد خرید را بر اساس شناسه‌های محصول برای یک سبد مشخص حذف می‌کند.
     *
     * @param Cart $cart
     * @param array $productIds
     * @return int The number of items deleted.
     */
    public function deleteCartItemsByProductIds(Cart $cart, array $productIds): int;

    /**
     * Upsert multiple cart items.
     * چندین آیتم سبد خرید را به صورت Upsert (به‌روزرسانی یا درج) می‌کند.
     *
     * @param array $itemsData
     * @param array $uniqueBy
     * @param array $update
     * @return int The number of rows affected.
     */
    public function upsertCartItems(array $itemsData, array $uniqueBy, array $update): int;

    /**
     * Get carts that are expired (guest carts without user_id and updated_at older than cutoff).
     * سبدهای خرید منقضی شده (سبدهای مهمان بدون user_id و با updated_at قدیمی‌تر از تاریخ مشخص) را دریافت می‌کند.
     *
     * @param Carbon $cutoffDate
     * @return \Illuminate\Database\Eloquent\Collection<Cart>
     */
    public function getExpiredGuestCarts(Carbon $cutoffDate): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get a cart with its items loaded.
     * سبد خرید را به همراه آیتم‌های آن دریافت می‌کند.
     *
     * @param int $cartId
     * @return Cart|null
     */
    public function findCartWithItems(int $cartId): ?Cart;

    /**
     * Clear all items from a cart.
     * تمام آیتم‌ها را از یک سبد خرید پاک می‌کند.
     *
     * @param Cart $cart
     * @return bool
     */
    public function clearCart(Cart $cart): bool;
}
