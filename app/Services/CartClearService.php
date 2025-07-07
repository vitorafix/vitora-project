<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\CartMetricsManager;
use App\Services\Managers\StockManager; // Added for stock release
use App\Services\Contracts\CartClearServiceInterface;
use Illuminate\Contracts\Events\Dispatcher; // If you have a CartCleared event

class CartClearService implements CartClearServiceInterface
{
    protected CartRepositoryInterface $cartRepository;
    protected CartCacheManager $cacheManager;
    protected CartMetricsManager $metricsManager;
    protected StockManager $stockManager; // Added for stock release
    protected Dispatcher $eventDispatcher;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartCacheManager $cacheManager,
        CartMetricsManager $metricsManager,
        StockManager $stockManager, // Inject StockManager
        Dispatcher $eventDispatcher
    ) {
        $this->cartRepository = $cartRepository;
        $this->cacheManager = $cacheManager;
        $this->metricsManager = $metricsManager;
        $this->stockManager = $stockManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Clear all items from the cart.
     * همه آیتم‌ها را از سبد خرید پاک می‌کند.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function clearCart(Cart $cart): \App\Services\Responses\CartOperationResponse
    {
        $startTime = microtime(true);
        Log::info('Attempting to clear cart.', ['cart_id' => $cart->id, 'user_id' => $cart->user_id, 'session_id' => $cart->session_id]);

        DB::beginTransaction();
        try {
            // Load items with products to release stock
            $cart->load('items.product');
            foreach ($cart->items as $item) {
                if ($item->product) {
                    $this->stockManager->releaseStock($item->product, $item->quantity);
                } else {
                    Log::warning('Product not found for cart item during clear. Stock not released for this item.', [
                        'cart_id' => $cart->id,
                        'cart_item_id' => $item->id,
                        'product_id' => $item->product_id
                    ]);
                }
            }

            $this->cartRepository->clearCartItems($cart); // This method should delete all items for the given cart

            // If you want to keep the cart record but clear items, use this config
            if (!config('cart.keep_cart_on_clear', false)) {
                $this->cartRepository->delete($cart);
                Log::info('Cart record deleted after clearing items.', ['cart_id' => $cart->id]);
            } else {
                // If cart record is kept, reset coupon and discount
                $cart->coupon_id = null;
                $cart->discount_amount = 0;
                $cart->subtotal = 0;
                $cart->total = 0;
                $this->cartRepository->save($cart);
                Log::info('Cart record kept and reset after clearing items.', ['cart_id' => $cart->id]);
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id); // Clear cache for the cleared cart
            $this->metricsManager->recordMetric('clearCart_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);
            $this->eventDispatcher->dispatch(new \App\Events\CartCleared($cart)); // Dispatch event

            Log::info('Cart successfully cleared.', ['cart_id' => $cart->id]);
            return \App\Services\Responses\CartOperationResponse::success('سبد خرید با موفقیت خالی شد.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error clearing cart: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('clearCart_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return \App\Services\Responses\CartOperationResponse::fail('خطا در خالی کردن سبد خرید.', 500);
        }
    }
}

