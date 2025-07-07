<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 * title="ProductVariant",
 * description="Product variant model",
 * @OA\Xml(
 * name="ProductVariant"
 * )
 * )
 */
class ProductVariant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_variants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'name', // e.g., "Color", "Size"
        'value', // e.g., "Red", "Large"
        'sku',
        'price_adjustment', // e.g., +10.00 for a larger size
        'stock',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * Get the product that owns the variant.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

