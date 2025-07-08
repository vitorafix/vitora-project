<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\Dispatcher;

// Contracts
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface; 
use App\Contracts\Repositories\ProductVariantRepositoryInterface;
use App\Services\Contracts\CartItemManagementServiceInterface; // Corrected: Use the interface from Contracts namespace
use App\Services\Managers\StockManager;
use App\Services\Managers\CartValidator;

// Managers
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;

// Custom Exceptions
use App\Exceptions\BaseCartException;
use App\Exceptions\Cart\InsufficientStockException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\CartInvalidArgumentException;
use App\Exceptions\Cart\CartLimitExceededException;
use App\Exceptions\UnauthorizedCartAccessException;
use App\Exceptions\CartOperationException;

class CartItemManagementService implements CartItemManagementServiceInterface
{
    protected CartRepositoryInterface $cartRepository;
    protected ProductRepositoryInterface $productRepository;
    protected ProductVariantRepositoryInterface $productVariantRepository;
    protected StockManager $stockManager;
    protected CartValidator $cartValidator;
    protected CartRateLimiter $rateLimiter;
    protected CartMetricsManager $metricsManager;
    protected Dispatcher $eventDispatcher;
    protected CartCacheManager $cacheManager;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
        ProductVariantRepositoryInterface $productVariantRepository,
        StockManager $stockManager,
        CartValidator $cartValidator,
        CartRateLimiter $rateLimiter,
        CartMetricsManager $metricsManager,
        Dispatcher $eventDispatcher,
        CartCacheManager $cacheManager
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->productVariantRepository = $productVariantRepository;
        $this->stockManager = $stockManager;
        $this->cartValidator = $cartValidator;
        $this->rateLimiter = $rateLimiter;
        $this->metricsManager = $metricsManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Adds a new product to the cart or updates an existing item's quantity.
     *
     * @param \App\Models\Cart $cart
     * @param int $productId
     * @param int $quantity
     * @param int|null $productVariantId
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity, ?int $productVariantId = null): \App\Services\Responses\CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        DB::beginTransaction();
        try {
            // اصلاح: فراخوانی ensureProductExists از CartValidator
            $product = $this->cartValidator->ensureProductExists($productId);
            $productVariant = null;
            $effectivePrice = $product->price;
            $entityForStock = $product; // The entity whose stock we are managing

            if ($productVariantId) {
                $productVariant = $this->productVariantRepository->find($productVariantId);
                if (!$productVariant || $productVariant->product_id !== $product->id) {
                    throw new CartInvalidArgumentException('واریانت محصول معتبر نیست.', 400);
                }
                $effectivePrice += $productVariant->price_adjustment;
                $entityForStock = $productVariant; // Manage stock on the variant
            }

            // اصلاح: فراخوانی validateQuantity به جای validateItemQuantity
            $validatedQuantity = $this->cartValidator->validateQuantity($quantity);

            // Find cart item by product_id AND product_variant_id
            $cartItem = $this->cartRepository->findCartItem($cart->id, $productId, $productVariantId);
            $oldQuantity = 0;
            $isNewItem = false;

            if ($cartItem) {
                $oldQuantity = $cartItem->quantity;
                $newQuantity = $validatedQuantity;
                $quantityChange = $newQuantity - $oldQuantity;

                if ($newQuantity === 0) {
                    $this->cartRepository->deleteCartItem($cartItem);
                    $this->stockManager->releaseStock($entityForStock, $oldQuantity); // Release stock from correct entity
                    $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cart, $cartItem));
                    Log::info('Cart item removed because new quantity is zero.', ['cart_id' => $cart->id, 'product_id' => $productId, 'product_variant_id' => $productVariantId]);
                    DB::commit();
                    $this->cacheManager->clearCache($cart->user, $cart->session_id);
                    $this->metricsManager->recordMetric('addOrUpdateCartItem_duration', microtime(true) - $startTime, ['action' => 'removed_by_zero_quantity']);
                    return \App\Services\Responses\CartOperationResponse::success('محصول از سبد خرید حذف شد.', ['product_id' => $productId, 'product_variant_id' => $productVariantId]);
                }

                if ($quantityChange > 0) {
                    $this->stockManager->validateStock($entityForStock, $quantityChange); // Validate stock from correct entity
                    $this->stockManager->reserveStock($entityForStock, $quantityChange); // Reserve stock from correct entity
                } elseif ($quantityChange < 0) {
                    $this->stockManager->releaseStock($entityForStock, abs($quantityChange)); // Release stock from correct entity
                }

                $this->cartRepository->updateCartItem($cartItem, ['quantity' => $newQuantity]);
                $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cart, $cartItem, $oldQuantity, $newQuantity));
                Log::info('Cart item quantity updated', ['cart_id' => $cart->id, 'product_id' => $productId, 'product_variant_id' => $productVariantId, 'old_quantity' => $oldQuantity, 'new_quantity' => $newQuantity]);

            } else {
                $isNewItem = true;
                // اصلاح: فراخوانی validateCartLimits به جای ensureCartItemLimitNotExceeded
                $this->cartValidator->validateCartLimits($cart, 1);
                $this->stockManager->validateStock($entityForStock, $validatedQuantity); // Validate stock from correct entity
                $this->stockManager->reserveStock($entityForStock, $validatedQuantity); // Reserve stock from correct entity

                $cartItem = $this->cartRepository->createCartItem([
                    'cart_id' => $cart->id,
                    'product_id' => $productId,
                    'product_variant_id' => $productVariantId, // Set variant ID
                    'quantity' => $validatedQuantity,
                    'price' => $effectivePrice, // Use effective price
                ]);
                // اصلاح: ارسال $cartItem به جای $cart به سازنده CartItemAdded
                // تغییر در این خط برای مطابقت با سازنده CartItemAdded
                $this->eventDispatcher->dispatch(new \App\Events\CartItemAdded($cartItem, $cart, $product, $cart->user)); 
                Log::info('New cart item added', ['cart_id' => $cart->id, 'product_id' => $productId, 'product_variant_id' => $productVariantId, 'quantity' => $validatedQuantity]);
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('addOrUpdateCartItem_duration', microtime(true) - $startTime, ['action' => ($isNewItem ? 'added' : 'updated')]);

            return \App\Services\Responses\CartOperationResponse::success(
                $isNewItem ? 'محصول با موفقیت به سبد خرید اضافه شد!' : 'تعداد محصول در سبد خرید به‌روزرسانی شد.',
                [
                    'product_id' => $productId,
                    'product_variant_id' => $productVariantId,
                    'quantity' => $cartItem->quantity,
                    'cart_item_id' => $cartItem->id,
                ]
            );

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error during add/update cart item: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'product_variant_id' => $productVariantId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('addOrUpdateCartItem_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return \App\Services\Responses\CartOperationResponse::fail($e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during add/update cart item: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'product_variant_id' => $productVariantId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('addOrUpdateCartItem_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return \App\Services\Responses\CartOperationResponse::fail('خطای سیستمی در اضافه کردن/به‌روزرسانی محصول به سبد خرید.', 500);
        }
    }

    /**
     * Updates the quantity of an existing cart item.
     *
     * @param \App\Models\CartItem $cartItem
     * @param int $newQuantity
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function updateItemQuantity(
        CartItem $cartItem,
        int $newQuantity,
        ?User $user = null,
        ?string $sessionId = null
    ): \App\Services\Responses\CartOperationResponse {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($user, $sessionId);

        $this->cartValidator->ensureCartOwnership($cartItem->cart, $user, $sessionId);

        DB::beginTransaction();
        try {
            // Load product and variant for stock checks
            $cartItem->loadMissing(['product', 'productVariant']);
            $product = $cartItem->product;
            $productVariant = $cartItem->productVariant;

            if (!$product) {
                throw new ProductNotFoundException("محصول مرتبط با آیتم سبد خرید یافت نشد.");
            }

            $entityForStock = $productVariant ?? $product; // Use variant for stock if it exists, else product
            $stockToCheck = $entityForStock->stock;

            // اصلاح: فراخوانی validateQuantity به جای validateItemQuantity
            $validatedNewQuantity = $this->cartValidator->validateQuantity($newQuantity);
            $oldQuantity = $cartItem->quantity;
            $quantityChange = $validatedNewQuantity - $oldQuantity;

            if ($validatedNewQuantity === 0) {
                $this->cartRepository->deleteCartItem($cartItem);
                $this->stockManager->releaseStock($entityForStock, $oldQuantity); // Release stock from correct entity
                $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cartItem->cart, $cartItem));
                Log::info('Cart item removed as quantity set to zero', ['cart_item_id' => $cartItem->id, 'product_id' => $product->id, 'product_variant_id' => $productVariant->id ?? null]);
                DB::commit();
                $this->cacheManager->clearCache($user, $sessionId);
                $this->metricsManager->recordMetric('updateCartItemQuantity_duration', microtime(true) - $startTime, ['action' => 'removed']);
                return \App\Services\Responses\CartOperationResponse::success('محصول از سبد خرید حذف شد.', ['product_id' => $product->id, 'cart_item_id' => $cartItem->id, 'product_variant_id' => $productVariant->id ?? null]);
            }

            if ($quantityChange > 0) {
                $this->stockManager->validateStock($entityForStock, $quantityChange); // Validate stock from correct entity
                $this->stockManager->reserveStock($entityForStock, $quantityChange); // Reserve stock from correct entity
            } elseif ($quantityChange < 0) {
                $this->stockManager->releaseStock($entityForStock, abs($quantityChange)); // Release stock from correct entity
            }

            $this->cartRepository->updateCartItem($cartItem, ['quantity' => $validatedNewQuantity]);
            $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cartItem->cart, $cartItem, $oldQuantity, $validatedNewQuantity));
            Log::info('Cart item quantity updated', ['cart_item_id' => $cartItem->id, 'product_id' => $product->id, 'product_variant_id' => $productVariant->id ?? null, 'old_quantity' => $oldQuantity, 'new_quantity' => $validatedNewQuantity]);

            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId);
            $this->metricsManager->recordMetric('updateCartItemQuantity_duration', microtime(true) - $startTime, ['action' => 'updated']);
            return \App\Services\Responses\CartOperationResponse::success(
                'تعداد محصول در سبد خرید به‌روزرسانی شد.',
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $productVariant->id ?? null,
                    'quantity' => $validatedNewQuantity,
                    'cart_item_id' => $cartItem->id,
                ]
            );

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error during update cart item quantity: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateCartItemQuantity_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return \App\Services\Responses\CartOperationResponse::fail($e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during update cart item quantity: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateCartItemQuantity_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return \App\Services\Responses\CartOperationResponse::fail('خطای سیستمی در به‌روزرسانی تعداد محصول.', 500);
        }
    }

    /**
     * Removes a cart item from the cart.
     *
     * @param \App\Models\CartItem $cartItem
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function removeItem(
        CartItem $cartItem,
        ?User $user = null,
        ?string $sessionId = null
    ): \App\Services\Responses\CartOperationResponse {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($user, $sessionId);

        $this->cartValidator->ensureCartOwnership($cartItem->cart, $user, $sessionId);

        DB::beginTransaction();
        try {
            // Load product and variant for stock release
            $cartItem->loadMissing(['product', 'productVariant']);
            $product = $cartItem->product;
            $productVariant = $cartItem->productVariant;
            $entityForStock = $productVariant ?? $product; // Use variant for stock if it exists, else product

            if ($entityForStock) { // Ensure there's an entity to release stock from
                $this->stockManager->releaseStock($entityForStock, $cartItem->quantity);
            } else {
                Log::warning('Product or variant for cart item not found during removal. Stock not released.', ['cart_item_id' => $cartItem->id, 'product_id' => $cartItem->product_id, 'product_variant_id' => $cartItem->product_variant_id]);
            }

            $this->cartRepository->deleteCartItem($cartItem);
            $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cartItem->cart, $cartItem));
            Log::info('Cart item removed successfully', ['cart_item_id' => $cartItem->id, 'product_id' => $cartItem->product_id, 'product_variant_id' => $cartItem->product_variant_id]);

            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId);
            $this->metricsManager->recordMetric('removeCartItem_duration', microtime(true) - $startTime);
            return \App\Services\Responses\CartOperationResponse::success('محصول با موفقیت از سبد خرید حذف شد.', ['product_id' => $cartItem->product_id, 'cart_item_id' => $cartItem->id, 'product_variant_id' => $cartItem->product_variant_id]);

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error removing cart item: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('removeCartItem_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e; // Re-throw specific cart exceptions
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error removing cart item: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('removeCartItem_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return \App\Services\Responses\CartOperationResponse::fail('خطا در حذف محصول از سبد خرید.', 500);
        }
    }
}
