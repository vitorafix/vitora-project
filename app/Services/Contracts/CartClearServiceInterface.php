<?php

namespace App\Services\Contracts;

use App\Models\Cart;
use App\Services\Responses\CartOperationResponse;

/**
 * Interface for managing cart clearing operations.
 */
interface CartClearServiceInterface
{
    /**
     * Clears all items from a given cart and optionally deletes the cart itself.
     *
     * @param Cart $cart The cart to be cleared.
     * @return CartOperationResponse A response indicating the success or failure of the operation.
     */
    public function clearCart(Cart $cart): CartOperationResponse;
}

