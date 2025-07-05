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
     * یک محصول را بر اساس شناسه آن پیدا می‌کند.
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
     * چندین محصول را بر اساس شناسه‌های آن‌ها پیدا می‌کند.
     *
     * @param array $ids
     * @return Collection<Product>
     */
    public function findByIds(array $ids): Collection
    {
        return Product::whereIn('id', $ids)->get();
    }

    /**
     * Update product stock.
     * موجودی محصول را به‌روزرسانی می‌کند.
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
