<?php
// File: app/Repositories/Eloquent/ProductRepository.php
namespace App\Repositories\Eloquent;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use App\Contracts\Repositories\ProductRepositoryInterface; // بهبود: ایمپورت کردن اینترفیس

class ProductRepository implements ProductRepositoryInterface // بهبود: پیاده‌سازی اینترفیس
{
    /**
     * Find a product by its ID.
     * جستجوی یک محصول بر اساس شناسه (ID) آن.
     * اگر محصولی با شناسه داده شده یافت شود، شیء Product آن را برمی‌گرداند؛ در غیر این صورت null برمی‌گرداند.
     * این متد برای بازیابی ساده یک محصول استفاده می‌شود.
     *
     * @param int $id
     * @return Product|null
     */
    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * Find multiple products by their IDs.
     * جستجوی چندین محصول بر اساس آرایه‌ای از شناسه‌های (IDs) آن‌ها.
     * از متد whereIn برای فیلتر کردن محصولات بر اساس شناسه‌های موجود در آرایه استفاده می‌کند
     * و یک Collection از اشیاء Product را برمی‌گرداند.
     *
     * @param array $ids
     * @return Collection<Product>
     */
    public function findByIds(array $ids): Collection
    {
        return Product::whereIn('id', $ids)->get();
    }

    /**
     * Find a product by its ID and lock it for update.
     * جستجوی یک محصول با شناسه (ID) به‌علاوه قفل FOR UPDATE.
     * این قفل از تغییرات همزمان ناخواسته (Race Condition) جلوگیری می‌کند؛
     * به‌ویژه در عملیات حساس مثل کاهش موجودی انبار.
     *
     * @param int $id
     * @return Product|null
     */
    public function findByIdWithLock(int $id): ?Product
    {
        // Use the Product model directly to query and apply lockForUpdate
        // از مدل Product مستقیماً برای کوئری و اعمال lockForUpdate استفاده کنید.
        return Product::where('id', $id)->lockForUpdate()->first();
    }

    /**
     * Update product stock.
     * تغییر مقدار موجودی (stock) محصول و ذخیره آن در دیتابیس.
     * نتیجه عملیات: موفق (true) یا ناموفق (false).
     *
     * @param Product $product
     * @param int $newStock
     * @return bool
     */
    public function updateStock(Product $product, int $newStock): bool
    {
        $product->stock = $newStock;
        return $product->save();
    }
}
