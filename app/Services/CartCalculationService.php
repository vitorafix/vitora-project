<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Services\CouponService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // New import for caching
use Illuminate\Support\Facades\Event; // New import for event dispatching
use App\Services\Responses\CartOperationResponse;
use App\Exceptions\EmptyCartException; // New import for custom exception
use App\DTOs\CartTotalsDTO; // New import for DTO

class CartCalculationService
{
    protected CartRepositoryInterface $cartRepository;
    protected ProductRepositoryInterface $productRepository;
    protected ?CouponService $couponService;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
        ?CouponService $couponService = null
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->couponService = $couponService;
    }

    /**
     * Refresh cart item prices based on current product prices in the database.
     * به‌روزرسانی قیمت آیتم‌های سبد خرید بر اساس قیمت‌های فعلی محصول در پایگاه داده.
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function refreshCartItemPrices(Cart $cart): CartOperationResponse
    {
        $startTime = microtime(true);
        $cart->loadMissing('items.product', 'items.productVariant');
        $updates = [];
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($cart->items as $item) {
                // Fetch the product with lock for update to ensure latest price
                // محصول را با قفل برای به‌روزرسانی دریافت کنید تا آخرین قیمت اطمینان حاصل شود.
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                if (!$product) {
                    Log::warning('Skipping refresh for cart item with missing product (locked).', ['cart_item_id' => $item->id]);
                    continue;
                }

                $currentPrice = (float) $item->price;
                $expectedPrice = (float) $product->price;

                if ($item->productVariant) {
                    // Fetch variant with lock for update if its price_adjustment is dynamic
                    // واریانت را با قفل برای به‌روزرسانی دریافت کنید اگر price_adjustment آن پویا است.
                    $productVariant = ProductVariant::where('id', $item->product_variant_id)->lockForUpdate()->first();
                    if ($productVariant) {
                        $expectedPrice += (float) $productVariant->price_adjustment;
                    } else {
                        Log::warning('Product variant not found for price refresh.', ['product_variant_id' => $item->product_variant_id]);
                    }
                }

                if ($currentPrice !== $expectedPrice) {
                    $updates[] = [
                        'id' => $item->id,
                        'price' => $expectedPrice
                    ];
                    $updatedCount++;
                }
            }

            if (!empty($updates)) {
                // Use bulk update for cart items
                // از به‌روزرسانی انبوه برای آیتم‌های سبد خرید استفاده کنید.
                $this->cartRepository->bulkUpdateCartItems($updates);
            }

            DB::commit();
            // Dispatch event if prices were updated
            // در صورت به‌روزرسانی قیمت‌ها، رویداد را ارسال کنید.
            if ($updatedCount > 0) {
                Event::dispatch(new \App\Events\CartPricesUpdated($cart, $updatedCount));
            }

            return CartOperationResponse::success("قیمت {$updatedCount} آیتم سبد خرید به‌روزرسانی شد.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error refreshing cart item prices: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            return CartOperationResponse::error('خطا در به‌روزرسانی قیمت آیتم‌های سبد خرید.', 500);
        }
    }

    /**
     * Calculates the subtotal of the cart (sum of item prices * quantities).
     * مجموع فرعی سبد خرید (مجموع قیمت آیتم‌ها * تعداد) را محاسبه می‌کند.
     * @param Cart $cart
     * @return float
     */
    public function calculateSubtotal(Cart $cart): float
    {
        $subtotal = 0.0;
        $cart->loadMissing('items');
        Log::debug('CartCalculationService::calculateSubtotal - Starting calculation for cart: ' . $cart->id);
        foreach ($cart->items as $item) {
            $itemSubtotal = (float) $item->price * (int) $item->quantity;
            $subtotal += $itemSubtotal;
            Log::debug('CartCalculationService::calculateSubtotal - Item: ' . $item->id . ', Product: ' . ($item->product->name ?? 'N/A') . ', Price: ' . $item->price . ', Quantity: ' . $item->quantity . ', Item Subtotal: ' . $itemSubtotal);
        }
        Log::debug('CartCalculationService::calculateSubtotal - Final Subtotal: ' . $subtotal);
        return (float) $subtotal;
    }

    /**
     * Calculates the total price after applying discounts.
     * این متد قیمت کل را پس از اعمال تخفیف‌ها محاسبه می‌کند.
     * @param Cart $cart
     * @param float $discountAmount
     * @return float
     */
    public function calculateDiscountedTotal(Cart $cart, float $discountAmount = 0.0): float
    {
        $subtotal = $this->calculateSubtotal($cart);
        return (float) max(0, $subtotal - $discountAmount);
    }

    /**
     * Calculates the tax amount for the cart.
     * مقدار مالیات سبد خرید را محاسبه می‌کند.
     * @param Cart $cart
     * @return float
     */
    public function calculateTax(Cart $cart): float
    {
        // Use config for tax rate
        // از پیکربندی برای نرخ مالیات استفاده کنید.
        $taxRate = (float) config('cart.tax_rate', 0.09);
        $taxableAmount = $this->calculateDiscountedTotal($cart, (float) $cart->discount_amount ?? 0.0);
        return (float) ($taxableAmount * $taxRate);
    }

    /**
     * Calculates the shipping cost for the cart.
     * هزینه حمل و نقل سبد خرید را محاسبه می‌کند.
     * @param Cart $cart
     * @return float
     */
    public function calculateShippingCost(Cart $cart): float
    {
        // Use config for shipping thresholds and costs
        // از پیکربندی برای آستانه‌ها و هزینه‌های حمل و نقل استفاده کنید.
        $freeShippingThreshold = (float) config('cart.free_shipping_threshold', 1000000.0);
        $shippingCost = (float) config('cart.shipping_cost', 30000.0);

        $subtotal = $this->calculateSubtotal($cart);
        return (float) ($subtotal > $freeShippingThreshold ? 0.0 : $shippingCost);
    }

    /**
     * Calculates the discount amount applied by a coupon.
     * مقدار تخفیف اعمال شده توسط کوپن را محاسبه می‌کند.
     * @param Cart $cart
     * @return float
     */
    private function calculateCouponDiscount(Cart $cart): float
    {
        if (!$cart->coupon_id || !$this->couponService || !$cart->coupon) {
            return 0.0;
        }

        try {
            // Ensure coupon relationship is loaded for coupon->code
            // اطمینان حاصل شود که رابطه کوپن برای coupon->code بارگذاری شده است.
            $cart->loadMissing('coupon');
            $couponResult = $this->couponService->applyCoupon($cart, $cart->coupon->code);

            if ($couponResult->isSuccessful()) {
                $discount = (float) ($couponResult->getData()['discount_amount'] ?? 0.0);
                Log::info('Coupon applied successfully in CartCalculationService', [
                    'coupon_id' => $cart->coupon_id,
                    'discount' => $discount
                ]);
                return $discount;
            }

            Log::warning('Failed to apply coupon during cart total calculation in CartCalculationService', [
                'coupon_id' => $cart->coupon_id,
                'error' => $couponResult->getMessage()
            ]);

        } catch (\Throwable $e) {
            Log::error('Error applying coupon in calculateCouponDiscount (CartCalculationService): ' . $e->getMessage(), [
                'coupon_id' => $cart->coupon_id,
                'exception' => $e->getTraceAsString()
            ]);
        }

        return 0.0;
    }

    /**
     * Performs the actual calculations for cart totals.
     * محاسبات واقعی برای مجموع سبد خرید را انجام می‌دهد.
     * @param Cart $cart
     * @return CartTotalsDTO
     */
    private function performCalculations(Cart $cart): CartTotalsDTO
    {
        Log::debug('CartCalculationService::performCalculations - Starting calculations for cart: ' . $cart->id);
        $subtotal = $this->calculateSubtotal($cart);
        Log::debug('CartCalculationService::performCalculations - Calculated Subtotal: ' . $subtotal);

        $discount = (float) $cart->discount_amount ?? $this->calculateCouponDiscount($cart);
        Log::debug('CartCalculationService::performCalculations - Calculated Discount: ' . $discount);

        $tax = $this->calculateTax($cart);
        Log::debug('CartCalculationService::performCalculations - Calculated Tax: ' . $tax);

        $shipping = $this->calculateShippingCost($cart);
        Log::debug('CartCalculationService::performCalculations - Calculated Shipping: ' . $shipping);

        $discountedSubtotal = $this->calculateDiscountedTotal($cart, $discount);
        Log::debug('CartCalculationService::performCalculations - Discounted Subtotal (after discount): ' . $discountedSubtotal);

        $total = $discountedSubtotal + $tax + $shipping;
        Log::debug('CartCalculationService::performCalculations - Raw Total: ' . $total);

        return new CartTotalsDTO(
            subtotal: (float) round($subtotal, 2),
            discount: (float) round($discount, 2),
            shipping: (float) round($shipping, 2),
            tax: (float) round($tax, 2),
            total: (float) round($total, 2)
        );
    }

    /**
     * Calculates the grand total of the cart including subtotal, discounts, tax, and shipping.
     * مجموع کل سبد خرید شامل مجموع فرعی، تخفیف‌ها، مالیات و هزینه حمل و نقل را محاسبه می‌کند.
     * @param Cart $cart
     * @return CartTotalsDTO
     * @throws EmptyCartException If the cart is empty.
     */
    public function calculateCartTotals(Cart $cart): CartTotalsDTO
    {
        // Validate if cart is empty
        // اعتبارسنجی برای خالی بودن سبد خرید
        if ($cart->items->isEmpty()) {
            throw new EmptyCartException('سبد خرید خالی است.');
        }

        $cacheKey = "cart_totals_{$cart->id}_" . ($cart->updated_at ? $cart->updated_at->timestamp : 'no_timestamp');
        $cacheTtl = (int) config('cart.totals_cache_ttl', 300); // Cache for 5 minutes by default

        return Cache::remember($cacheKey, $cacheTtl, function() use ($cart) {
            return $this->performCalculations($cart);
        });
    }
}
