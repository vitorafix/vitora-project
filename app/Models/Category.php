<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get the products for the category.
     *
     * یک دسته‌بندی می‌تواند محصولات زیادی داشته باشد (One-to-Many relationship).
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
