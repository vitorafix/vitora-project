<?php
// File: app/Services/Managers/StockManager.php (این کامنت به اینجا منتقل شد)
namespace App\Services\Managers;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // For stock reservation
use App\Exceptions\InsufficientStockException; // Custom exception

class StockManager
{
    private bool $stockCheckEnabled;
    private int $stockReservationMinutes;

    public function __construct()
    {
        $this->stockCheckEnabled = config('cart.stock_check_enabled', true);
        $this->stockReservationMinutes = config('cart.stock_reservation_minutes', 15);
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

        // Check actual stock
        // Ensure that reserved stock is also considered when validating
        $reservedStock = $this->getReservedStock($product->id);
        if (($product->stock - $reservedStock) < $quantity) {
            Log::warning('Insufficient stock for product', ['product_id' => $product->id, 'requested_quantity' => $quantity, 'available_stock' => $product->stock - $reservedStock]);
            throw new InsufficientStockException('موجودی کافی برای محصول ' . $product->name . ' وجود ندارد. موجودی فعلی: ' . ($product->stock - $reservedStock));
        }
    }

    /**
     * Reserves stock for a product.
     * موجودی محصول را رزرو می‌کند.
     *
     * @param Product $product
     * @param int $quantity
     * @param int|null $minutes The duration in minutes for which the stock should be reserved.
     * @return bool
     */
    public function reserveStock(Product $product, int $quantity, ?int $minutes = null): bool
    {
        if (!$this->stockCheckEnabled) {
            return true;
        }

        if ($quantity <= 0) {
            return false; // Cannot reserve non-positive quantity
        }

        $cacheKey = 'reserved_stock_' . $product->id;
        $reserved = Cache::increment($cacheKey, $quantity);
        Cache::put($cacheKey, $reserved, $minutes ?? $this->stockReservationMinutes * 60); // Store for defined minutes

        Log::info('Stock reserved', ['product_id' => $product->id, 'quantity' => $quantity, 'new_reserved' => $reserved]);
        return true;
    }

    /**
     * Releases reserved stock for a product.
     * موجودی رزرو شده محصول را آزاد می‌کند.
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

        $cacheKey = 'reserved_stock_' . $product->id;
        try {
            // Ensure reserved stock doesn't go below zero
            $currentReserved = Cache::get($cacheKey, 0);
            $newReserved = max(0, $currentReserved - $quantity);
            Cache::put($cacheKey, $newReserved, $this->stockReservationMinutes * 60); // Update cache with new value

            Log::info('Stock released', ['product_id' => $product->id, 'quantity' => $quantity, 'new_reserved' => Cache::get($cacheKey)]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error releasing stock: ' . $e->getMessage(), ['product_id' => $product->id, 'quantity' => $quantity]);
            return false;
        }
    }

    /**
     * Gets the currently reserved stock for a product.
     * موجودی فعلی رزرو شده برای یک محصول را دریافت می‌کند.
     *
     * @param int $productId
     * @return int
     */
    public function getReservedStock(int $productId): int
    {
        return Cache::get('reserved_stock_' . $productId, 0);
    }

    /**
     * Performs a health check for the stock manager.
     * یک بررسی سلامت برای مدیر موجودی انجام می‌دهد.
     *
     * @return array
     */
    public function healthCheck(): array
    {
        // In a real scenario, you might check database connectivity for product stock.
        // در یک سناریوی واقعی، ممکن است اتصال پایگاه داده را برای موجودی محصول بررسی کنید.
        try {
            Product::first(); // Simple check to see if Product model can access DB
            return ['status' => 'ok', 'message' => 'Stock manager is operational.'];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => 'Stock manager error: ' . $e->getMessage()];
        }
    }
}
