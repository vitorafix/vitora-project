<?php
namespace App\Events;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Models\Product; // Added for return type hint in getProduct()
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use InvalidArgumentException;

class CartItemRemoved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Cart $cart;
    public CartItem $cartItem;
    public int $removedQuantity; // The quantity that was removed
    public ?User $user;

    public function __construct(Cart $cart, CartItem $cartItem, int $removedQuantity, ?User $user = null)
    {
        // Basic validation: Removed quantity must be positive
        if ($removedQuantity <= 0) {
            throw new InvalidArgumentException('Removed quantity must be greater than zero.');
        }

        // Additional validation: Cannot remove more than available in cart
        if ($removedQuantity > $cartItem->quantity) {
            throw new InvalidArgumentException('Cannot remove more items than available in cart.');
        }

        $this->cart = $cart;
        $this->cartItem = $cartItem;
        $this->removedQuantity = $removedQuantity;
        $this->user = $user;
    }

    /**
     * Returns the product associated with the removed item.
     *
     * @return \App\Models\Product
     */
    public function getProduct(): Product
    {
        return $this->cartItem->product;
    }

    /**
     * Returns the total value of the removed item quantity.
     *
     * @return float
     */
    public function getRemovedItemValue(): float
    {
        return $this->cartItem->price * $this->removedQuantity;
    }

    /**
     * Checks if the entire item was removed (quantity became zero).
     *
     * @return bool
     */
    public function isCompleteRemoval(): bool
    {
        // Check if the removed quantity is equal to the original quantity of the cart item
        // This implicitly means the item is completely removed from the cart.
        return $this->removedQuantity === $this->cartItem->quantity;
    }

    /**
     * Checks if a partial removal occurred (some quantity remains).
     *
     * @return bool
     */
    public function isPartialRemoval(): bool
    {
        // Check if the removed quantity is less than the original quantity of the cart item
        return $this->removedQuantity < $this->cartItem->quantity;
    }

    /**
     * Returns the remaining quantity after removal (0 if completely removed).
     *
     * @return int
     */
    public function getRemainingQuantity(): int
    {
        return max(0, $this->cartItem->quantity - $this->removedQuantity);
    }

    /**
     * Returns a summary of the removed item for logging and analytics.
     *
     * @return array
     */
    public function getRemovedItemSummary(): array
    {
        return [
            'product_id' => $this->getProduct()->id,
            'product_name' => $this->getProduct()->title, // Assuming 'title' is the product name
            'removed_quantity' => $this->removedQuantity,
            'unit_price' => $this->cartItem->price,
            'total_value' => $this->getRemovedItemValue(),
            'is_complete_removal' => $this->isCompleteRemoval(),
        ];
    }
}
