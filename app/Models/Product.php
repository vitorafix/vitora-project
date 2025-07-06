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
     * Accessor برای دریافت آدرس URL تصویر محصول.
     * این متد ابتدا تصاویر گالری، سپس فیلد 'image' خود محصول و در نهایت تصویر پیش‌فرض را بررسی می‌کند.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        // 1. اگر رابطه 'images' لود شده باشد و حداقل یک تصویر در گالری وجود داشته باشد،
        // آدرس اولین تصویر گالری را برمی‌گرداند.
        // همچنین بررسی می‌کند که آیا فایل تصویر در دیسک وجود دارد یا خیر.
        if ($this->relationLoaded('images') && $this->images->count() > 0) {
            // اطمینان از وجود فیلد image_url در مدل ProductImage
            // و اینکه آیا آدرس تصویر معتبر است
            if (!empty($this->images->first()->image_url) && Storage::disk('public')->exists($this->images->first()->image_url)) {
                return asset('storage/' . $this->images->first()->image_url);
            }
        }

        // 2. اگر فیلد 'image' در خود محصول (جدول products) خالی نباشد و فایل آن در storage/app/public موجود باشد،
        // آدرس آن را با استفاده از asset('storage/') برمی‌گرداند.
        // فرض بر این است که 'image' حاوی مسیر نسبی مانند 'images/products/1.jpg' است.
        if (!empty($this->image) && Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . $this->image);
        }

        // 3. در صورت عدم وجود هیچ تصویری، آدرس تصویر پیش‌فرض را برمی‌گرداند.
        return 'https://placehold.co/400x400/E5E7EB/4B5563?text=Product';
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
