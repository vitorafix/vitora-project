<?php

namespace App\Services\Contracts;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Services\Responses\CartOperationResponse;

/**
 * Interface for Cart Item Management Service.
 * رابط برای سرویس مدیریت آیتم‌های سبد خرید.
 */
interface CartItemManagementServiceInterface
{
    /**
     * Adds a new product to the cart or updates an existing item's quantity.
     * یک محصول جدید را به سبد خرید اضافه می‌کند یا تعداد یک آیتم موجود را به‌روزرسانی می‌کند.
     *
     * @param \App\Models\Cart $cart The cart to operate on.
     * سبد خریدی که عملیات روی آن انجام می‌شود.
     * @param int $productId The ID of the product.
     * شناسه محصول.
     * @param int $quantity The quantity to add or set.
     * تعداد برای اضافه کردن یا تنظیم.
     * @param int|null $productVariantId The ID of the product variant, if applicable.
     * شناسه واریانت محصول، در صورت وجود.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity, ?int $productVariantId = null): \App\Services\Responses\CartOperationResponse;

    /**
     * Updates the quantity of an existing cart item.
     * تعداد یک آیتم موجود در سبد خرید را به‌روزرسانی می‌کند.
     *
     * @param \App\Models\CartItem $cartItem The cart item to update.
     * آیتم سبد خریدی که باید به‌روزرسانی شود.
     * @param int $newQuantity The new quantity for the item.
     * تعداد جدید برای آیتم.
     * @param \App\Models\User|null $user The authenticated user, if any.
     * کاربر احراز هویت شده، در صورت وجود.
     * @param string|null $sessionId The session ID for guest carts.
     * شناسه جلسه برای سبدهای خرید مهمان.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function updateItemQuantity(CartItem $cartItem, int $newQuantity, ?User $user = null, ?string $sessionId = null): \App\Services\Responses\CartOperationResponse;

    /**
     * Removes a cart item from the cart.
     * یک آیتم سبد خرید را از سبد خرید حذف می‌کند.
     *
     * @param \App\Models\CartItem $cartItem The cart item to remove.
     * آیتم سبد خریدی که باید حذف شود.
     * @param \App\Models\User|null $user The authenticated user, if any.
     * کاربر احراز هویت شده، در صورت وجود.
     * @param string|null $sessionId The session ID for guest carts.
     * شناسه جلسه برای سبدهای خرید مهمان.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function removeItem(CartItem $cartItem, ?User $user = null, ?string $sessionId = null): \App\Services\Responses\CartOperationResponse;
}

