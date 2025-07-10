<?php

namespace App\Services;

use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Events\Dispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection; // اضافه شده: برای استفاده از متد toArray() روی Collection

// Contracts
use App\Services\Contracts\CartServiceInterface;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\ProductServiceInterface;
use App\Services\Contracts\CartCleanupServiceInterface;
use App\Services\Contracts\CartItemManagementServiceInterface;
use App\Services\Contracts\CartBulkUpdateServiceInterface;
use App\Services\Contracts\CartClearServiceInterface;
use App\Contracts\Services\CouponService; // اطمینان حاصل کنید که اینترفیس صحیح ایمپورت شده است
use App\Services\CartCalculationService;

// Managers
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;
use App\Services\Managers\StockManager;
use App\Services\Managers\CartValidator;

// Responses
use App\Services\Responses\CartOperationResponse;
use App\Services\Responses\CartContentsResponse;
use App\Exceptions\EmptyCartException; // New import for custom exception
use App\DTOs\CartTotalsDTO; // New import for DTO

class CartService implements CartServiceInterface, CartItemManagementServiceInterface, CartBulkUpdateServiceInterface, CartClearServiceInterface, CartCleanupServiceInterface
{
    private CartRepositoryInterface $cartRepository;
    private ProductRepositoryInterface $productRepository;
    private CartCacheManager $cacheManager;
    private CartRateLimiter $cartRateLimiter;
    private CartMetricsManager $metricsManager;
    private StockManager $stockManager;
    private CartValidator $cartValidator;
    private CouponService $couponService;
    private Dispatcher $eventDispatcher;
    private CartCalculationService $cartCalculationService;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
        CartCacheManager $cacheManager,
        CartRateLimiter $cartRateLimiter,
        CartMetricsManager $metricsManager,
        StockManager $stockManager,
        CartValidator $cartValidator,
        CouponService $couponService,
        Dispatcher $eventDispatcher,
        CartCalculationService $cartCalculationService
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->cacheManager = $cacheManager;
        $this->cartRateLimiter = $cartRateLimiter;
        $this->metricsManager = $metricsManager;
        $this->stockManager = $stockManager;
        $this->cartValidator = $cartValidator;
        $this->couponService = $couponService;
        $this->eventDispatcher = $eventDispatcher;
        $this->cartCalculationService = $cartCalculationService;
    }

    /**
     * Get or create a cart for a given user or session.
     * دریافت یا ایجاد یک سبد خرید برای کاربر یا جلسه مشخص.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @return Cart
     */
    public function getOrCreateCart(?User $user = null, ?string $sessionId = null): Cart
    {
        $startTime = microtime(true);
        $cart = $this->cartRepository->findByUserOrSession($user, $sessionId);

        if (!$cart) {
            $data = [];
            if ($user) {
                $data['user_id'] = $user->id;
            } elseif ($sessionId) {
                $data['session_id'] = $sessionId;
            } else {
                $data['session_id'] = Session::getId();
            }
            $cart = $this->cartRepository->create($data);
            Log::info('New cart created', ['cart_id' => $cart->id, 'user_id' => $user->id ?? 'guest', 'session_id' => $sessionId]);
        } else {
            Log::info('Existing cart retrieved', ['cart_id' => $cart->id, 'user_id' => $user->id ?? 'guest', 'session_id' => $sessionId]);
        }
        $this->metricsManager->recordMetric('getOrCreateCart_duration', microtime(true) - $startTime, ['user_id' => $user ? $user->id : null, 'session_id' => $sessionId ?? session()->getId()]);
        return $cart;
    }

    /**
     * Merge a guest cart into a user's cart upon login.
     * ادغام سبد خرید مهمان با سبد خرید کاربر پس از ورود.
     *
     * @param \App\Models\User $user
     * @param string $guestSessionId
     * @return void
     */
    public function mergeGuestCart(User $user, string $guestSessionId): void
    {
        $guestCart = $this->cartRepository->findBySessionId($guestSessionId);
        if ($guestCart) {
            $this->assignGuestCartToUser($user, $guestSessionId);
        }
        Log::info('Merge guest cart called', ['user_id' => $user->id, 'guest_session_id' => $guestSessionId]);
    }

    /**
     * Assigns a guest cart to a newly registered user.
     * اختصاص سبد خرید مهمان به کاربر تازه ثبت نام شده.
     *
     * @param string $guestSessionId
     * @param \App\Models\User $newUser
     * @return void
     */
    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void
    {
        $this->assignGuestCartToUser($newUser, $guestSessionId);
        Log::info('Assign guest cart to new user called', ['new_user_id' => $newUser->id, 'guest_session_id' => $guestSessionId]);
    }

    /**
     * Adds a new product to the cart or updates an existing item's quantity.
     * یک محصول جدید را به سبد خرید اضافه می‌کند یا تعداد یک آیتم موجود را به‌روزرسانی می‌کند.
     *
     * This method implements CartItemManagementServiceInterface::addOrUpdateItem.
     *
     * @param \App\Models\Cart $cart The cart to operate on.
     * @param int $productId The ID of the product.
     * @param int $quantity The quantity to add or set.
     * @param int|null $productVariantId The ID of the product variant, if applicable.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function addOrUpdateItem(Cart $cart, int $productId, int $quantity, ?int $productVariantId = null): CartOperationResponse
    {
        $startTime = microtime(true);
        try {
            if ($quantity <= 0) {
                return CartOperationResponse::error('تعداد نمی‌تواند صفر یا منفی باشد.', 400);
            }

            DB::beginTransaction();

            $product = $this->productRepository->findByIdWithLock($productId);
            if (!$product) {
                DB::rollBack();
                Log::error('Product not found during add/update cart item.', ['product_id' => $productId]);
                return CartOperationResponse::error('محصول یافت نشد.', 404);
            }

            $cartItem = $cart->items->first(function ($item) use ($productId, $productVariantId) {
                return $item->product_id === $productId && $item->product_variant_id === $productVariantId;
            });

            $oldQuantity = $cartItem ? $cartItem->quantity : 0;
            $newQuantity = $oldQuantity + $quantity;

            // Check stock
            $availableStockConsideringOtherReservations = $product->stock - ($product->reserved_stock ?? 0) + $oldQuantity;

            if ($newQuantity > $availableStockConsideringOtherReservations) {
                DB::rollBack();
                Log::warning('Insufficient stock for add/update operation', [
                    'product_id' => $product->id,
                    'requested_new_quantity' => $newQuantity,
                    'available_stock_considering_others' => $availableStockConsideringOtherReservations,
                    'product_stock' => $product->stock,
                    'current_reserved_stock' => ($product->reserved_stock ?? 0),
                    'old_quantity_this_item' => $oldQuantity
                ]);
                $message = 'موجودی کافی نیست. حداکثر موجودی قابل افزودن: ' . ($availableStockConsideringOtherReservations - $oldQuantity) . ' عدد.';
                return CartOperationResponse::error($message, 400);
            }

            if ($cartItem) {
                $cartItem->quantity = $newQuantity;
                $cartItem->save();
                $action = 'updated';
            } else {
                $cartItem = $this->cartRepository->createCartItem([
                    'cart_id' => $cart->id,
                    'product_id' => $productId,
                    'product_variant_id' => $productVariantId,
                    'quantity' => $newQuantity,
                    'price' => $product->price, // Store current price
                ]);
                $action = 'added';
            }

            if ($this->stockManager) {
                $quantityDifference = $newQuantity - $oldQuantity;
                if ($quantityDifference > 0) {
                    $this->stockManager->reserveStock($product, $quantityDifference);
                } elseif ($quantityDifference < 0) {
                    $this->stockManager->releaseStock($product, abs($quantityDifference));
                }
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('addOrUpdateItem_duration', microtime(true) - $startTime, ['action' => $action]);
            Log::info('Cart item ' . $action, ['cart_item_id' => $cartItem->id, 'product_id' => $productId, 'product_variant_id' => $productVariantId, 'quantity' => $quantity]);
            return CartOperationResponse::success('آیتم سبد خرید با موفقیت ' . ($action === 'added' ? 'اضافه' : 'به‌روزرسانی') . ' شد.', ['cart_item' => $cartItem]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error adding or updating cart item: ' . $e->getMessage(), ['product_id' => $productId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('addOrUpdateItem_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::error('خطا در افزودن یا به‌روزرسانی آیتم سبد خرید.', 500);
        }
    }

    /**
     * Add product to cart or update quantity.
     * محصول را به سبد خرید اضافه یا تعداد آن را به‌روزرسانی می‌کند.
     *
     * This method forwards the call to addOrUpdateItem to satisfy CartServiceInterface.
     *
     * @param \App\Models\Cart $cart
     * @param int $productId
     * @param int $quantity
     * @param int|null $productVariantId
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function addOrUpdateCartItem(Cart $cart, int $productId, int $quantity, ?int $productVariantId = null): CartOperationResponse
    {
        return $this->addOrUpdateItem($cart, $productId, $quantity, $productVariantId);
    }

    /**
     * Updates the quantity of an existing cart item.
     * تعداد یک آیتم موجود در سبد خرید را به‌روزرسانی می‌کند.
     *
     * This method implements CartItemManagementServiceInterface::updateItemQuantity.
     *
     * @param \App\Models\CartItem $cartItem The cart item to update.
     * @param int $newQuantity The new quantity for the item.
     * @param \App\Models\User|null $user The authenticated user, if any.
     * @param string|null $sessionId The session ID for guest carts.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function updateItemQuantity(CartItem $cartItem, int $newQuantity, ?User $user = null, ?string $sessionId = null): CartOperationResponse
    {
        $startTime = microtime(true);
        try {
            if ($newQuantity < 0) {
                return CartOperationResponse::error('تعداد نمی‌تواند منفی باشد.', 400);
            }

            DB::beginTransaction();

            $product = Product::where('id', $cartItem->product_id)->lockForUpdate()->first();
            if (!$product) {
                DB::rollBack();
                Log::error('Product not found for cart item during quantity update (locked).', ['cart_item_id' => $cartItem->id]);
                return CartOperationResponse::error('محصول مرتبط با آیتم سبد خرید یافت نشد.', 404);
            }

            $oldQuantity = $cartItem->quantity;

            $availableStockConsideringOtherReservations = $product->stock - ($product->reserved_stock ?? 0) + $oldQuantity;

            if ($newQuantity > $availableStockConsideringOtherReservations) {
                DB::rollBack();
                Log::warning('Insufficient stock for quantity update', [
                    'cart_item_id' => $cartItem->id,
                    'product_id' => $product->id,
                    'requested_new_quantity' => $newQuantity,
                    'available_stock_considering_others' => $availableStockConsideringOtherReservations,
                    'product_stock' => $product->stock,
                    'current_reserved_stock' => ($product->reserved_stock ?? 0),
                    'old_quantity_this_item' => $oldQuantity
                ]);
                $message = 'موجودی کافی نیست. حداکثر موجودی قابل تنظیم: ' . $availableStockConsideringOtherReservations . ' عدد.';
                return CartOperationResponse::error($message, 400);
            }

            $cartItem->quantity = $newQuantity;
            $cartItem->save();

            if ($this->stockManager) {
                $quantityDifference = $newQuantity - $oldQuantity;
                if ($quantityDifference > 0) {
                    $this->stockManager->reserveStock($product, $quantityDifference);
                } elseif ($quantityDifference < 0) {
                    $this->stockManager->releaseStock($product, abs($quantityDifference));
                }
            }

            DB::commit();
            $this->cacheManager->clearCache($user ?? $cartItem->cart->user, $sessionId ?? $cartItem->cart->session_id);
            $this->metricsManager->recordMetric('updateItemQuantity_duration', microtime(true) - $startTime, ['action' => 'updated']);
            Log::info('Cart item quantity updated', ['cart_item_id' => $cartItem->id, 'product_id' => $product->id, 'product_variant_id' => $cartItem->product_variant_id, 'old_quantity' => $oldQuantity, 'new_quantity' => $newQuantity]);
            return CartOperationResponse::success('تعداد آیتم سبد خرید با موفقیت به‌روزرسانی شد.', ['cart_item' => $cartItem]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating cart item quantity: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateItemQuantity_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::error('خطا در به‌روزرسانی تعداد آیتم سبد خرید.', 500);
        }
    }

    /**
     * Update quantity of a specific cart item.
     * تعداد یک آیتم خاص در سبد خرید را به‌روزرسانی می‌کند.
     *
     * This method forwards the call to updateItemQuantity to satisfy CartServiceInterface.
     *
     * @param \App\Models\CartItem $cartItem
     * @param int $newQuantity
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function updateCartItemQuantity(CartItem $cartItem, int $newQuantity, ?User $user = null, ?string $sessionId = null): CartOperationResponse
    {
        return $this->updateItemQuantity($cartItem, $newQuantity, $user, $sessionId);
    }

    /**
     * Remove a specific cart item.
     * یک آیتم خاص را از سبد خرید حذف می‌کند.
     *
     * This method implements CartServiceInterface::removeCartItem.
     *
     * @param \App\Models\CartItem $cartItem The cart item to remove.
     * @param \App\Models\User|null $user The authenticated user, if any.
     * @param string|null $sessionId The session ID for guest carts.
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function removeCartItem(CartItem $cartItem, ?User $user = null, ?string $sessionId = null): CartOperationResponse
    {
        try {
            DB::beginTransaction();
            if ($this->stockManager) {
                $product = Product::where('id', $cartItem->product_id)->lockForUpdate()->first();
                if ($product) {
                    $this->stockManager->releaseStock($product, $cartItem->quantity);
                } else {
                    Log::warning('Product not found for stock release during cart item removal.', ['cart_item_id' => $cartItem->id]);
                }
            }
            $this->cartRepository->deleteCartItem($cartItem);
            DB::commit();
            $this->cacheManager->clearCache($user ?? $cartItem->cart->user, $sessionId ?? $cartItem->cart->session_id);
            Log::info('Cart item removed', ['cart_item_id' => $cartItem->id]);
            return CartOperationResponse::success('آیتم سبد خرید با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error removing cart item: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return CartOperationResponse::error('خطا در حذف آیتم سبد خرید.', 500);
        }
    }

    /**
     * Removes a cart item from the cart.
     * یک آیتم سبد خرید را از سبد خرید حذف می‌کند.
     *
     * This method forwards the call to removeCartItem to satisfy CartItemManagementServiceInterface.
     *
     * @param \App\Models\CartItem $cartItem The cart item to remove.
     * @param \App\Models\User|null $user The authenticated user, if any.
     * @param string|null $sessionId The session ID for guest carts.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function removeItem(CartItem $cartItem, ?User $user = null, ?string $sessionId = null): CartOperationResponse
    {
        return $this->removeCartItem($cartItem, $user, $sessionId);
    }

    /**
     * Clear all items from the cart.
     * همه آیتم‌ها را از سبد خرید پاک می‌کند.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function clearCart(Cart $cart): CartOperationResponse
    {
        $startTime = microtime(true);
        try {
            DB::beginTransaction();
            $user = $cart->user;
            $sessionId = $cart->session_id;

            if ($this->stockManager) {
                foreach ($cart->items as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                    if ($product) {
                        $this->stockManager->releaseStock($product, $item->quantity);
                    } else {
                        Log::warning('Product not found for stock release during cart clear.', ['cart_item_id' => $item->id]);
                    }
                }
            }

            $this->cartRepository->clearCart($cart);
            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId);
            $this->metricsManager->recordMetric('clearCart_duration', microtime(true) - $startTime, ['action' => 'cleared']);
            Log::info('Cart cleared', ['cart_id' => $cart->id]);
            return CartOperationResponse::success('سبد خرید با موفقیت پاک شد.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error clearing cart: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('clearCart_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::error('خطا در پاک کردن سبد خرید.', 500);
        }
    }

    /**
     * Get cart contents for display.
     * محتویات سبد خرید را برای نمایش دریافت می‌کند.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartContentsResponse
     */
    public function getCartContents(Cart $cart): CartContentsResponse
    {
        $startTime = microtime(true);
        try {
            // Load items and their relations
            $cart->load(['items.product', 'items.productVariant']); // بارگذاری روابط مستقیماً روی آبجکت $cart

            // اگر سبد خرید خالی است، یک پاسخ خالی و با مجموع‌های صفر برگردانید.
            if ($cart->items->isEmpty()) {
                $emptyTotals = new CartTotalsDTO(
                    subtotal: 0,
                    discount: 0,
                    shipping: 0,
                    tax: 0,
                    total: 0
                );
                $this->metricsManager->recordMetric('getCartContents_duration', microtime(true) - $startTime, ['cart_id' => $cart->id, 'status' => 'empty_cart']);
                // Convert the empty collection to an array before passing it
                return new CartContentsResponse($cart->items->toArray(), 0, 0.0, $emptyTotals);
            }

            // Calculate totals using the dedicated service
            // Note: This DTO contains subtotal, tax, total, etc.
            $cartTotalsDTO = $this->cartCalculationService->calculateCartTotals($cart);

            // Create the response object using positional arguments
            // Since CartContentsResponse now expects $cartTotals in its constructor,
            // we pass it directly as the fourth positional argument.
            $response = new CartContentsResponse(
                $cart->items->toArray(), // تبدیل کالکشن به آرایه
                $cart->items->sum('quantity'),
                $cartTotalsDTO->total,
                $cartTotalsDTO // Pass CartTotalsDTO directly as the fourth argument
            );

            $this->metricsManager->recordMetric('getCartContents_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);

            return $response;
        } catch (\Throwable $e) {
            Log::error('Error getting cart contents: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('getCartContents_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);

            // In case of any error, return an empty CartContentsResponse with default totals
            $emptyTotals = new CartTotalsDTO(
                subtotal: 0,
                discount: 0,
                shipping: 0,
                tax: 0,
                total: 0
            );

            // Pass all arguments as positional, including the emptyTotals DTO
            $errorResponse = new CartContentsResponse(
                collect([])->toArray(), // items (empty Collection converted to array)
                0,           // totalQuantity
                0.0,         // totalPrice
                $emptyTotals // cartTotals
            );

            return $errorResponse;
        }
    }

    /**
     * Update multiple items in the cart (e.g., from a form submission).
     * به‌روزرسانی چندین آیتم در سبد خرید (مثلاً از طریق ارسال فرم).
     *
     * @param \App\Models\Cart $cart
     * @param array $updates An array of updates, each containing 'cart_item_id' and 'quantity'.
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function updateMultipleItems(Cart $cart, array $updates): CartOperationResponse
    {
        try {
            DB::beginTransaction();
            foreach ($updates as $update) {
                $cartItem = $cart->items->find($update['cart_item_id']);
                if ($cartItem) {
                    $this->updateItemQuantity($cartItem, $update['quantity'], $cart->user, $cart->session_id);
                } else {
                    Log::warning('Cart item not found for bulk update.', ['cart_item_id' => $update['cart_item_id']]);
                }
            }
            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            return CartOperationResponse::success('آیتم‌های سبد خرید با موفقیت به‌روزرسانی شدند.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating multiple cart items: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return CartOperationResponse::error('خطا در به‌روزرسانی چندین آیتم سبد خرید.', 500);
        }
    }

    /**
     * Cleanup expired guest carts.
     * پاکسازی سبدهای خرید مهمان منقضی شده.
     *
     * @param int|null $daysCutoff
     * @return int The number of carts cleaned up.
     */
    public function cleanupExpiredCarts(?int $daysCutoff = null): int
    {
        Log::info('cleanupExpiredCarts called via interface. This method needs to be properly implemented or delegated to an existing cleanup method that returns a count.');
        // You would typically delegate this to a dedicated cleanup service or implement the logic here.
        // For now, returning 0 as per previous implementation.
        return 0;
    }

    /**
     * Check if a user (or session) owns a specific cart item.
     * بررسی مالکیت یک آیتم سبد خرید توسط کاربر (یا جلسه).
     *
     * @param \App\Models\CartItem $cartItem
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @return bool
     */
    public function userOwnsCartItem(CartItem $cartItem, ?User $user, ?string $sessionId): bool
    {
        if ($user && $cartItem->cart->user_id === $user->id) {
            return true;
        }
        if ($sessionId && $cartItem->cart->session_id === $sessionId) {
            return true;
        }
        return false;
    }

    /**
     * Get a cart by its ID, ensuring ownership.
     * دریافت یک سبد خرید بر اساس شناسه آن، با اطمینان از مالکیت.
     *
     * @param int $cartId
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @return \App\Models\Cart|null
     */
    public function getCartById(int $cartId, ?User $user = null, ?string $sessionId = null): ?Cart
    {
        $cart = $this->cartRepository->findById($cartId);
        // Ensure that if the cart exists, it belongs to the current user or session.
        // If the cart has items, check the ownership of the first item.
        // Otherwise, check cart's direct user_id or session_id.
        if ($cart && (
            ($cart->items->isNotEmpty() && $this->userOwnsCartItem($cart->items->first(), $user, $sessionId)) ||
            ($user && $cart->user_id === $user->id) ||
            ($sessionId && $cart->session_id === $sessionId)
        )) {
            return $cart;
        }
        return null;
    }

    /**
     * Calculate the subtotal, total, shipping, tax, and discount for a cart.
     * محاسبه مجموع فرعی، مجموع کل، هزینه حمل و نقل، مالیات و تخفیف برای یک سبد خرید.
     *
     * This method implements CartServiceInterface::calculateCartTotals.
     *
     * @param \App\Models\Cart $cart
     * @return array
     */
    public function calculateCartTotals(Cart $cart): array
    {
        // Delegate the calculation to the dedicated CartCalculationService
        // محاسبه را به سرویس اختصاصی CartCalculationService واگذار کنید.
        $cartTotalsDTO = $this->cartCalculationService->calculateCartTotals($cart);
        return $cartTotalsDTO->toArray(); // Assuming CartTotalsDTO has a toArray method
    }

    /**
     * Validate cart items for issues like insufficient stock or missing products.
     * اعتبارسنجی آیتم‌های سبد خرید برای مشکلاتی مانند کمبود موجودی یا محصولات گم شده.
     *
     * @param \App\Models\Cart $cart
     * @return array An array of validation issues.
     */
    public function validateCartItems(Cart $cart): array
    {
        $issues = [];
        foreach ($cart->items as $item) {
            $product = $item->product;
            if (!$product) {
                $issues[] = ['type' => 'missing_product', 'cart_item_id' => $item->id];
                continue;
            }

            $product = Product::where('id', $product->id)->lockForUpdate()->first();
            if (!$product) {
                 $issues[] = ['type' => 'missing_product_after_lock', 'cart_item_id' => $item->id];
                 continue;
            }

            $availableStock = $product->stock - ($product->reserved_stock ?? 0) + $item->quantity;
            if ($item->quantity > $availableStock) {
                $issues[] = [
                    'type' => 'insufficient_stock',
                    'cart_item_id' => $item->id,
                    'product_id' => $product->id,
                    'requested_quantity' => $item->quantity,
                    'available_stock' => $availableStock
                ];
            }
        }
        return $issues;
    }

    /**
     * Apply a coupon to the cart.
     * اعمال یک کد تخفیف به سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @param string $couponCode
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse
    {
        try {
            DB::beginTransaction();
            // واگذاری اعمال کوپن به CouponService
            $response = $this->couponService->applyCoupon($cart, $couponCode);

            if ($response->isSuccess()) {
                // اگر اعمال کوپن موفقیت‌آمیز بود، کش را پاک کرده
                // و CartOperationResponse را با مجموع‌های جدید سبد خرید برمی‌گردانیم.
                $this->cacheManager->clearCache($cart->user, $cart->session_id);
                return CartOperationResponse::success(
                    $response->getMessage(),
                    ['cartTotals' => $this->cartCalculationService->calculateCartTotals($cart->fresh())]
                );
            }

            // اگر اعمال کوپن ناموفق بود، تراکنش را Rollback کرده و پاسخ CouponService را برمی‌گردانیم.
            DB::rollBack();
            return $response;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error applying coupon in CartService: ' . $e->getMessage(), ['coupon_code' => $couponCode, 'exception' => $e->getTraceAsString()]);
            return CartOperationResponse::error('خطا در اعمال کد تخفیف.', 500);
        }
    }

    /**
     * Remove a coupon from the cart.
     * حذف یک کد تخفیف از سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function removeCoupon(Cart $cart): CartOperationResponse
    {
        try {
            DB::beginTransaction();
            // واگذاری حذف کوپن به CouponService
            $response = $this->couponService->removeCoupon($cart);

            if ($response->isSuccess()) {
                // اگر حذف کوپن موفقیت‌آمیز بود، کش را پاک کرده
                // و CartOperationResponse را با مجموع‌های جدید سبد خرید برمی‌گردانیم.
                $this->cacheManager->clearCache($cart->user, $cart->session_id);
                return CartOperationResponse::success(
                    $response->getMessage(),
                    ['cartTotals' => $this->cartCalculationService->calculateCartTotals($cart->fresh())]
                );
            }

            // اگر حذف کوپن ناموفق بود، تراکنش را Rollback کرده و پاسخ CouponService را برمی‌گردانیم.
            DB::rollBack();
            return $response;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error removing coupon in CartService: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return CartOperationResponse::error('خطا در حذف کد تخفیف.', 500);
        }
    }

    /**
     * Get the total count of items (sum of quantities) in the cart.
     * دریافت تعداد کل آیتم‌ها (مجموع تعداد) در سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @return int
     */
    public function getCartItemCount(Cart $cart): int
    {
        return $cart->items->sum('quantity');
    }

    /**
     * Transfer cart ownership from a guest session to a new user.
     * انتقال مالکیت سبد خرید از جلسه مهمان به کاربر جدید.
     *
     * @param \App\Models\Cart $cart
     * @param \App\Models\User $newOwner
     * @return bool
     */
    public function transferCartOwnership(Cart $cart, User $newOwner): bool
    {
        try {
            DB::beginTransaction();
            $this->cartRepository->assignCartToUser($cart, $newOwner);
            DB::commit();
            $this->cacheManager->clearCache($newOwner, null);
            Log::info('Cart ownership transferred', ['cart_id' => $cart->id, 'old_session_id' => $cart->session_id, 'new_user_id' => $newOwner->id]);
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error transferring cart ownership: ' . $e->getMessage(), ['cart_id' => $cart->id, 'new_user_id' => $newOwner->id, 'exception' => $e->getTraceAsString()]);
            return false;
        }
    }

    /**
     * Check if the cart is empty.
     * بررسی خالی بودن سبد خرید.
     *
     * @param \App\Models\Cart $cart
     * @return bool
     */
    public function isCartEmpty(Cart $cart): bool
    {
        return $cart->items->isEmpty();
    }

    /**
     * Get the expiry date for a guest cart.
     * دریافت تاریخ انقضای سبد خرید مهمان.
     *
     * @param \App\Models\Cart $cart
     * @return \Carbon\Carbon|null
     */
    public function getCartExpiryDate(Cart $cart): ?Carbon
    {
        if ($cart->user_id) {
            return null;
        }
        $guestCartLifetimeHours = config('cart.guest_cart_lifetime_hours', 72);
        return $cart->created_at->addHours($guestCartLifetimeHours);
    }

    /**
     * Refresh the prices of items in the cart based on current product prices.
     * به‌روزرسانی قیمت آیتم‌های سبد خرید بر اساس قیمت‌های فعلی محصول.
     *
     * @param \App\Models\Cart $cart
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function refreshCartItemPrices(Cart $cart): CartOperationResponse
    {
        // This method now delegates to CartCalculationService
        // این متد اکنون به CartCalculationService واگذار می‌شود.
        return $this->cartCalculationService->refreshCartItemPrices($cart);
    }

    /**
     * سبد خرید مهمان را به کاربر لاگین شده اختصاص می‌دهد.
     *
     * @param User $user
     * @param string $sessionId
     * @return CartOperationResponse
     */
    public function assignGuestCartToUser(User $user, string $sessionId): CartOperationResponse
    {
        $startTime = microtime(true);
        try {
            DB::beginTransaction();

            $guestCart = $this->cartRepository->findBySessionId($sessionId);
            $userCart = $this->cartRepository->findByUserId($user->id);

            if ($guestCart) {
                if ($userCart) {
                    foreach ($guestCart->items as $guestItem) {
                        $product = Product::where('id', $guestItem->product_id)->lockForUpdate()->first();
                        if (!$product) {
                            Log::warning('Product not found during guest cart merge, skipping item.', ['product_id' => $guestItem->product_id]);
                            continue;
                        }

                        $existingUserItem = $userCart->items->first(function ($item) use ($guestItem) {
                            return $item->product_id === $guestItem->product_id && $item->product_variant_id === $guestItem->product_variant_id;
                        });

                        if ($existingUserItem) {
                            $oldUserQuantity = $existingUserItem->quantity;
                            $desiredNewQuantity = $oldUserQuantity + $guestItem->quantity;

                            $availableStock = $product->stock - ($product->reserved_stock ?? 0) + $oldUserQuantity;

                            $finalNewQuantity = $desiredNewQuantity;
                            if ($desiredNewQuantity > $availableStock) {
                                $finalNewQuantity = $availableStock;
                                Log::warning('Merged cart item quantity capped due to insufficient stock.', [
                                    'product_id' => $product->id,
                                    'requested_quantity' => $desiredNewQuantity,
                                    'capped_quantity' => $finalNewQuantity,
                                    'available_stock' => $availableStock,
                                    'current_reserved_stock' => ($product->reserved_stock ?? 0),
                                    'old_user_quantity' => $oldUserQuantity
                                ]);
                            }

                            $this->cartRepository->updateCartItem($existingUserItem, ['quantity' => $finalNewQuantity]);
                            if ($this->stockManager) {
                                $quantityDifference = $finalNewQuantity - $oldUserQuantity;
                                if ($quantityDifference > 0) {
                                    $this->stockManager->reserveStock($product, $quantityDifference);
                                } elseif ($quantityDifference < 0) {
                                    $this->stockManager->releaseStock($product, abs($quantityDifference));
                                }
                            }
                        } else {
                            $guestItem->cart_id = $userCart->id;
                            $guestItem->save();
                            if ($this->stockManager) {
                                $this->stockManager->reserveStock($product, $guestItem->quantity);
                            }
                        }
                    }
                    $this->cartRepository->delete($guestCart);
                    Log::info('Guest cart merged with user cart', ['guest_cart_id' => $guestCart->id, 'user_cart_id' => $userCart->id, 'user_id' => $user->id]);
                } else {
                    $this->cartRepository->assignCartToUser($guestCart, $user);
                    Log::info('Guest cart assigned to user', ['cart_id' => $guestCart->id, 'user_id' => $user->id]);
                }
            }
            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId);
            $this->metricsManager->recordMetric('assignGuestCartToUser_duration', microtime(true) - $startTime, ['user_id' => $user->id]);
            return CartOperationResponse::success('سبد خرید مهمان با موفقیت به کاربر اختصاص داده شد.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error assigning guest cart to user: ' . $e->getMessage(), ['user_id' => $user->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('assignGuestCartToUser_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::error('خطا در اختصاص سبد خرید مهمان به کاربر.', 500);
        }
    }
}