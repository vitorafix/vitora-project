<?php

namespace App\Observers;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Log; // برای اهداف لاگ‌گیری، اختیاری
use Illuminate\Support\Facades\Storage; // برای مدیریت فایل در رویداد updating
use Illuminate\Support\Facades\Cache; // برای مدیریت کش در رویداد updated
// use App\Events\ProductImageUploaded; // اگر این رویداد را برای نوتیفیکیشن‌ها دارید، کامنت را بردارید

class ProductImageObserver
{
    /**
     * Handle the ProductImage "creating" event.
     * این متد قبل از ذخیره یک رکورد جدید ProductImage فراخوانی می‌شود.
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function creating(ProductImage $productImage): void
    {
        // Add any logic here that needs to run before a new ProductImage is created.
        // For example, generating a unique ID or validating data.
        Log::info('ProductImage creating event fired for image: ' . $productImage->image_path);
    }

    /**
     * Handle the ProductImage "created" event.
     * این متد پس از ذخیره یک رکورد جدید ProductImage فراخوانی می‌شود.
     * Includes example for notification system.
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function created(ProductImage $productImage): void
    {
        // Add any logic here that needs to run after a new ProductImage has been created.
        // For example, sending notifications or updating related records.
        Log::info('ProductImage created event fired for image ID: ' . $productImage->id);

        // Example: Dispatch an event for notification system
        // if (class_exists(ProductImageUploaded::class)) {
        //     event(new ProductImageUploaded($productImage));
        // }
    }

    /**
     * Handle the ProductImage "updating" event.
     * این متد قبل از به‌روزرسانی یک رکورد ProductImage موجود فراخوانی می‌شود.
     * Includes example for managing old image files.
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function updating(ProductImage $productImage): void
    {
        // Add any logic here that needs to run before an existing ProductImage is updated.
        // Example: Check if image_path has changed and delete the old file.
        if ($productImage->isDirty('image_path')) {
            $oldPath = $productImage->getOriginal('image_path');
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
                Log::info('Old image deleted for ProductImage ID: ' . $productImage->id . ' - Path: ' . $oldPath);
            }
        }
        Log::info('ProductImage updating event fired for image ID: ' . $productImage->id);
    }

    /**
     * Handle the ProductImage "updated" event.
     * این متد پس از به‌روزرسانی یک رکورد ProductImage موجود فراخوانی می‌شود.
     * Includes example for cache management.
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function updated(ProductImage $productImage): void
    {
        // Add any logic here that needs to run after an existing ProductImage has been updated.
        // Example: Clear cache related to this product's images.
        Cache::forget("product_images_{$productImage->product_id}");
        Log::info('ProductImage updated event fired for image ID: ' . $productImage->id . ' - Cache cleared.');
    }

    /**
     * Handle the ProductImage "deleting" event.
     * این متد قبل از حذف یک رکورد ProductImage فراخوانی می‌شود.
     * (توجه: متد deleteImage() در خود مدل ProductImage فراخوانی می‌شود،
     * اما می‌توانید منطق اضافی را در اینجا اضافه کنید.)
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function deleting(ProductImage $productImage): void
    {
        // Add any logic here that needs to run before a ProductImage is deleted.
        // For example, preventing deletion based on certain conditions.
        Log::info('ProductImage deleting event fired for image ID: ' . $productImage->id);
    }

    /**
     * Handle the ProductImage "deleted" event.
     * این متد پس از حذف یک رکورد ProductImage فراخوانی می‌شود.
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function deleted(ProductImage $productImage): void
    {
        // Add any logic here that needs to run after a ProductImage has been deleted.
        // For example, cleaning up related data or logging.
        Log::info('ProductImage deleted event fired for image ID: ' . $productImage->id);
    }

    /**
     * Handle the ProductImage "restoring" event.
     * این متد قبل از بازیابی یک رکورد ProductImage (فقط برای Soft Deletes) فراخوانی می‌شود.
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function restoring(ProductImage $productImage): void
    {
        // Add any logic here that needs to run before a soft-deleted ProductImage is restored.
        Log::info('ProductImage restoring event fired for image ID: ' . $productImage->id);
    }

    /**
     * Handle the ProductImage "restored" event.
     * این متد پس از بازیابی یک رکورد ProductImage (فقط برای Soft Deletes) فراخوانی می‌شود.
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function restored(ProductImage $productImage): void
    {
        // Add any logic here that needs to run after a soft-deleted ProductImage has been restored.
        Log::info('ProductImage restored event fired for image ID: ' . $productImage->id);
    }

    /**
     * Handle the ProductImage "forceDeleted" event.
     * این متد پس از حذف دائمی یک رکورد ProductImage (فقط برای Soft Deletes) فراخوانی می‌شود.
     *
     * @param  \App\Models\ProductImage  $productImage
     * @return void
     */
    public function forceDeleted(ProductImage $productImage): void
    {
        // Add any logic here that needs to run after a ProductImage has been permanently deleted.
        Log::info('ProductImage forceDeleted event fired for image ID: ' . $productImage->id);
    }
}
