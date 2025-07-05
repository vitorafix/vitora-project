<?php

namespace App\Services\Contracts;

use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Responses\CartContentsResponse;
use App\Services\Responses\CartOperationResponse; // added
use Carbon\Carbon; // added for Type Hint

interface CartServiceInterface
{
    /**
     * Get existing cart or create new one based on user or session.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @return Cart
     */
    public function getOrCreateCart(?User $user = null, ?string $sessionId = null): Cart;

    /**
     * Merge guest cart with user cart when user logs in.
     *
     * @param User $user
     * @param string $guestSessionId
     * @return void
     */
    public function mergeGuestCart(User $user, string $guestSessionId): void;

    /**
     * Assign guest cart to newly registered user.
     *
     * @param string $guestSessionId
     * @param User $newUser
     * @return void
     */
    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void;

    /**
     * Add new item or update existing item quantity in the cart.
     *
     * @param Cart $cart
     * @param int $productId
     * @param int $quantity
     * @return CartOperationResponse
     */
    public function addOrUpdateCartItem(Cart $cart, int $productId, int $quantity): CartOperationResponse;

    /**
     * Update specific cart item quantity.
     *
     * @param CartItem $cartItem
     * @param int $newQuantity
     * @param User|null $user
     * @param string|null $sessionId
     * @return CartOperationResponse
     */
    public function updateCartItemQuantity(
        CartItem $cartItem,
        int $newQuantity,
        ?User $user = null,
        ?string $sessionId = null
    ): CartOperationResponse;

    /**
     * Remove specific item from cart.
     *
     * @param CartItem $cartItem
     * @param User|null $user
     * @param string|null $sessionId
     * @return CartOperationResponse
     */
    public function removeCartItem(
        CartItem $cartItem,
        ?User $user = null,
        ?string $sessionId = null
    ): CartOperationResponse;

    /**
     * Clear all items from cart and optionally delete the cart itself.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function clearCart(Cart $cart): CartOperationResponse;

    /**
     * Get complete cart contents with calculations for display.
     *
     * @param Cart $cart
     * @return CartContentsResponse
     */
    public function getCartContents(Cart $cart): CartContentsResponse;

    /**
     * Update multiple cart items in a single bulk operation.
     *
     * @param Cart $cart
     * @param array $updates An associative array where keys are product IDs and values are quantities.
     * @return CartOperationResponse
     */
    public function updateMultipleItems(Cart $cart, array $updates): CartOperationResponse;

    /**
     * Reserve product stock for cart items (typically in a temporary cache).
     *
     * @param Product $product
     * @param int $quantity
     * @param int|null $minutes The duration in minutes for which the stock should be reserved.
     * @return bool True if stock was successfully reserved, false otherwise.
     */
    public function reserveStock(Product $product, int $quantity, ?int $minutes = null): bool;

    /**
     * Release reserved product stock.
     *
     * @param Product $product
     * @param int $quantity
     * @return bool True if stock was successfully released, false otherwise.
     */
    public function releaseStock(Product $product, int $quantity): bool;

    /**
     * Clean up expired guest carts and release their stock.
     *
     * @param int|null $daysCutoff Number of days after which a cart is considered expired.
     * @return int The number of expired carts cleaned up.
     */
    public function cleanupExpiredCarts(?int $daysCutoff = null): int;

    /**
     * Check if the given user or session owns a specific cart item.
     *
     * @param CartItem $cartItem
     * @param User|null $user
     * @param string|null $sessionId
     * @return bool
     */
    public function userOwnsCartItem(CartItem $cartItem, ?User $user, ?string $sessionId): bool;

    /**
     * Get a cart by its ID with ownership validation.
     *
     * @param int $cartId
     * @param User|null $user
     * @param string|null $sessionId
     * @return Cart|null The cart object if found and owned, null otherwise.
     */
    public function getCartById(int $cartId, ?User $user = null, ?string $sessionId = null): ?Cart;

    /**
     * Calculate cart totals, including subtotal, shipping, taxes, and discounts.
     *
     * @param Cart $cart
     * @return array An associative array of calculated totals.
     */
    public function calculateCartTotals(Cart $cart): array;

    /**
     * Validate cart items for availability, stock, and current prices.
     *
     * @param Cart $cart
     * @return array An array of validation results (e.g., items with issues).
     */
    public function validateCartItems(Cart $cart): array;

    /**
     * Apply a coupon/discount code to the cart.
     *
     * @param Cart $cart
     * @param string $couponCode
     * @return CartOperationResponse
     */
    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse;

    /**
     * Remove an applied coupon from the cart.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function removeCoupon(Cart $cart): CartOperationResponse;

    /**
     * Get the total count of unique items in the cart.
     *
     * @param Cart $cart
     * @return int
     */
    public function getCartItemCount(Cart $cart): int;

    /**
     * Transfer cart ownership from one user/session to another user.
     *
     * @param Cart $cart The cart to transfer.
     * @param User $newOwner The new user to whom the cart will be assigned.
     * @return bool True on successful transfer, false otherwise.
     */
    public function transferCartOwnership(Cart $cart, User $newOwner): bool;

    /**
     * Check if the cart is empty (contains no items).
     *
     * @param Cart $cart
     * @return bool
     */
    public function isCartEmpty(Cart $cart): bool;

    /**
     * Get the estimated expiry date/time for a guest cart.
     *
     * @param Cart $cart
     * @return Carbon|null The expiry date/time, or null if not applicable (e.g., for user carts).
     */
    public function getCartExpiryDate(Cart $cart): ?Carbon;

    /**
     * Refresh cart item prices from current product prices in the database.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function refreshCartItemPrices(Cart $cart): CartOperationResponse;
}
