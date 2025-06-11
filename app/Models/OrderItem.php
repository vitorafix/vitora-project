<?php

namespace App\Models; // این namespace باید App\Models باشد

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model // نام کلاس باید OrderItem باشد
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
    ];

    /**
     * Get the order that owns the order item.
     *
     * یک آیتم سفارش متعلق به یک سفارش است.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with the order item.
     *
     * یک آیتم سفارش مربوط به یک محصول است.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
