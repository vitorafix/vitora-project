<?php

namespace App\Events;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;     // For user transfer
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use InvalidArgumentException; // For validation
use App\Models\Product; // Added for return type hint

class CartItemUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Cart $cart;
    public CartItem $cartItem;
    public int $oldQuantity;
    public int $newQuantity;
    public ?User $user;

    public function __construct(Cart $cart, CartItem $cartItem, int $oldQuantity, int $newQuantity, ?User $user = null)
    {
        // Basic validation to prevent invalid quantities
        if ($oldQuantity < 0 || $newQuantity < 0) {
            // In a real system, this error might be handled before the event is dispatched.
            // But for robustness, it can be added here too.
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        // Optional: Consider if newQuantity === 0 should trigger a CartItemRemoved event instead
        // if ($newQuantity === 0) {
        //     // You might want to dispatch CartItemRemoved event here or handle this case upstream
        //     // throw new InvalidArgumentException('New quantity cannot be zero. Use removeCartItem for removal.');
        // }

        $this->cart = $cart;
        $this->cartItem = $cartItem;
        $this->oldQuantity = $oldQuantity;
        $this->newQuantity = $newQuantity;
        $this->user = $user;

        // Check product match (optional: although service logic should ensure this)
        // if ($this->cartItem->product_id !== $product->id) {
        //     throw new InvalidArgumentException('Product ID does not match cart item product ID.');
        // }
    }

    /**
     * Returns the product associated with the cart item.
     *
     * @return \App\Models\Product
     */
    public function getProduct(): Product // Added return type hint
    {
        return $this->cartItem->product;
    }

    /**
     * Returns the difference between the new and old quantity.
     *
     * @return int
     */
    public function getQuantityDifference(): int
    {
        return $this->newQuantity - $this->oldQuantity;
    }

    /**
     * Checks if the product quantity has increased.
     *
     * @return bool
     */
    public function isQuantityIncreased(): bool
    {
        return $this->newQuantity > $this->oldQuantity;
    }

    /**
     * Checks if the product quantity has decreased.
     *
     * @return bool
     */
    public function isQuantityDecreased(): bool
    {
        return $this->newQuantity < $this->oldQuantity;
    }

    /**
     * Checks if the product quantity has changed.
     *
     * @return bool
     */
    public function hasQuantityChanged(): bool // Added helper method
    {
        return $this->oldQuantity !== $this->newQuantity;
    }
}
