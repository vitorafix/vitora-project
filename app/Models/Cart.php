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
     * Calculate the total price of items in the cart.
     * مجموع قیمت تمامی آیتم‌های موجود در سبد خرید را محاسبه و برمی‌گرداند.
     *
     * @return float
     */
    public function getTotalPrice()
    {
        // با استفاده از eager loading قبلی، آیتم‌ها از قبل لود شده‌اند.
        // اگر آیتم‌ها لود نشده باشند، N+1 query ایجاد می‌کند. بهتر است قبل از فراخوانی، items() با with('product') لود شود.
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
