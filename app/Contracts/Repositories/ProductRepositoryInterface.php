<?php
// File: app/Contracts/Repositories/ProductRepositoryInterface.php
namespace App\Contracts\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Find a product by its ID.
     * یک محصول را بر اساس شناسه آن پیدا می‌کند.
     *
     * @param int $id
     * @return Product|null
     */
    public function find(int $id): ?Product;

    /**
     * Find multiple products by their IDs.
     * چندین محصول را بر اساس شناسه‌های آن‌ها پیدا می‌کند.
     *
     * @param array $ids
     * @return Collection<Product>
     */
    public function findByIds(array $ids): Collection;

    /**
     * Update product stock.
     * موجودی محصول را به‌روزرسانی می‌کند.
     *
     * @param Product $product
     * @param int $newStock
     * @return bool
     */
    public function updateStock(Product $product, int $newStock): bool;
}
