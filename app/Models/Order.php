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
        'session_id',
        'total_amount',
        'status',
        'address',
        'city',
        'province',
        'postal_code',
        'first_name',   // اضافه شده: نام مشتری
        'last_name',    // اضافه شده: نام خانوادگی مشتری
        'phone_number', // اضافه شده: شماره تلفن مشتری
    ];

    /**
     * Get the user that owns the order.
     *
     * یک سفارش می‌تواند متعلق به یک کاربر باشد (Many-to-One relationship).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     *
     * یک سفارش می‌تواند شامل چندین آیتم باشد (One-to-Many relationship).
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}

