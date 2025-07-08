<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // مطمئن شوید که این خط وجود دارد

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
        // 'status' => 'boolean', // این خط کامنت شد زیرا status در دیتابیس enum است
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
     * این متد هنگام بوت شدن مدل فراخوانی می‌شود.
     *
     * @return void
     */
    protected static function booted(): void
    {
        // حذف تصویر اصلی هنگام حذف محصول
        static::deleting(function ($product) {
            if ($product->image) {
                // مسیر را نرمال‌سازی کنید تا از خطاهای مسیر خراب جلوگیری شود
                $normalizedPath = trim(str_replace(['\\', '//'], '/', $product->image));
                // بررسی وجود فایل قبل از حذف (اختیاری، اما توصیه می‌شود)
                if (Storage::disk('public')->exists($normalizedPath)) {
                    Storage::disk('public')->delete($normalizedPath);
                }
            }
            // همچنین تمام تصاویر گالری مرتبط با محصول را هنگام حذف محصول پاک کنید
            $product->images()->each(function ($image) {
                if ($image->image_path) {
                    // مسیر را نرمال‌سازی کنید تا از خطاهای مسیر خراب جلوگیری شود
                    $normalizedImagePath = trim(str_replace(['\\', '//'], '/', $image->image_path));
                    // بررسی وجود فایل قبل از حذف (اختیاری، اما توصیه می‌شود)
                    if (Storage::disk('public')->exists($normalizedImagePath)) {
                        Storage::disk('public')->delete($normalizedImagePath);
                    }
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
        // این بخش دیگر وجود فایل را در بک‌اند بررسی نمی‌کند و به مرورگر اجازه می‌دهد تا با onerror آن را مدیریت کند.
        if ($this->relationLoaded('images') && $this->images->count() > 0) {
            $firstImage = $this->images->first();
            // فرض بر این است که ProductImage دارای فیلد image_path است
            if (!empty($firstImage->image_path)) {
                // مسیر را نرمال‌سازی کنید تا از خطاهای مسیر خراب جلوگیری شود
                $normalizedImagePath = trim(str_replace(['\\', '//'], '/', $firstImage->image_path));
                return asset('storage/' . $normalizedImagePath);
            }
        }

        // 2. اگر فیلد 'image' در خود محصول (جدول products) خالی نباشد،
        // آدرس آن را با استفاده از asset('storage/') برمی‌گرداند.
        // این بخش نیز وجود فایل را در بک‌اند بررسی نمی‌کند.
        if (!empty($this->image)) {
            // مسیر را نرمال‌سازی کنید تا از خطاهای مسیر خراب جلوگیری شود
            $normalizedProductImage = trim(str_replace(['\\', '//'], '/', $this->image));
            return asset('storage/' . $normalizedProductImage);
        }

        // 3. در صورت عدم وجود هیچ تصویری، آدرس تصویر پیش‌فرض را برمی‌گرداند.
        return 'https://placehold.co/400x400/E5E7EB/4B5563?text=Product';
    }

    /**
     * Accessor برای بررسی فعال بودن محصول.
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
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
        // استفاده از Str::title به جای Str::ucwords
        $this->attributes['title'] = Str::title($value);
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
        return $query->where('status', 'active'); // تغییر از true به 'active'
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
