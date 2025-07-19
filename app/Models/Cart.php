<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session; // برای دسترسی به سشن

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id', // برای سبد خرید مهمان
        'guest_uuid', // New: To store the unique guest identifier
        'coupon_id', // New: To store the ID of the applied coupon
        'discount_amount', // New: To store the discount amount applied by the coupon
        'subtotal', // New: To store the subtotal before discount/shipping/tax
        'total', // New: To store the final total after all calculations
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        // 'guest_uuid' => 'string', // Optional: You can cast UUID to string if needed, but not strictly required
    ];

    /**
     * Get the cart items for the cart.
     * یک سبد خرید می‌تواند شامل چندین آیتم باشد (One-to-Many relationship).
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the coupon that was applied to the cart.
     * یک سبد خرید می‌تواند یک کد تخفیف داشته باشد (Many-to-One relationship).
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Removed: getTotalPrice method has been moved to CartCalculationService.
     * این متد از مدل حذف شده و مسئولیت محاسبه به CartCalculationService منتقل شده است.
     */
}
