<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product; // Assuming product details are needed for price calculation
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Responses\CartOperationResponse;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface; // For refreshing prices

class CartCalculationService
{
    protected CartRepositoryInterface $cartRepository;
    protected ProductRepositoryInterface $productRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Refresh cart item prices based on current product prices in the database.
     * @param Cart $cart
     * @return CartOperationResponse
     *
     */
    public function refreshCartItemPrices(Cart $cart): CartOperationResponse
    {
        $startTime = microtime(true);
        // Ensure cart items and their associated products are loaded
        $cart->loadMissing('items.product');
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($cart->items as $item) {
                // If product exists and item price differs from product's current price, update it
                if ($item->product && $item->price !== $item->product->price) {
                    $item->price = $item->product->price;
                    // Update the cart item in the database
                    $this->cartRepository->updateCartItem($item, ['price' => $item->product->price]);
                    $updatedCount++;
                }
            }
            DB::commit();
            // In a real scenario, you might also clear cache here if applicable
            // $this->cacheManager->clearCache($cart->user, $cart->session_id); // This would come from CartService or a dedicated CacheService
            // Metrics recording would also be in a dedicated MetricsService or the main CartService
            // $this->metricsManager->recordMetric('refreshCartItemPrices_duration', microtime(true) - $startTime, ['updated_count' => $updatedCount]);
            return CartOperationResponse::success("قیمت {$updatedCount} آیتم سبد خرید به‌روزرسانی شد.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error refreshing cart item prices: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            // $this->metricsManager->recordMetric('refreshCartItemPrices_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::error('خطا در به‌روزرسانی قیمت آیتم‌های سبد خرید.', 500);
        }
    }

    /**
     * Calculates the subtotal of the cart (sum of item prices * quantities).
     * @param Cart $cart
     * @return float
     */
    public function calculateSubtotal(Cart $cart): float
    {
        $subtotal = 0.0;
        $cart->loadMissing('items'); // Ensure items are loaded
        foreach ($cart->items as $item) {
            $subtotal += $item->price * $item->quantity;
        }
        return $subtotal;
    }

    /**
     * Calculates the total price after applying discounts.
     * This method assumes discount logic (e.g., from coupons) is handled elsewhere or passed in.
     * @param Cart $cart
     * @param float $discountAmount
     * @return float
     */
    public function calculateDiscountedTotal(Cart $cart, float $discountAmount = 0.0): float
    {
        $subtotal = $this->calculateSubtotal($cart);
        // Ensure discount doesn't make total negative
        return max(0, $subtotal - $discountAmount);
    }

    /**
     * Calculates the tax amount for the cart.
     * This is a placeholder; real tax calculation can be complex (e.g., based on location, product type).
     * @param Cart $cart
     * @param float $taxRate (e.g., 0.09 for 9%)
     * @return float
     */
    public function calculateTax(Cart $cart, float $taxRate): float
    {
        $taxableAmount = $this->calculateDiscountedTotal($cart); // Usually tax is on discounted total
        return $taxableAmount * $taxRate;
    }

    /**
     * Calculates the shipping cost for the cart.
     * This is a placeholder; real shipping calculation can be complex (e.g., based on weight, destination, carrier).
     * @param Cart $cart
     * @return float
     */
    public function calculateShippingCost(Cart $cart): float
    {
        // Example: simple flat rate or based on total quantity/weight
        // $totalQuantity = $cart->items->sum('quantity');
        // return $totalQuantity > 0 ? 5.00 : 0.00;
        return 0.0; // Placeholder
    }

    /**
     * Calculates the grand total of the cart including subtotal, discounts, tax, and shipping.
     * @param Cart $cart
     * @param float $discountAmount
     * @param float $taxRate
     * @param float $shippingCost
     * @return float
     */
    public function calculateGrandTotal(Cart $cart, float $discountAmount = 0.0, float $taxRate = 0.0, float $shippingCost = 0.0): float
    {
        $discountedTotal = $this->calculateDiscountedTotal($cart, $discountAmount);
        $tax = $this->calculateTax($cart, $taxRate);
        $shipping = $this->calculateShippingCost($cart); // Assuming this method might fetch its own cost or take it as param

        return $discountedTotal + $tax + $shipping;
    }
}