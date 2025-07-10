<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'expires_at',
        'is_active',
        'max_discount_amount',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * محاسبه مقدار تخفیف برای مبلغ کل سبد خرید.
     *
     * @param float $total
     * @return float
     */
    public function calculateDiscount(float $total): float
    {
        $discount = 0;

        if ($this->type === 'percentage') {
            $discount = $total * ($this->value / 100);
        } elseif ($this->type === 'fixed') {
            $discount = $this->value;
        }

        // اگر سقف تخفیف وجود دارد، مقدار تخفیف از آن بیشتر نشود
        if ($this->max_discount_amount !== null) {
            $discount = min($discount, $this->max_discount_amount);
        }

        return $discount;
    }

    /**
     * بررسی اعتبار کوپن.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->is_active
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }
}
