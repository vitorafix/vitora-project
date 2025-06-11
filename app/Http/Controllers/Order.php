<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id', // برای کاربران مهمان
        'total_amount',
        'status', // وضعیت سفارش (مثلاً pending, completed, cancelled)
        'address',
        'city',
        'province',
        'postal_code',
        // فیلدهای اضافی دیگر مانند نام، نام خانوادگی، شماره تلفن را در اینجا اضافه کنید
    ];

    /**
     * Get the user that owns the order.
     * یک سفارش می‌تواند به یک کاربر تعلق داشته باشد (Many-to-One relationship).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     * یک سفارش می‌تواند شامل چندین آیتم باشد (One-to-Many relationship).
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
