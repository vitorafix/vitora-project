<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\CartMetricsManager;
use App\Services\Contracts\CartCleanupServiceInterface;
use Illuminate\Contracts\Events\Dispatcher;
use App\Services\Managers\StockManager;

class CartCleanupService implements CartCleanupServiceInterface
{
    protected CartRepositoryInterface $cartRepository;
    protected CartCacheManager $cacheManager;
    protected CartMetricsManager $metricsManager;
    protected Dispatcher $eventDispatcher;
    protected StockManager $stockManager;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartCacheManager $cacheManager,
        CartMetricsManager $metricsManager,
        Dispatcher $eventDispatcher,
        StockManager $stockManager
    ) {
        $this->cartRepository = $cartRepository;
        $this->cacheManager = $cacheManager;
        $this->metricsManager = $metricsManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->stockManager = $stockManager;
    }

    /**
     * Clean up expired guest carts.
     * پاکسازی سبدهای خرید مهمان منقضی شده.
     *
     * @param int|null $daysCutoff The number of days after which a guest cart is considered expired.
     * @return int The number of carts cleaned up.
     */
    public function cleanupExpiredCarts(?int $daysCutoff = null): int
    {
        $startTime = microtime(true);
        $cutoffDate = Carbon::now()->subDays($daysCutoff ?? config('cart.cleanup_days', 30));

        Log::info("Starting cart cleanup for carts older than {$cutoffDate->toDateTimeString()}.");

        $expiredCarts = $this->cartRepository->findExpiredGuestCarts($cutoffDate);
        $cleanedCount = 0;

        foreach ($expiredCarts as $cart) {
            DB::beginTransaction();
            try {
                $cart->load('items.product'); // Load items with products to release stock
                foreach ($cart->items as $item) {
                    if ($item->product) {
                        $this->stockManager->releaseStock($item->product, $item->quantity);
                    } else {
                        Log::warning('Product not found for cart item during cleanup. Stock not released for this item.', [
                            'cart_id' => $cart->id,
                            'cart_item_id' => $item->id,
                            'product_id' => $item->product_id
                        ]);
                    }
                }

                $this->cartRepository->delete($cart);
                $this->cacheManager->clearCache(null, $cart->session_id);

                $cleanedCount++;
                DB::commit();
                Log::info('Expired guest cart cleaned up.', ['cart_id' => $cart->id, 'session_id' => $cart->session_id]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error cleaning up expired cart: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
                $this->metricsManager->recordMetric('cleanupExpiredCarts_exception', microtime(true) - $startTime, ['error_type' => 'cleanup_failed']);
            }
        }

        $this->metricsManager->recordMetric('cleanupExpiredCarts_duration', microtime(true) - $startTime, ['cleaned_count' => $cleanedCount]);
        Log::info("Finished cart cleanup. Cleaned {$cleanedCount} expired guest carts.");

        return $cleanedCount;
    }
}

