<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // For using the Storage facade
use App\Observers\ProductImageObserver; // Import the observer

class ProductImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'image_path',
    ];

    /**
     * The attributes that should be cast.
     * Added casts for timestamps.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     * This ensures 'image_url' is always included when converting to array/JSON.
     *
     * @var array
     */
    protected $appends = [
        'image_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Useful for security if certain fields should not be exposed in API responses.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'image_path', // Uncomment if you want to hide the raw path in API responses
    ];

    /**
     * Get the product that owns the image.
     * A product image belongs to one product (Many-to-One relationship).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor to get the full image URL of the product image.
     * This method makes 'image_url' available as a virtual attribute.
     * Optimized to use empty() and provide a placeholder.
     *
     * @return string
     */
    public function getImageUrlAttribute(): string
    {
        // If the 'image_path' column in the database is empty (no image uploaded),
        // return a default placeholder URL.
        if (empty($this->image_path)) {
            // Default placeholder image URL
            return 'https://placehold.co/600x600/E5E7EB/4B5563?text=No+Image'; // Using the existing placeholder for consistency
        }

        // Use Storage::disk('public')->url() to convert the relative path to a full URL.
        // 'public' is the disk name defined in your .env file as FILESYSTEM_DISK=public.
        // $this->image_path is the relative path like 'images/products/gallery/1.jpg' coming from the database.
        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Helper method to delete the associated image file from storage.
     *
     * @return bool
     */
    public function deleteImage(): bool
    {
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            return Storage::disk('public')->delete($this->image_path);
        }
        return true; // Return true even if no image exists or path is empty
    }

    /**
     * Helper method to check if an image file exists for this record.
     *
     * @return bool
     */
    public function hasImage(): bool
    {
        return !empty($this->image_path) && Storage::disk('public')->exists($this->image_path);
    }

    /**
     * Define validation rules for ProductImage.
     *
     * @return array
     */
    public static function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'image_path' => 'required|string|max:255',
        ];
    }

    /**
     * Scope a query to only include images for a specific product.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * The "booting" method of the model.
     * Used to register event listeners and observers.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Register a 'deleting' event listener to automatically delete the image file
        // from storage when a ProductImage record is deleted from the database.
        // This is an alternative to using an Observer for simple cases, but for comprehensive
        // lifecycle management, an Observer (like ProductImageObserver) is preferred.
        static::deleting(function ($productImage) {
            $productImage->deleteImage();
        });

        // Register the ProductImageObserver for more comprehensive lifecycle management.
        // Uncomment this line after you have moved ProductImageObserver.php to app/Observers/
        // and registered it in AppServiceProvider.php (or EventServiceProvider.php).
        // ProductImage::observe(ProductImageObserver::class);
    }
}
