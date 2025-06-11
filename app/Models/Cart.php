<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'session_id',
        // 'status' اگر نیاز به ستون وضعیت برای سبد خرید دارید (مثلاً 'فعال', 'تکمیل شده')
    ];

    /**
     * Get the user that owns the cart.
     *
     * یک سبد خرید می‌تواند به یک کاربر تعلق داشته باشد (Many-to-One relationship).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cart items for the cart.
     *
     * یک سبد خرید می‌تواند شامل چندین آیتم باشد (One-to-Many relationship).
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate the total price of the cart.
     *
     * متدی برای محاسبه مجموع قیمت آیتم‌های موجود در سبد خرید.
     */
    public function getTotalPrice()
    {
        return $this->items->sum(function($item) {
            return $item->price * $item->quantity;
        });
    }
}
