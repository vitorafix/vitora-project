<?php

namespace App\Services\Responses;

use JsonSerializable; // برای اطمینان از اینکه کلاس به درستی به JSON تبدیل شود
use App\DTOs\CartTotalsDTO; // اضافه شده: برای نوع‌دهی به ویژگی cartTotals

class CartContentsResponse implements JsonSerializable
{
    /**
     * @param array $items آرایه‌ای از آیتم‌های سبد خرید، هر کدام شامل جزئیات محصول و موجودی.
     * @param int $totalQuantity تعداد کل محصولات در سبد خرید.
     * @param float $totalPrice قیمت کل محصولات در سبد خرید.
     * @param CartTotalsDTO $cartTotals اطلاعات مجموع سبد خرید (زیرمجموع، تخفیف، مالیات، کل).
     */
    public function __construct(
        public array $items,
        public int $totalQuantity,
        public float $totalPrice,
        // اضافه شده: ویژگی cartTotals به سازنده
        public CartTotalsDTO $cartTotals // اطمینان حاصل کنید که این پارامتر در زمان ساخت شیء ارسال می‌شود
    ) {}

    /**
     * متد getItems() برای دریافت آیتم‌ها.
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * متد getTotalQuantity() برای دریافت تعداد کل.
     *
     * @return int
     */
    public function getTotalQuantity(): int
    {
        return $this->totalQuantity;
    }

    /**
     * متد getTotalPrice() برای دریافت قیمت کل.
     *
     * @return float
     */
    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    /**
     * متد getCartTotals() برای دریافت DTO مجموع سبد خرید.
     *
     * @return CartTotalsDTO
     */
    public function getCartTotals(): CartTotalsDTO
    {
        return $this->cartTotals;
    }

    /**
     * متد toArray() برای تبدیل شیء به آرایه.
     * این متد برای زمانی که می‌خواهیم داده‌ها را به صورت آرایه به کنترلر برگردانیم مفید است.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total_quantity' => $this->totalQuantity,
            'total_price' => $this->totalPrice,
            'cartTotals' => $this->cartTotals->toArray(), // تبدیل DTO به آرایه
        ];
    }

    /**
     * متد jsonSerialize() برای سریالایز کردن شیء به JSON.
     * این متد به طور خودکار توسط تابع json_encode() یا متد response()->json() لاراول فراخوانی می‌شود.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
