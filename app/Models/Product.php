<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // اضافه شده برای مدیریت تصاویر

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
        'status', // اضافه شده: برای هماهنگی با اعتبارسنجی status در AddToCartRequest
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2', // قیمت را به صورت عدد اعشاری با 2 رقم دقت تبدیل می‌کند
        'stock' => 'integer',   // موجودی را به صورت عدد صحیح تبدیل می‌کند
        'status' => 'boolean',  // وضعیت را به صورت boolean تبدیل می‌کند (اگر 0/1 باشد)
        // اگر status از نوع enum در دیتابیس است، نیازی به cast به boolean نیست و می‌توانید آن را حذف کنید.
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

    /**
     * Get the product variants for the product.
     *
     * یک محصول می‌تواند چندین واریانت داشته باشد (One-to-Many relationship).
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Scope a query to only include active products.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', true); // فرض بر این است که status boolean است یا 'active'
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
     * Get the formatted price of the product.
     *
     * @return string
     */
    public function getFormattedPriceAttribute()
    {
        // بهبود: حذف 0 از number_format برای قیمت‌های بزرگتر
        return number_format($this->price) . ' تومان';
    }

    /**
     * Check if the product is available (in stock and active).
     *
     * @return bool
     */
    public function getIsAvailableAttribute()
    {
        return $this->stock > 0 && $this->status;
    }

    /**
     * Get the URL for the product image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        // استفاده از Storage::url برای مسیرهای ذخیره‌سازی عمومی
        if ($this->image) {
            return Storage::url('products/' . $this->image);
        }
        return asset('images/no-image.png');
    }

    /**
     * Get the validation rules for the product.
     *
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'status' => 'boolean', // یا in:active,inactive اگر enum است
        ];
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        // حذف تصویر هنگام حذف محصول
        static::deleting(function ($product) {
            if ($product->image) {
                // فرض می‌کنیم تصاویر در storage/app/public/products ذخیره می‌شوند
                Storage::delete('public/products/' . $product->image);
            }
        });
    }

    // روابط اضافی مفید (در صورت نیاز به فعال‌سازی، use مربوطه را اضافه کنید)
    /**
     * Get the order items for the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function orderItems()
    // {
    //     return $this->hasMany(OrderItem::class); // نیاز به use App\Models\OrderItem;
    // }

    /**
     * Get the reviews for the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function reviews()
    // {
    //     return $this->hasMany(Review::class); // نیاز به use App\Models\Review;
    // }
}
