<?php

namespace App\Services;

use App\Models\Product;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for managing product stock operations.
 * This class encapsulates the logic for validating, reserving,
 * and releasing product stock.
 */
class StockService
{
    /**
     * Validates if the requested quantity of a product is available in stock.
     *
     * @param Product $product The product to check stock for.
     * @param int $quantity The quantity requested.
     * @throws InsufficientStockException If the stock is insufficient.
     * @return void
     */
    public function validateStock(Product $product, int $quantity): void
    {
        // Ensure the product stock is up-to-date before validation
        $product->refresh();

        if ($product->stock < $quantity) {
            Log::warning('Insufficient stock for product', [
                'product_id' => $product->id,
                'requested_quantity' => $quantity,
                'available_stock' => $product->stock
            ]);
            throw new InsufficientStockException("موجودی محصول '{$product->title}' کافی نیست. تعداد موجود: {$product->stock}");
        }
    }

    /**
     * Reserves a specified quantity of a product from stock.
     * This method decrements the product's stock.
     *
     * @param Product $product The product to reserve stock for.
     * @param int $quantity The quantity to reserve.
     * @param int|null $minutes Optional: Time in minutes for temporary reservation (not implemented in this basic version).
     * @return bool True if stock was successfully reserved, false otherwise.
     */
    public function reserveStock(Product $product, int $quantity, ?int $minutes = null): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        // Use a transaction to ensure atomicity for stock updates
        return DB::transaction(function () use ($product, $quantity) {
            // Re-fetch the product within the transaction to get the latest stock value
            $product->refresh();

            if ($product->stock < $quantity) {
                Log::error('Attempted to reserve more stock than available after refresh', [
                    'product_id' => $product->id,
                    'requested_quantity' => $quantity,
                    'available_stock' => $product->stock
                ]);
                // Optionally throw an exception here if strict validation is needed post-refresh
                return false;
            }

            $product->decrement('stock', $quantity);
            Log::info('Stock reserved', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'new_stock' => $product->stock
            ]);
            return true;
        });
    }

    /**
     * Releases a specified quantity of a product back to stock.
     * This method increments the product's stock.
     *
     * @param Product $product The product to release stock for.
     * @param int $quantity The quantity to release.
     * @return bool True if stock was successfully released, false otherwise.
     */
    public function releaseStock(Product $product, int $quantity): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        // Use a transaction to ensure atomicity for stock updates
        return DB::transaction(function () use ($product, $quantity) {
            // Re-fetch the product within the transaction to get the latest stock value
            $product->refresh();

            $product->increment('stock', $quantity);
            Log::info('Stock released', [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'new_stock' => $product->stock
            ]);
            return true;
        });
    }
}
