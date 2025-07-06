<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'status',
        'slug',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Get the category that owns the product.
     * A product belongs to one category (Many-to-One relationship).
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Define the relationship to product images.
     * A product can have many images (One-to-Many relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class); // Requires use App\Models\ProductImage;
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        // Delete main image when product is deleted
        static::deleting(function ($product) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            // Also delete all associated product images when the product is deleted
            $product->images()->each(function ($image) {
                if (Storage::disk('public')->exists($image->image_path)) {
                    Storage::disk('public')->delete($image->image_path);
                }
                $image->delete();
            });
        });
    }

    /**
     * Accessor to get the full image URL of the product.
     * This method makes 'image_url' available as a virtual attribute.
     *
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        // If the 'image' column in the database is empty (no image uploaded for the product),
        // return a default placeholder URL.
        if (!$this->image) {
            // Default placeholder image URL
            return 'https://placehold.co/600x600/E5E7EB/4B5563?text=No+Image';
        }

        // Use Storage::disk('public')->url() to convert the relative path to a full URL.
        // 'public' is the disk name defined in your .env file as FILESYSTEM_DISK=public.
        // $this->image is the relative path like 'images/products/1.jpg' coming from the database.
        return Storage::disk('public')->url($this->image);
    }

    /**
     * Accessor for formatted price.
     * Returns the price formatted with commas and 'تومان'.
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 0) . ' تومان';
    }

    /**
     * Mutator for the 'title' attribute.
     * Converts the first letter of each word to uppercase and the rest to lowercase.
     * Also generates a slug from the title.
     *
     * @param string $value
     * @return void
     */
    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = Str::ucwords(Str::lower($value));
        $this->attributes['slug'] = Str::slug($value);
    }

    /**
     * Scope a query to only include active products.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include products that are in stock.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope a query to search products by title or description.
     * Improved to encapsulate the OR condition.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Additional useful relationships (uncomment and add necessary 'use' statements if needed)
    /**
     * Get the order items for the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class); // Requires use App\Models\OrderItem;
    // }

    /**
     * Get the reviews for the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function reviews()
    // {
    //     return $this->hasMany(Review::class); // Requires use App\Models\Review;
    // }
}
