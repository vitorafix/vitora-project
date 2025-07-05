<?php
// File: app/Services/Contracts/CartServiceInterface.php
namespace App\Services\Contracts;

use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\Responses\CartContentsResponse;
use App\Services\Responses\CartOperationResponse; // اضافه شد
use Carbon\Carbon; // اضافه شد برای Type Hint

interface CartServiceInterface
{
    /**
     * Get existing cart or create new one based on user or session.
     * سبد خرید موجود را دریافت می‌کند یا در صورت عدم وجود، یک سبد جدید ایجاد می‌کند.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @return Cart
     */
    public function getOrCreateCart(?User $user = null, ?string $sessionId = null): Cart;

    /**
     * Merge guest cart with user cart when user logs in.
     * سبد خرید مهمان را با سبد خرید کاربر پس از ورود به سیستم ادغام می‌کند.
     *
     * @param User $user
     * @param string $guestSessionId
     * @return void
     */
    public function mergeGuestCart(User $user, string $guestSessionId): void;

    /**
     * Assign guest cart to newly registered user.
     * سبد خرید مهمان را به کاربر تازه ثبت‌نام شده اختصاص می‌دهد.
     *
     * @param string $guestSessionId
     * @param User $newUser
     * @return void
     */
    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void;

    /**
     * Add new item or update existing item quantity in the cart.
     * یک آیتم جدید را به سبد خرید اضافه می‌کند یا تعداد یک آیتم موجود را به‌روزرسانی می‌کند.
     *
     * @param Cart $cart
     * @param int $productId
     * @param int $quantity
     * @return CartOperationResponse
     */
    public function addOrUpdateCartItem(Cart $cart, int $productId, int $quantity): CartOperationResponse;

    /**
     * Update specific cart item quantity.
     * تعداد یک آیتم خاص در سبد خرید را به‌روزرسانی می‌کند.
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
     * یک آیتم خاص را از سبد خرید حذف می‌کند.
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
     * تمام آیتم‌ها را از سبد خرید پاک می‌کند و به صورت اختیاری خود سبد را حذف می‌کند.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function clearCart(Cart $cart): CartOperationResponse;

    /**
     * Get complete cart contents with calculations for display.
     * محتویات کامل سبد خرید را به همراه محاسبات برای نمایش دریافت می‌کند.
     *
     * @param Cart $cart
     * @return CartContentsResponse
     */
    public function getCartContents(Cart $cart): CartContentsResponse;

    /**
     * Update multiple cart items in a single bulk operation.
     * چندین آیتم سبد خرید را در یک عملیات گروهی به‌روزرسانی می‌کند.
     *
     * @param Cart $cart
     * @param array $updates An associative array where keys are product IDs and values are quantities.
     * @return CartOperationResponse
     */
    public function updateMultipleItems(Cart $cart, array $updates): CartOperationResponse;

    /**
     * Reserve product stock for cart items (typically in a temporary cache).
     * موجودی محصول را برای آیتم‌های سبد خرید (معمولاً در یک کش موقت) رزرو می‌کند.
     *
     * @param Product $product
     * @param int $quantity
     * @param int|null $minutes The duration in minutes for which the stock should be reserved.
     * @return bool True if stock was successfully reserved, false otherwise.
     */
    public function reserveStock(Product $product, int $quantity, ?int $minutes = null): bool;

    /**
     * Release reserved product stock.
     * موجودی رزرو شده محصول را آزاد می‌کند.
     *
     * @param Product $product
     * @param int $quantity
     * @return bool True if stock was successfully released, false otherwise.
     */
    public function releaseStock(Product $product, int $quantity): bool;

    /**
     * Clean up expired guest carts and release their stock.
     * سبدهای خرید مهمان منقضی شده را پاکسازی و موجودی آن‌ها را آزاد می‌کند.
     *
     * @param int|null $daysCutoff Number of days after which a cart is considered expired.
     * @return int The number of expired carts cleaned up.
     */
    public function cleanupExpiredCarts(?int $daysCutoff = null): int;

    /**
     * Check if the given user or session owns a specific cart item.
     * بررسی می‌کند که آیا کاربر یا سشن مشخص، مالک یک آیتم سبد خرید خاص است یا خیر.
     *
     * @param CartItem $cartItem
     * @param User|null $user
     * @param string|null $sessionId
     * @return bool
     */
    public function userOwnsCartItem(CartItem $cartItem, ?User $user, ?string $sessionId): bool;

    /**
     * Get a cart by its ID with ownership validation.
     * سبد خرید را بر اساس شناسه آن و با اعتبارسنجی مالکیت دریافت می‌کند.
     *
     * @param int $cartId
     * @param User|null $user
     * @param string|null $sessionId
     * @return Cart|null The cart object if found and owned, null otherwise.
     */
    public function getCartById(int $cartId, ?User $user = null, ?string $sessionId = null): ?Cart;

    /**
     * Calculate cart totals, including subtotal, shipping, taxes, and discounts.
     * مجموع سبد خرید، شامل زیرمجموعه، هزینه ارسال، مالیات و تخفیفات را محاسبه می‌کند.
     *
     * @param Cart $cart
     * @return array An associative array of calculated totals.
     */
    public function calculateCartTotals(Cart $cart): array;

    /**
     * Validate cart items for availability, stock, and current prices.
     * آیتم‌های سبد خرید را از نظر در دسترس بودن، موجودی و قیمت‌های فعلی اعتبارسنجی می‌کند.
     *
     * @param Cart $cart
     * @return array An array of validation results (e.g., items with issues).
     */
    public function validateCartItems(Cart $cart): array;

    /**
     * Apply a coupon/discount code to the cart.
     * یک کد تخفیف را به سبد خرید اعمال می‌کند.
     *
     * @param Cart $cart
     * @param string $couponCode
     * @return CartOperationResponse
     */
    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse;

    /**
     * Remove an applied coupon from the cart.
     * کد تخفیف اعمال شده را از سبد خرید حذف می‌کند.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function removeCoupon(Cart $cart): CartOperationResponse;

    /**
     * Get the total count of unique items in the cart.
     * تعداد کل آیتم‌های منحصر به فرد در سبد خرید را دریافت می‌کند.
     *
     * @param Cart $cart
     * @return int
     */
    public function getCartItemCount(Cart $cart): int;

    /**
     * Transfer cart ownership from one user/session to another user.
     * مالکیت سبد خرید را از یک کاربر/سشن به کاربر دیگری منتقل می‌کند.
     *
     * @param Cart $cart The cart to transfer.
     * @param User $newOwner The new user to whom the cart will be assigned.
     * @return bool True on successful transfer, false otherwise.
     */
    public function transferCartOwnership(Cart $cart, User $newOwner): bool;

    /**
     * Check if the cart is empty (contains no items).
     * بررسی می‌کند که آیا سبد خرید خالی است (هیچ آیتمی ندارد).
     *
     * @param Cart $cart
     * @return bool
     */
    public function isCartEmpty(Cart $cart): bool;

    /**
     * Get the estimated expiry date/time for a guest cart.
     * تاریخ/زمان انقضای تخمینی برای یک سبد خرید مهمان را دریافت می‌کند.
     *
     * @param Cart $cart
     * @return Carbon|null The expiry date/time, or null if not applicable (e.g., for user carts).
     */
    public function getCartExpiryDate(Cart $cart): ?Carbon;

    /**
     * Refresh cart item prices from current product prices in the database.
     * قیمت آیتم‌های سبد خرید را بر اساس قیمت‌های فعلی محصولات در دیتابیس به‌روزرسانی می‌کند.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function refreshCartItemPrices(Cart $cart): CartOperationResponse;
}
