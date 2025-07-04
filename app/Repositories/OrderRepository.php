<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class OrderRepository
{
    /**
     * Create a new order in the database.
     * یک سفارش جدید در پایگاه داده ایجاد می‌کند.
     *
     * @param array $data داده‌های سفارش شامل اطلاعات مشتری و آدرس.
     * @return Order
     */
    public function createOrder(array $data): Order
    {
        Log::info('Creating new order', ['data' => $data]);
        return Order::create($data);
    }

    /**
     * Add items to an existing order.
     * آیتم‌ها را به یک سفارش موجود اضافه می‌کند.
     *
     * @param Order $order آبجکت سفارش.
     * @param Collection $cartItems مجموعه آیتم‌های سبد خرید.
     * @return void
     */
    public function addOrderItems(Order $order, Collection $cartItems): void
    {
        Log::info('Adding order items', ['order_id' => $order->id, 'item_count' => $cartItems->count()]);
        foreach ($cartItems as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price, // قیمت از CartItem گرفته می‌شود
            ]);
        }
    }

    /**
     * Find an order by its ID.
     * یک سفارش را بر اساس شناسه آن پیدا می‌کند.
     *
     * @param int $orderId شناسه سفارش.
     * @return Order|null
     */
    public function findById(int $orderId): ?Order
    {
        return Order::find($orderId);
    }
}
