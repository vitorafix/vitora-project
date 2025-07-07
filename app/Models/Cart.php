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
     * Calculate the total price of items in the cart.
     * مجموع قیمت تمامی آیتم‌های موجود در سبد خرید را محاسبه و برمی‌گرداند.
     *
     * @return float
     */
    public function getTotalPrice()
    {
        // با استفاده از eager loading قبلی، آیتم‌ها از قبل لود شده‌اند.
        // اگر آیتم‌ها لود نشده باشند، N+1 query ایجاد می‌کند. بهتر است قبل از فراخوانی، items() با with('product') لود شود.
        // This method might become less relevant if `total` is stored and updated by the service.
        // However, it can still be used for on-the-fly calculation if needed.
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
    }

    /**
     * Find a cart by user ID or session ID, or create a new one.
     * یک سبد خرید را بر اساس ID کاربر یا ID سشن پیدا می‌کند، یا یک سبد خرید جدید ایجاد می‌کند.
     *
     * @param int|null $userId
     * @return static
     */
    public static function findOrCreateCart(?int $userId = null)
    {
        if ($userId) {
            return static::firstOrCreate(['user_id' => $userId]);
        }

        $sessionId = Session::getId();
        return static::firstOrCreate(['session_id' => $sessionId]);
    }
}

