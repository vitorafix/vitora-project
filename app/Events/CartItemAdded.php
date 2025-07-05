<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\CartItem; // اضافه شده: برای انتقال آیتم سبد خرید
use App\Models\Cart;     // اضافه شده: برای انتقال سبد خرید
use App\Models\Product;  // اضافه شده: برای انتقال اطلاعات محصول
use App\Models\User;     // اضافه شده: برای انتقال کاربر

class CartItemAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The cart item instance.
     *
     * @var \App\Models\CartItem
     */
    public CartItem $cartItem;

    /**
     * The cart instance.
     *
     * @var \App\Models\Cart
     */
    public Cart $cart;

    /**
     * The product instance associated with the cart item.
     *
     * @var \App\Models\Product
     */
    public Product $product;

    /**
     * The user who performed the action (if authenticated).
     *
     * @var \App\Models\User|null
     */
    public ?User $user;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\CartItem $cartItem
     * @param \App\Models\Cart $cart
     * @param \App\Models\Product $product
     * @param \App\Models\User|null $user
     * @return void
     */
    public function __construct(CartItem $cartItem, Cart $cart, Product $product, ?User $user = null)
    {
        $this->cartItem = $cartItem;
        $this->cart = $cart;
        $this->product = $product;
        $this->user = $user;
    }

    // اگر نیاز به کانال‌های برودکست دارید، می‌توانید متد broadcastOn را اینجا تعریف کنید.
    // public function broadcastOn(): array
    // {
    //     return [
    //         new PrivateChannel('cart.'.$this->cart->id),
    //     ];
    // }
}
