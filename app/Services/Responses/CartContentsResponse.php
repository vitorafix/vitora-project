<?php

namespace App\Services\Responses;

use JsonSerializable; // برای اطمینان از اینکه کلاس به درستی به JSON تبدیل شود

class CartContentsResponse implements JsonSerializable
{
    /**
     * @param array $items آرایه‌ای از آیتم‌های سبد خرید، هر کدام شامل جزئیات محصول و موجودی.
     * @param int $totalQuantity تعداد کل محصولات در سبد خرید.
     * @param float $totalPrice قیمت کل محصولات در سبد خرید.
     */
    public function __construct(
        public array $items,
        public int $totalQuantity,
        public float $totalPrice
    ) {}

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
            'total_quantity' => $this->totalQuantity, // تغییر نام به snake_case
            'total_price' => $this->totalPrice,     // تغییر نام به snake_case
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
