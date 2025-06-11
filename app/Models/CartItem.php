<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
    ];

    /**
     * Get the cart that owns the cart item.
     *
     * یک آیتم سبد خرید متعلق به یک سبد خرید است (Many-to-One relationship).
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the product associated with the cart item.
     *
     * یک آیتم سبد خرید متعلق به یک محصول است (Many-to-One relationship).
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
