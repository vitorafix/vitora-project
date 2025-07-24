<?php

namespace App\Services\Contracts;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Http\Request; // اضافه شده برای استفاده از Request در اینترفیس
use App\Services\Responses\CartContentsResponse;
use App\Services\Responses\CartOperationResponse;
use Carbon\Carbon;

interface CartServiceInterface
{
    /**
     * Get or create a cart for a given user, session, or guest UUID.
     * دریافت یا ایجاد یک سبد خرید برای کاربر، جلسه یا UUID مهمان مشخص.
     *
     * @param Request $request شیء درخواست HTTP برای دسترسی به سشن و کوکی
     * @param User|null $user
     * @return Cart
     */
    public function getOrCreateCart(Request $request, ?User $user = null): Cart;

    /**
     * Merge a guest cart into a user's cart upon login.
     * ادغام سبد خرید مهمان با سبد خرید کاربر پس از ورود.
     *
     * @param \App\Models\User $user
     * @param string $guestSessionId
     * @return void
     */
    public function mergeGuestCart(User $user, string $guestSessionId): void;

    /**
     * Assigns a guest cart to a newly registered user.
     * اختصاص سبد خرید مهمان به کاربر تازه ثبت نام شده.
     *
     * @param string $guestSessionId
     * @param \App\Models\User $newUser
     * @return void
     */
    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void;

    /**
     * Add product to cart or update quantity.
     * محصول را به سبد خرید اضافه یا تعداد آن را به‌روزرسانی می‌کند.
     *
     * @param \App\Models\Cart $cart
     * @param int $productId
     * @param int $quantity
     * @param int|null $productVariantId
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function addOrUpdateCartItem(Cart $cart, int $productId, int $quantity, ?int $productVariantId = null): CartOperationResponse;

    /**
     * Update quantity of a specific cart item.
     * تعداد یک آیتم خاص در سبد خرید را به‌روزرسانی می‌کند.
     *
     * @param \App\Models\CartItem $cartItem
     * @param int $newQuantity
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @param string|null $guestUuid
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function updateCartItemQuantity(CartItem $cartItem, int $newQuantity, ?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): CartOperationResponse;

    /**
     * Remove a specific cart item.
     * یک آیتم خاص را از سبد خرید حذف می‌کند.
     *
     * @param \App\Models\CartItem $cartItem
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @param string|null $guestUuid
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function removeCartItem(CartItem $cartItem, ?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): CartOperationResponse;

    /**
     * Clear all items from the cart.
     * همه آیتم‌ها را از سبد خرید پاک می‌کند.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function clearCart(Cart $cart): CartOperationResponse;

    /**
     * Get cart contents for display.
     * محتویات سبد خرید را برای نمایش دریافت می‌کند.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartContentsResponse
     */
    public function getCartContents(Cart $cart): CartContentsResponse;

    /**
     * Update multiple items in the cart (e.g., from a form submission).
     * به‌روزرسانی چندین آیتم در سبد خرید (مثلاً از طریق ارسال فرم).
     *
     * @param \App\Models\Cart $cart
     * @param array $updates An array of updates, each containing 'cart_item_id' and 'quantity'.
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function updateMultipleItems(Cart $cart, array $updates): CartOperationResponse;

    /**
     * Cleanup expired guest carts.
     * پاکسازی سبدهای خرید مهمان منقضی شده.
     *
     * @param int|null $daysCutoff
     * @return int The number of carts cleaned up.
     */
    public function cleanupExpiredCarts(?int $daysCutoff = null): int;

    /**
     * Check if a user (or session) owns a specific cart item.
     * بررسی مالکیت یک آیتم سبد خرید توسط کاربر (یا جلسه).
     *
     * @param \App\Models\CartItem $cartItem
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @param string|null $guestUuid
     * @return bool
     */
    public function userOwnsCartItem(CartItem $cartItem, ?User $user, ?string $sessionId, ?string $guestUuid = null): bool;

    /**
     * Get a cart by its ID, ensuring ownership.
     * دریافت یک سبد خرید بر اساس شناسه آن، با اطمینان از مالکیت.
     *
     * @param int $cartId
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @param string|null $guestUuid
     * @return \App\Models\Cart|null
     */
    public function getCartById(int $cartId, ?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): ?Cart;

    /**
     * Calculate the subtotal, total, shipping, tax, and discount for a cart.
     * محاسبه مجموع فرعی، مجموع کل، هزینه حمل و نقل، مالیات و تخفیف برای یک سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @return array
     */
    public function calculateCartTotals(Cart $cart): array;

    /**
     * Validate cart items for issues like insufficient stock or missing products.
     * اعتبارسنجی آیتم‌های سبد خرید برای مشکلاتی مانند کمبود موجودی یا محصولات گم شده.
     *
     * @param \App\Models\Cart $cart
     * @return array An array of validation issues.
     */
    public function validateCartItems(Cart $cart): array;

    /**
     * Apply a coupon to the cart.
     * اعمال یک کد تخفیف به سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @param string $couponCode
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse;

    /**
     * Remove a coupon from the cart.
     * حذف یک کد تخفیف از سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function removeCoupon(Cart $cart): CartOperationResponse;

    /**
     * Get the total count of items (sum of quantities) in the cart.
     * دریافت تعداد کل آیتم‌ها (مجموع تعداد) در سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @return int
     */
    public function getCartItemCount(Cart $cart): int;

    /**
     * Transfer cart ownership from a guest session to a new user.
     * انتقال مالکیت سبد خرید از جلسه مهمان به کاربر جدید.
     *
     * @param \App\Models\Cart $cart
     * @param \App\Models\User $newOwner
     * @return bool
     */
    public function transferCartOwnership(Cart $cart, User $newOwner): bool;

    /**
     * Check if the cart is empty.
     * بررسی خالی بودن سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @return bool
     */
    public function isCartEmpty(Cart $cart): bool;

    /**
     * Get the expiry date for a guest cart.
     * دریافت تاریخ انقضای سبد خرید مهمان.
     *
     * @param \App\Models\Cart $cart
     * @return \Carbon\Carbon|null
     */
    public function getCartExpiryDate(Cart $cart): ?Carbon;

    /**
     * Refresh the prices of items in the cart based on current product prices.
     * به‌روزرسانی قیمت آیتم‌های سبد خرید بر اساس قیمت‌های فعلی محصول.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function refreshCartItemPrices(Cart $cart): CartOperationResponse;
}
