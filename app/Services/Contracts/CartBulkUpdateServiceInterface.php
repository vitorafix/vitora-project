<?php

namespace App\Services\Contracts;

use App\Models\Cart;
use App\Services\Responses\CartOperationResponse;

/**
 * Interface for managing bulk updates of cart items.
 * رابط برای مدیریت به‌روزرسانی‌های گروهی آیتم‌های سبد خرید.
 */
interface CartBulkUpdateServiceInterface
{
    /**
     * Updates multiple items in the cart simultaneously.
     * آیتم‌های متعدد را به صورت همزمان در سبد خرید به‌روزرسانی می‌کند.
     *
     * @param Cart $cart The cart to update.
     * @param array $updates An associative array where keys are product IDs and values are new quantities.
     * @return CartOperationResponse Response indicating the success or failure of the operation.
     * @throws \App\Exceptions\CartInvalidArgumentException If the number of bulk operations exceeds the allowed limit.
     * @throws \App\Exceptions\InsufficientStockException If there's insufficient stock for an item.
     * @throws \App\Exceptions\CartOperationException For other unexpected errors during the operation.
     */
    public function updateMultipleItems(Cart $cart, array $updates): CartOperationResponse;
}

