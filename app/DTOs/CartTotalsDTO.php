<?php

namespace App\DTOs;

class CartTotalsDTO
{
    /**
     * @param float $subtotal مجموع فرعی (قیمت قبل از تخفیف، مالیات و حمل و نقل)
     * @param float $discount مقدار تخفیف اعمال شده
     * @param float $shipping هزینه حمل و نقل
     * @param float $tax مقدار مالیات
     * @param float $total مجموع کل (قیمت نهایی)
     */
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $shipping,
        public readonly float $tax,
        public readonly float $total
    ) {}

    /**
     * Convert the DTO to an array.
     * DTO را به آرایه تبدیل می‌کند.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping' => $this->shipping,
            'tax' => $this->tax,
            'totalPrice' => $this->total, // اصلاح کلید اینجا به totalPrice
        ];
    }
}
