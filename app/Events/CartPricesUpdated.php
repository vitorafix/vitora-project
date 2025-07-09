<?php

namespace App\Events;

use App\Models\Cart;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartPricesUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Cart $cart;
    public int $updatedCount;

    /**
     * Create a new event instance.
     * یک نمونه رویداد جدید ایجاد کنید.
     *
     * @param Cart $cart The cart whose prices were updated.
     * @param int $updatedCount The number of items whose prices were updated.
     */
    public function __construct(Cart $cart, int $updatedCount)
    {
        $this->cart = $cart;
        $this->updatedCount = $updatedCount;
    }
}
