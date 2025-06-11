<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'price',
        'stock',
        'image',
        'category_id',
    ];

    /**
     * Get the category that owns the product.
     *
     * یک محصول متعلق به یک دسته‌بندی است (Many-to-One relationship).
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
