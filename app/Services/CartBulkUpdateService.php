<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\Dispatcher;

// Contracts
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Services\Contracts\CartBulkUpdateServiceInterface;
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;
use App\Services\Managers\StockManager;
use App\Services\Managers\CartValidator;

// Custom Exceptions
use App\Exceptions\BaseCartException;
use App\Exceptions\Cart\InsufficientStockException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\CartInvalidArgumentException;
use App\Exceptions\Cart\CartLimitExceededException;
use App\Exceptions\UnauthorizedCartAccessException;
use App\Exceptions\CartOperationException;

class CartBulkUpdateService implements CartBulkUpdateServiceInterface
{
    protected CartRepositoryInterface $cartRepository;
    protected ProductRepositoryInterface $productRepository;
    protected StockManager $stockManager;
    protected CartValidator $cartValidator;
    protected CartRateLimiter $rateLimiter;
    protected CartMetricsManager $metricsManager;
    protected Dispatcher $eventDispatcher;
    protected CartCacheManager $cacheManager;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
        StockManager $stockManager,
        CartValidator $cartValidator,
        CartRateLimiter $rateLimiter,
        CartMetricsManager $metricsManager,
        Dispatcher $eventDispatcher,
        CartCacheManager $cacheManager
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->stockManager = $stockManager;
        $this->cartValidator = $cartValidator;
        $this->rateLimiter = $rateLimiter;
        $this->metricsManager = $metricsManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Update multiple items in the cart (e.g., from a form submission).
     * به‌روزرسانی چندین آیتم در سبد خرید (مثلاً از طریق ارسال فرم).
     *
     * @param \App\Models\Cart $cart
     * @param array $updates An array of updates, each containing 'cart_item_id' and 'quantity'.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function updateMultipleItems(Cart $cart, array $updates): \App\Services\Responses\CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        if (count($updates) > config('cart.max_bulk_operations', 100)) {
            throw new CartLimitExceededException('تعداد عملیات به‌روزرسانی گروهی بیش از حد مجاز است.', 400);
        }

        DB::beginTransaction();
        try {
            $updatedCount = 0;
            $removedCount = 0;
            $errors = [];

            // Load all cart items and their products/variants for efficient processing
            $cart->loadMissing(['items.product', 'items.productVariant']);
            $cartItemsMap = $cart->items->keyBy('id');

            foreach ($updates as $update) {
                $cartItemId = $update['cart_item_id'] ?? null;
                $newQuantity = $update['quantity'] ?? null;

                if (is_null($cartItemId) || !is_numeric($newQuantity)) {
                    $errors[] = ['message' => 'فرمت به‌روزرسانی نامعتبر است.', 'update' => $update];
                    continue;
                }

                $cartItem = $cartItemsMap->get($cartItemId);

                if (!$cartItem) {
                    $errors[] = ['message' => 'آیتم سبد خرید یافت نشد.', 'cart_item_id' => $cartItemId];
                    continue;
                }

                // Ensure ownership (already handled by CartController if using Route Model Binding, but good to double check)
                // $this->cartValidator->ensureCartOwnership($cartItem->cart, $cart->user, $cart->session_id);

                try {
                    $product = $cartItem->product;
                    $productVariant = $cartItem->productVariant;
                    $entityForStock = $productVariant ?? $product;

                    if (!$product) {
                        Log::warning('Product not found for cart item during bulk update. Skipping item.', ['cart_item_id' => $cartItem->id]);
                        $errors[] = ['message' => 'محصول مرتبط با آیتم یافت نشد.', 'cart_item_id' => $cartItem->id];
                        continue;
                    }

                    $validatedNewQuantity = $this->cartValidator->validateItemQuantity($newQuantity);
                    $oldQuantity = $cartItem->quantity;
                    $quantityChange = $validatedNewQuantity - $oldQuantity;

                    if ($validatedNewQuantity === 0) {
                        $this->cartRepository->deleteCartItem($cartItem);
                        $this->stockManager->releaseStock($entityForStock, $oldQuantity);
                        $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cart, $cartItem));
                        $removedCount++;
                        Log::info('Cart item removed during bulk update as quantity set to zero.', ['cart_item_id' => $cartItem->id]);
                    } elseif ($quantityChange !== 0) {
                        if ($quantityChange > 0) {
                            $this->stockManager->validateStock($entityForStock, $quantityChange);
                            $this->stockManager->reserveStock($entityForStock, $quantityChange);
                        } else {
                            $this->stockManager->releaseStock($entityForStock, abs($quantityChange));
                        }
                        $this->cartRepository->updateCartItem($cartItem, ['quantity' => $validatedNewQuantity]);
                        $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cart, $cartItem, $oldQuantity, $validatedNewQuantity));
                        $updatedCount++;
                        Log::info('Cart item quantity updated during bulk update.', ['cart_item_id' => $cartItem->id, 'old_quantity' => $oldQuantity, 'new_quantity' => $validatedNewQuantity]);
                    }
                    // If quantityChange is 0, no action needed for this item
                } catch (BaseCartException $e) {
                    $errors[] = ['message' => $e->getMessage(), 'cart_item_id' => $cartItem->id];
                    Log::error('Error updating cart item during bulk update: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
                } catch (\Throwable $e) {
                    $errors[] = ['message' => 'خطای ناشناخته در به‌روزرسانی آیتم.', 'cart_item_id' => $cartItem->id];
                    Log::error('Unexpected error during bulk update: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
                }
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('updateMultipleItems_duration', microtime(true) - $startTime, ['updated_count' => $updatedCount, 'removed_count' => $removedCount, 'error_count' => count($errors)]);

            if (empty($errors)) {
                return \App\Services\Responses\CartOperationResponse::success("عملیات به‌روزرسانی گروهی با موفقیت انجام شد. {$updatedCount} آیتم به‌روزرسانی و {$removedCount} آیتم حذف شد.");
            } else {
                return \App\Services\Responses\CartOperationResponse::fail("عملیات به‌روزرسانی گروهی با مشکلاتی مواجه شد. {$updatedCount} آیتم به‌روزرسانی و {$removedCount} آیتم حذف شد. جزئیات خطا: " . json_encode($errors), 400);
            }

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Bulk update operation error: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateMultipleItems_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return \App\Services\Responses\CartOperationResponse::fail($e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during bulk update operation: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateMultipleItems_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return \App\Services\Responses\CartOperationResponse::fail('خطای سیستمی در به‌روزرسانی گروهی سبد خرید.', 500);
        }
    }
}

