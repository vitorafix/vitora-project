<?php
// File: app/Services/Managers/StockManager.php
namespace App\Services\Managers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // اضافه شده برای استفاده از تراکنش و قفل
use App\Exceptions\InsufficientStockException; // Custom exception

class StockManager
{
    private bool $stockCheckEnabled;

    public function __construct()
    {
        $this->stockCheckEnabled = config('cart.stock_check_enabled', true);
        // stockReservationMinutes دیگر برای مدیریت موجودی رزرو شده در دیتابیس لازم نیست
    }

    /**
     * Validates if enough stock is available for a product.
     * اعتبارسنجی می‌کند که آیا موجودی کافی برای یک محصول موجود است یا خیر.
     *
     * @param Product $product
     * @param int $quantity
     * @throws InsufficientStockException
     */
    public function validateStock(Product $product, int $quantity): void
    {
        if (!$this->stockCheckEnabled) {
            return;
        }

        if ($quantity <= 0) {
            throw new InsufficientStockException('تعداد محصول باید مثبت باشد.'); // Quantity must be positive.
        }

        // موجودی قابل دسترس را از دیتابیس با آخرین وضعیت دریافت کنید
        // نیازی به قفل در اینجا نیست، زیرا این فقط یک عملیات خواندن است
        // قفل در reserveStock و releaseStock اعمال می شود
        $currentProduct = Product::find($product->id); // برای اطمینان از دریافت آخرین وضعیت
        if (!$currentProduct) {
            throw new InsufficientStockException('محصول یافت نشد.');
        }

        $availableStock = $currentProduct->stock - ($currentProduct->reserved_stock ?? 0); // فرض بر وجود ستون reserved_stock

        if ($availableStock < $quantity) {
            Log::warning('Insufficient stock for product', ['product_id' => $product->id, 'requested_quantity' => $quantity, 'available_stock' => $availableStock]);
            throw new InsufficientStockException('موجودی کافی برای محصول ' . $product->name . ' وجود ندارد. موجودی فعلی: ' . $availableStock);
        }
    }

    /**
     * Reserves stock for a product, using pessimistic locking.
     * موجودی محصول را رزرو می‌کند، با استفاده از قفل بدبینانه.
     *
     * @param Product $product
     * @param int $quantity
     * @return bool
     */
    public function reserveStock(Product $product, int $quantity): bool
    {
        if (!$this->stockCheckEnabled) {
            return true;
        }

        if ($quantity <= 0) {
            return false; // Cannot reserve non-positive quantity
        }

        try {
            DB::beginTransaction();

            // قفل کردن رکورد محصول برای جلوگیری از Race Condition
            $currentProduct = Product::where('id', $product->id)->lockForUpdate()->first();

            if (!$currentProduct) {
                DB::rollBack();
                Log::error('Product not found for stock reservation.', ['product_id' => $product->id]);
                return false;
            }

            $currentReserved = $currentProduct->reserved_stock ?? 0;
            $availableStock = $currentProduct->stock - $currentReserved;

            if ($availableStock < $quantity) {
                DB::rollBack();
                Log::warning('Insufficient stock during reservation due to race condition or prior check failure.', [
                    'product_id' => $product->id,
                    'requested_quantity' => $quantity,
                    'available_stock' => $availableStock,
                    'current_stock' => $currentProduct->stock,
                    'current_reserved' => $currentReserved
                ]);
                // در اینجا می توانید یک استثنا پرتاب کنید یا پیام خطای خاصی برگردانید
                // برای سادگی، فقط false برمی گردانیم
                return false;
            }

            $currentProduct->reserved_stock += $quantity;
            $currentProduct->save();

            DB::commit();

            Log::info('Stock reserved', ['product_id' => $product->id, 'quantity' => $quantity, 'new_reserved' => $currentProduct->reserved_stock]);
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error reserving stock: ' . $e->getMessage(), ['product_id' => $product->id, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return false;
        }
    }

    /**
     * Releases reserved stock for a product, using pessimistic locking.
     * موجودی رزرو شده محصول را آزاد می‌کند، با استفاده از قفل بدبینانه.
     *
     * @param Product $product
     * @param int $quantity
     * @return bool
     */
    public function releaseStock(Product $product, int $quantity): bool
    {
        if (!$this->stockCheckEnabled) {
            return true;
        }

        if ($quantity <= 0) {
            return false; // Cannot release non-positive quantity
        }

        try {
            DB::beginTransaction();

            // قفل کردن رکورد محصول برای جلوگیری از Race Condition
            $currentProduct = Product::where('id', $product->id)->lockForUpdate()->first();

            if (!$currentProduct) {
                DB::rollBack();
                Log::error('Product not found for stock release.', ['product_id' => $product->id]);
                return false;
            }

            $currentReserved = $currentProduct->reserved_stock ?? 0;
            $newReserved = max(0, $currentReserved - $quantity); // اطمینان از عدم منفی شدن موجودی رزرو شده

            $currentProduct->reserved_stock = $newReserved;
            $currentProduct->save();

            DB::commit();

            Log::info('Stock released', ['product_id' => $product->id, 'quantity' => $quantity, 'new_reserved' => $currentProduct->reserved_stock]);
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error releasing stock: ' . $e->getMessage(), ['product_id' => $product->id, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return false;
        }
    }

    /**
     * Gets the currently reserved stock for a product from the database.
     * موجودی فعلی رزرو شده برای یک محصول را از پایگاه داده دریافت می‌کند.
     *
     * @param int $productId
     * @return int
     */
    public function getReservedStock(int $productId): int
    {
        $product = Product::find($productId);
        return $product ? ($product->reserved_stock ?? 0) : 0;
    }

    /**
     * Performs a health check for the stock manager.
     * یک بررسی سلامت برای مدیر موجودی انجام می‌دهد.
     *
     * @return array
     */
    public function healthCheck(): array
    {
        try {
            Product::first(); // Simple check to see if Product model can access DB
            return ['status' => 'ok', 'message' => 'Stock manager is operational.'];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => 'Stock manager error: ' . $e->getMessage()];
        }
    }
}
