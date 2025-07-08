<?php

namespace App\Services;

use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant; // New: Import ProductVariant model
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Events\Dispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache; // Import Cache facade

// Contracts
use App\Services\Contracts\CartServiceInterface;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\ProductServiceInterface;
use App\Services\Contracts\CartCleanupServiceInterface;
use App\Services\Contracts\CartItemManagementServiceInterface;
use App\Services\Contracts\CartBulkUpdateServiceInterface;
use App\Services\Contracts\CartClearServiceInterface;
use App\Contracts\Services\CouponService; // New: Import CouponService contract

// Managers
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;
use App\Services\Managers\StockManager; // Ensure StockManager is imported if used directly for stock checks
use App\Services\Managers\CartValidator; // Ensure CartValidator is imported if used directly for validation

// Responses
use App\Services\Responses\CartOperationResponse;
use App\Services\Responses\CartContentsResponse;

// Custom Exceptions
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\Cart\InsufficientStockException;
use App\Exceptions\UnauthorizedCartAccessException;
use App\Exceptions\CartOperationException;
use App\Exceptions\CartInvalidArgumentException;
use App\Exceptions\Cart\CartLimitExceededException;
use App\Exceptions\BaseCartException;

// Events (You should create these classes in App\Events/)
// class CartItemAdded { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public Product $product; public int $quantity; public ?ProductVariant $variant; public function __construct(Cart $cart, Product $product, int $quantity, ?ProductVariant $variant = null) { $this->cart = $cart; $this->product = $product; $this->quantity = $quantity; $this->variant = $variant; } }
// class CartItemUpdated { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public CartItem $cartItem; public int $oldQuantity; public int $newQuantity; public function __construct(Cart $cart, CartItem $cartItem, int $oldQuantity, int $newQuantity) { $this->cart = $cart; $this->cartItem = $cartItem; $this->oldQuantity = $oldQuantity; $this->newQuantity = $newQuantity; } }
// class CartItemRemoved { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public CartItem $cartItem; public int $removedQuantity; public ?User $user; public function __construct(Cart $cart, CartItem $cartItem, int $removedQuantity, ?User $user = null) { $this->cart = $cart; $this->cartItem = $cartItem; $this->removedQuantity = $removedQuantity; $this->user = $user; } }
// class CartCleared { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public function __construct(Cart $cart) { $this->cart = $cart; } }
// class CartMerged { use \Illuminate\Foundation\Events\Dispatchable; public Cart $fromCart; public Cart $toCart; public User $user; public function __construct(Cart $fromCart, Cart $toCart, User $user) { $this->fromCart = $fromCart; $this->toCart = $toCart; $this->user = $user; } }


class CartService implements CartServiceInterface
{
    protected CartRepositoryInterface $cartRepository;
    protected ProductRepositoryInterface $productRepository;
    protected ProductServiceInterface $productService;
    protected CartCacheManager $cacheManager;
    protected StockManager $stockManager; // Renamed from stockService for clarity and consistency
    protected CartValidator $cartValidator; // Renamed from cartValidationService for clarity and consistency
    protected CartRateLimiter $rateLimiter;
    protected CartMetricsManager $metricsManager;
    protected Dispatcher $eventDispatcher;
    protected CartMergeService $cartMergeService;
    protected CartCleanupServiceInterface $cartCleanupService;
    protected CartItemManagementServiceInterface $cartItemManagementService;
    protected CartBulkUpdateServiceInterface $cartBulkUpdateService;
    protected CartClearServiceInterface $cartClearService;
    protected CouponService $couponService; // New: Inject CouponService
    public function __construct(
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
        ProductServiceInterface $productService,
        CartCacheManager $cacheManager,
        StockManager $stockManager, // Renamed parameter
        CartValidator $cartValidator, // Renamed parameter
        CartRateLimiter $rateLimiter,
        CartMetricsManager $metricsManager,
        Dispatcher $eventDispatcher,
        CartMergeService $cartMergeService,
        CartCleanupServiceInterface $cartCleanupService,
        CartItemManagementServiceInterface $cartItemManagementService,
        CartBulkUpdateServiceInterface $cartBulkUpdateService,
        CartClearServiceInterface $cartClearService,
        CouponService $couponService // New: Inject CouponService
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->productService = $productService;
        $this->cacheManager = $cacheManager;
        $this->stockManager = $stockManager; // Assign renamed parameter
        $this->cartValidator = $cartValidator; // Assign renamed parameter
        $this->rateLimiter = $rateLimiter;
        $this->metricsManager = $metricsManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->cartMergeService = $cartMergeService;
        $this->cartCleanupService = $cartCleanupService;
        $this->cartItemManagementService = $cartItemManagementService;
        $this->cartBulkUpdateService = $cartBulkUpdateService;
        $this->cartClearService = $cartClearService;
        $this->couponService = $couponService; // New: Assign CouponService
    }

    private function getConfig(string $key, $default = null): mixed
    {
        return config("cart.{$key}", $default);
    }

    public function getOrCreateCart(?User $user = null, ?string $sessionId = null): Cart
    {
        $startTime = microtime(true);
        // Using validation service to check for user or session ID.
        // استفاده از سرویس اعتبارسنجی برای بررسی وجود کاربر یا شناسه جلسه.
        $this->cartValidator->ensureValidCartIdentifier($user, $sessionId);

        $cacheKey = $this->cacheManager->getCacheKey($user, $sessionId);

        // Retrieve cart ID from cache
        // دریافت شناسه سبد خرید از کش
        $cartId = Cache::get($cacheKey);
        $cart = null;

        if ($cartId) {
            // If cart ID found in cache, retrieve the cart with items eager-loaded from DB
            // اگر شناسه سبد خرید در کش یافت شد، سبد خرید را با آیتم‌های آن از دیتابیس بارگذاری کن
            $cart = $this->cartRepository->findCartWithItems($cartId); // Assuming findCartWithItems loads relations
            if (!$cart) {
                // If cart not found in DB despite having an ID in cache (e.g., deleted), clear cache
                // اگر سبد خرید در دیتابیس یافت نشد (مثلاً حذف شده)، کش را پاک کن
                Cache::forget($cacheKey);
                Log::warning('Cart ID found in cache but cart not found in DB, clearing cache.', ['cart_id' => $cartId, 'cache_key' => $cacheKey]);
            }
        }

        if (!$cart) {
            // If cart not found in cache or DB, try to find/create from DB
            // اگر سبد خرید در کش یا دیتابیس یافت نشد، تلاش کن از دیتابیس پیدا یا ایجاد کنی
            if ($user) {
                Log::info('Fetching or creating cart for user', ['user_id' => $user->id]);
                $cart = $this->cartRepository->findByUserId($user->id);
                if (!$cart) {
                    $cart = $this->cartRepository->create(['user_id' => $user->id]);
                }
            } elseif ($sessionId) {
                Log::info('Fetching or creating cart for guest session', ['session_id' => $sessionId]);
                $cart = $this->cartRepository->findBySessionId($sessionId);
                if (!$cart) {
                    $cart = $this->cartRepository->create(['session_id' => $sessionId]);
                }
            }

            // If a new cart is created or found, store its ID in cache
            // اگر سبد خرید جدیدی ایجاد یا یافت شد، شناسه آن را در کش ذخیره کن
            if ($cart) {
                Cache::put($cacheKey, $cart->id, now()->addMinutes($this->getConfig('cache_ttl', 60))); // Cache TTL from config
            }
        }

        // Ensure items are loaded for the returned cart object
        // اطمینان از بارگذاری آیتم‌ها برای آبجکت سبد خرید بازگردانده شده
        if ($cart) {
            $cart->loadMissing(['items.product', 'items.productVariant']);
        }


        $this->metricsManager->recordMetric('getOrCreateCart_duration', microtime(true) - $startTime, ['user_id' => $user?->id, 'session_id' => $sessionId]);
        return $cart;
    }

    // Delegate mergeGuestCart to CartMergeService
    // واگذاری mergeGuestCart به CartMergeService
    public function mergeGuestCart(User $user, string $guestSessionId): void
    {
        $this->cartMergeService->mergeGuestCart($user, $guestSessionId);
    }

    // Delegate assignGuestCartToNewUser to CartMergeService
    // واگذاری assignGuestCartToNewUser به CartMergeService
    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void
    {
        $this->cartMergeService->assignGuestCartToNewUser($guestSessionId, $newUser);
    }

    /**
     * Add product to cart or update quantity.
     *
     * @param Cart $cart
     * @param int $productId
     * @param int $quantity
     * @param int|null $productVariantId // New: Add productVariantId
     * @return CartOperationResponse
     */
    public function addOrUpdateCartItem(Cart $cart, int $productId, int $quantity, ?int $productVariantId = null): CartOperationResponse
    {
        // Delegate to the new CartItemManagementService
        // واگذاری به سرویس جدید CartItemManagementService
        $response = $this->cartItemManagementService->addOrUpdateItem($cart, $productId, $quantity, $productVariantId);
        // Clear cache after any cart modification
        // پاک کردن کش پس از هرگونه تغییر در سبد خرید
        $this->cacheManager->clearCache($cart->user, $cart->session_id);
        return $response;
    }

    public function updateCartItemQuantity(
        CartItem $cartItem,
        int $newQuantity,
        ?User $user = null,
        ?string $sessionId = null
    ): CartOperationResponse {
        // Delegate to the new CartItemManagementService
        // واگذاری به سرویس جدید CartItemManagementService
        $response = $this->cartItemManagementService->updateItemQuantity($cartItem, $newQuantity, $user, $sessionId);
        // Clear cache after any cart modification
        // پاک کردن کش پس از هرگونه تغییر در سبد خرید
        $this->cacheManager->clearCache($user, $sessionId);
        return $response;
    }

    public function removeCartItem(
        CartItem $cartItem,
        ?User $user = null,
        ?string $sessionId = null
    ): CartOperationResponse {
        // Delegate to the new CartItemManagementService
        // واگذاری به سرویس جدید CartItemManagementService
        $response = $this->cartItemManagementService->removeItem($cartItem, $user, $sessionId);
        // Clear cache after any cart modification
        // پاک کردن کش پس از هرگونه تغییر در سبد خرید
        $this->cacheManager->clearCache($user, $sessionId);
        return $response;
    }

    public function clearCart(Cart $cart): CartOperationResponse
    {
        // Delegate to the new CartClearService
        // واگذاری به سرویس جدید CartClearService
        $response = $this->cartClearService->clearCart($cart);
        // Clear cache after any cart modification
        // پاک کردن کش پس از هرگونه تغییر در سبد خرید
        $this->cacheManager->clearCache($cart->user, $cart->session_id);
        return $response;
    }

    public function getCartContents(Cart $cart): CartContentsResponse
    {
        $startTime = microtime(true);
        // Load product and productVariant if exists
        // بارگذاری محصول و واریانت محصول در صورت وجود
        $cart->loadMissing(['items.product', 'items.productVariant']);

        // dd($cart->toArray()); // این خط برای عیب‌یابی بود و اکنون حذف شده است.

        $itemsData = [];
        $totalQuantity = 0;
        $totalPrice = 0.0;

        foreach ($cart->items as $item) {
            if (!$item->product) {
                Log::warning('Product associated with cart item not found.', ['cart_item_id' => $item->id, 'product_id' => $item->product_id]);
                continue; // Skip this item if product is missing
            }

            $productPrice = $item->product->price;
            $variantName = null;
            $variantValue = null;

            if ($item->productVariant) {
                $productPrice += $item->productVariant->price_adjustment;
                $variantName = $item->productVariant->name;
                $variantValue = $item->productVariant->value; // Fixed: Changed from $item->product->value
            }

            $subtotal = $item->quantity * $productPrice;
            
            // Convert Product object to array and add image_url accessor.
            // تبدیل آبجکت Product به آرایه و افزودن Accessor image_url.
            $productArray = $item->product->append('image_url')->toArray();

            $itemsData[] = [
                'cart_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id, // New: Include variant ID
                'product_name' => $item->product->title,
                'product_price' => $productPrice, // Use adjusted price for display
                'variant_name' => $variantName, // New: Include variant name
                'variant_value' => $variantValue, // New: Include variant value
                'quantity' => $item->quantity,
                'subtotal' => $subtotal,
                'slug' => $item->product->slug,
                'stock' => $item->product->stock, // This should reflect product stock or variant stock
                'product' => $productArray,
            ];
            $totalQuantity += $item->quantity;
            $totalPrice += $subtotal;
        }

        $this->metricsManager->recordMetric('getCartContents_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);
        return new CartContentsResponse($itemsData, $totalQuantity, $totalPrice);
    }

    public function updateMultipleItems(Cart $cart, array $updates): CartOperationResponse
    {
        // Delegate to the new CartBulkUpdateService
        // واگذاری به سرویس جدید CartBulkUpdateService
        $response = $this->cartBulkUpdateService->updateMultipleItems($cart, $updates);
        // Clear cache after any cart modification
        // پاک کردن کش پس از هرگونه تغییر در سبد خرید
        $this->cacheManager->clearCache($cart->user, $cart->session_id);
        return $response;
    }

    public function cleanupExpiredCarts(?int $daysCutoff = null): int
    {
        // Delegate cleanup to the new CartCleanupService
        // واگذاری وظیفه پاکسازی به سرویس جدید CartCleanupService
        return $this->cartCleanupService->cleanupExpiredCarts($daysCutoff);
    }

    public function userOwnsCartItem(CartItem $cartItem, ?User $user, ?string $sessionId): bool
    {
        // This method now uses the validation service.
        // این متد اکنون از سرویس اعتبارسنجی استفاده می‌کند.
        return $this->cartValidator->ensureCartOwnership($cartItem->cart, $user, $sessionId);
    }

    public function getCartById(int $cartId, ?User $user = null, ?string $sessionId = null): ?Cart
    {
        // This method should also load relations if needed directly
        // این متد نیز در صورت نیاز باید روابط را مستقیماً بارگذاری کند
        $cart = $this->cartRepository->findCartWithItems($cartId); // Use the method that loads relations
        if (!$cart) {
            return null;
        }

        // Validate cart ownership.
        // اعتبارسنجی مالکیت سبد خرید.
        $this->cartValidator->ensureCartOwnership($cart, $user, $sessionId);

        return $cart;
    }

    public function calculateCartTotals(Cart $cart): array
    {
        $startTime = microtime(true);
        $cart->loadMissing(['items.product', 'items.productVariant']); // Load productVariant

        $subtotal = 0.0;
        foreach ($cart->items as $item) {
            if ($item->product) {
                $itemPrice = $item->product->price;
                if ($item->productVariant) {
                    $itemPrice += $item->productVariant->price_adjustment;
                }
                $subtotal += $item->quantity * $itemPrice;
            }
        }

        $discountAmount = 0.0;
        // Apply coupon discount if a coupon is associated with the cart
        if ($cart->coupon_id && $cart->coupon) { // Assuming a 'coupon' relationship on Cart model
            $discountAmount = $this->couponService->calculateDiscount($cart, $cart->coupon);
        } else if ($cart->discount_amount > 0) {
            // If discount_amount is already set (e.g., from a merged cart), use it.
            // This prevents recalculating if a coupon was applied and then the cart was loaded fresh.
            $discountAmount = $cart->discount_amount;
        }

        // Placeholder for shipping, taxes
        // مکان‌نگهدار برای هزینه حمل و نقل، مالیات
        $shippingCost = 0.0;
        $taxAmount = 0.0;
        
        $total = $subtotal + $shippingCost + $taxAmount - $discountAmount;

        // Ensure total doesn't go below zero
        $total = max(0, $total);

        $this->metricsManager->recordMetric('calculateCartTotals_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);

        return [
            'subtotal' => $subtotal,
            'shipping' => $shippingCost,
            'tax' => $taxAmount,
            'discount' => $discountAmount,
            'total' => $total,
        ];
    }

    public function validateCartItems(Cart $cart): array
    {
        $startTime = microtime(true);
        $cart->loadMissing(['items.product', 'items.productVariant']); // Load productVariant
        $issues = [];

        foreach ($cart->items as $item) {
            if (!$item->product) {
                $issues[] = [
                    'type' => 'product_not_found',
                    'cart_item_id' => $item->id,
                    'message' => 'محصول مرتبط با این آیتم سبد خرید یافت نشد و حذف خواهد شد.',
                ];
                continue;
            }

            // Determine stock to check: product stock or variant stock
            $stockToCheck = $item->product->stock;
            $entityName = $item->product->title;

            if ($item->productVariant) {
                $stockToCheck = $item->productVariant->stock;
                $entityName .= ' (' . $item->productVariant->name . ': ' . $item->productVariant->value . ')';
            }

            // Check stock availability
            // بررسی موجودی
            try {
                // Using validation service to check stock.
                // استفاده از سرویس اعتبارسنجی برای بررسی موجودی.
                $this->cartValidator->ensureProductHasSufficientStock($item->product, $item->quantity, $stockToCheck, $entityName);
            } catch (InsufficientStockException $e) {
                $issues[] = [
                    'type' => 'insufficient_stock',
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product->id,
                    'product_variant_id' => $item->product_variant_id, // New: Include variant ID
                    'product_name' => $entityName, // Use entityName for clearer message
                    'requested_quantity' => $item->quantity,
                    'available_stock' => $stockToCheck,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $this->metricsManager->recordMetric('validateCartItems_duration', microtime(true) - $startTime, ['cart_id' => $cart->id, 'issues_count' => count($issues)]);
        return $issues;
    }

    /**
     * Apply a coupon to the cart.
     *
     * @param Cart $cart
     * @param string $couponCode
     * @return CartOperationResponse
     */
    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse
    {
        try {
            $success = $this->couponService->applyCoupon($cart, $couponCode);
            if ($success) {
                // Refresh cart to get updated discount_amount and total
                $cart->refresh();
                $this->cacheManager->clearCache($cart->user, $cart->session_id); // Clear cache after update
                return CartOperationResponse::success('کد تخفیف با موفقیت اعمال شد.', ['cart' => $cart->toArray()]);
            } else {
                return CartOperationResponse::fail('کد تخفیف نامعتبر یا منقضی شده است.', 400);
            }
        } catch (\Throwable $e) {
            Log::error('Error applying coupon in CartService: ' . $e->getMessage(), ['cart_id' => $cart->id, 'coupon_code' => $couponCode, 'exception' => $e->getTraceAsString()]);
            return CartOperationResponse::fail('خطا در اعمال کد تخفیف.', 500);
        }
    }

    /**
     * Remove a coupon from the cart.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function removeCoupon(Cart $cart): CartOperationResponse
    {
        try {
            $success = $this->couponService->removeCoupon($cart);
            if ($success) {
                // Refresh cart to get updated discount_amount and total
                $cart->refresh();
                $this->cacheManager->clearCache($cart->user, $cart->session_id); // Clear cache after update
                return CartOperationResponse::success('کد تخفیف با موفقیت حذف شد.', ['cart' => $cart->toArray()]);
            } else {
                return CartOperationResponse::fail('کد تخفیفی برای حذف وجود ندارد.', 400);
            }
        } catch (\Throwable $e) {
            Log::error('Error removing coupon in CartService: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            return CartOperationResponse::fail('خطا در حذف کد تخفیف.', 500);
        }
    }

    public function getCartItemCount(Cart $cart): int
    {
        return $cart->items->sum('quantity');
    }

    public function transferCartOwnership(Cart $cart, User $newOwner): bool
    {
        $startTime = microtime(true);
        DB::beginTransaction();
        try {
            $cart->user_id = $newOwner->id;
            $cart->session_id = null;
            $this->cartRepository->save($cart);
            $this->cacheManager->clearCache($newOwner);
            $this->cacheManager->clearCache(null, $cart->session_id);
            DB::commit();
            $this->metricsManager->recordMetric('transferCartOwnership_duration', microtime(true) - $startTime, ['cart_id' => $cart->id, 'new_owner_id' => $newOwner->id]);
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error transferring cart ownership: ' . $e->getMessage(), ['cart_id' => $cart->id, 'new_owner_id' => $newOwner->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('transferCartOwnership_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در انتقال مالکیت سبد خرید.', 0, $e);
        }
    }

    public function isCartEmpty(Cart $cart): bool
    {
        return $cart->items->isEmpty();
    }

    public function getCartExpiryDate(Cart $cart): ?Carbon
    {
        if ($cart->user_id) {
            return null; // User carts don't expire
        }

        $lastActivity = $cart->updated_at ?? $cart->created_at;
        return $lastActivity->addDays(config('cart.guest_cart_expiry_days', 7));
    }

    public function refreshCartItemPrices(Cart $cart): CartOperationResponse
    {
        $startTime = microtime(true);
        // Load product and productVariant if exists
        $cart->loadMissing(['items.product', 'items.productVariant']);
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($cart->items as $item) {
                if (!$item->product) {
                    Log::warning('Skipping price refresh for cart item with missing product.', ['cart_item_id' => $item->id]);
                    continue;
                }

                $currentPrice = $item->price;
                $expectedPrice = $item->product->price;

                if ($item->productVariant) {
                    $expectedPrice += $item->productVariant->price_adjustment;
                }

                if ($currentPrice !== $expectedPrice) {
                    $item->price = $expectedPrice;
                    $this->cartRepository->updateCartItem($item, ['price' => $expectedPrice]);
                    $updatedCount++;
                }
            }
            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('refreshCartItemPrices_duration', microtime(true) - $startTime, ['updated_count' => $updatedCount]);
            return CartOperationResponse::success("قیمت {$updatedCount} آیتم سبد خرید به‌روزرسانی شد.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error refreshing cart item prices: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('refreshCartItemPrices_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در به‌روزرسانی قیمت آیتم‌های سبد خرید.', 0, $e);
        }
    }
}
