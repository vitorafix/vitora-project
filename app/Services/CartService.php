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
use Illuminate\Support\Collection;
use InvalidArgumentException; // Import for validation

// Contracts
use App\Services\Contracts\CartServiceInterface;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\ProductServiceInterface;
use App\Services\Contracts\CartCleanupServiceInterface;
use App\Services\Contracts\CartItemManagementServiceInterface;
use App\Services\Contracts\CartBulkUpdateServiceInterface;
use App\Services\Contracts\CartClearServiceInterface;
use App\Contracts\Services\CouponService;
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
use App\Exceptions\EmptyCartException;
use App\DTOs\CartTotalsDTO;

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
     * Get or create a cart for a given user, session, or guest UUID.
     * دریافت یا ایجاد یک سبد خرید برای کاربر، جلسه یا UUID مهمان مشخص.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @param string|null $guestUuid
     * @return Cart
     */
    public function getOrCreateCart(?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): Cart
    {
        $startTime = microtime(true);
        $cart = null;

        // 1. Try to find cart by user ID if user is authenticated
        if ($user) {
            $cart = $this->cartRepository->findByUserId($user->id);
            if ($cart) {
                Log::info('Existing cart found by user ID.', ['cart_id' => $cart->id, 'user_id' => $user->id]);
            }
        }

        // 2. If no user cart found, try to find by guest UUID
        if (!$cart && $guestUuid) {
            $cart = $this->cartRepository->findByGuestUuid($guestUuid);
            if ($cart) {
                Log::info('Existing cart found by guest UUID.', ['cart_id' => $cart->id, 'guest_uuid' => $guestUuid]);
                // If a guest cart is found and user is now logged in, assign it to the user
                if ($user && !$cart->user_id) {
                    $this->assignGuestCartToUser($user, $guestUuid); // Use guestUuid for assignment
                    $cart->refresh(); // Refresh the cart to get updated user_id
                    Log::info('Guest cart assigned to logged-in user during getOrCreateCart.', ['cart_id' => $cart->id, 'user_id' => $user->id, 'guest_uuid' => $guestUuid]);
                }
            }
        }

        // 3. If no cart found by user or guest UUID, try to find by session ID (fallback)
        // This is a fallback for existing carts using session_id before guest_uuid implementation
        if (!$cart && $sessionId) {
            $cart = $this->cartRepository->findBySessionId($sessionId);
            if ($cart) {
                Log::info('Existing cart found by session ID (fallback).', ['cart_id' => $cart->id, 'session_id' => $sessionId]);
                // If a guest cart is found and user is now logged in, assign it to the user
                if ($user && !$cart->user_id) {
                    $this->assignGuestCartToUser($user, $guestUuid ?? $sessionId); // Use guestUuid if available, else sessionId
                    $cart->refresh(); // Refresh the cart to get updated user_id
                    Log::info('Guest cart assigned to logged-in user during getOrCreateCart (from session_id).', ['cart_id' => $cart->id, 'user_id' => $user->id, 'session_id' => $sessionId]);
                }
            }
        }
        
        // 4. If no cart found by user, guest UUID, or session, create a new one
        if (!$cart) {
            Log::info('No existing cart found. Attempting to create new cart.', [
                'user_id_passed' => $user ? $user->id : 'null',
                'session_id_passed' => $sessionId ?? 'null',
                'guest_uuid_passed' => $guestUuid ?? 'null',
                'current_session_id_from_laravel' => Session::getId()
            ]);

            $data = [];
            if ($user) {
                $data['user_id'] = $user->id;
                $data['session_id'] = null; // Clear session_id for authenticated users
                $data['guest_uuid'] = null; // Clear guest_uuid for authenticated users
            } else {
                // For guest carts, prioritize guest_uuid, then session_id
                $data['guest_uuid'] = $guestUuid;
                $data['session_id'] = $sessionId ?? Session::getId(); // Keep session_id for backward compatibility/fallback
            }
            $cart = $this->cartRepository->create($data);
            Log::info('New cart created.', ['cart_id' => $cart->id, 'user_id' => $user->id ?? 'guest', 'session_id' => $sessionId ?? Session::getId(), 'guest_uuid' => $guestUuid ?? 'null']);
        } else {
            // Ensure the cart has the correct user_id if it was a guest cart and user logged in
            if ($user && !$cart->user_id) {
                $this->assignGuestCartToUser($user, $guestUuid ?? $sessionId); // Use guestUuid if available, else sessionId
                $cart->refresh();
                Log::info('Existing guest cart updated with user ID.', ['cart_id' => $cart->id, 'user_id' => $user->id, 'session_id' => $sessionId, 'guest_uuid' => $guestUuid]);
            }
            // If an existing cart is found and it's a guest cart based on session_id,
            // update its guest_uuid if a new one is provided and the existing one is null.
            if (!$cart->user_id && $cart->session_id && !$cart->guest_uuid && $guestUuid) {
                $cart->guest_uuid = $guestUuid;
                $cart->save();
                Log::info('Guest cart updated with new guest_uuid from session_id based cart.', ['cart_id' => $cart->id, 'guest_uuid' => $guestUuid, 'session_id' => $sessionId]);
            }
        }

        $this->metricsManager->recordMetric('getOrCreateCart_duration', microtime(true) - $startTime, ['user_id' => $user ? $user->id : null, 'session_id' => $sessionId ?? session()->getId(), 'guest_uuid' => $guestUuid ?? 'null']);
        return $cart;
    }

    /**
     * Merge a guest cart into a user's cart upon login.
     * ادغام سبد خرید مهمان با سبد خرید کاربر پس از ورود.
     *
     * @param \App\Models\User $user
     * @param string $guestIdentifier This can be guestSessionId or guestUuid
     * @return void
     */
    public function mergeGuestCart(User $user, string $guestIdentifier): void
    {
        // Try to find by guest UUID first, then by session ID
        $guestCart = $this->cartRepository->findByGuestUuid($guestIdentifier);
        if (!$guestCart) {
            $guestCart = $this->cartRepository->findBySessionId($guestIdentifier);
        }

        if ($guestCart) {
            $this->assignGuestCartToUser($user, $guestIdentifier);
        }
        Log::info('Merge guest cart called', ['user_id' => $user->id, 'guest_identifier' => $guestIdentifier]);
    }

    /**
     * Assigns a guest cart to a newly registered user.
     * اختصاص سبد خرید مهمان به کاربر تازه ثبت نام شده.
     *
     * @param string $guestIdentifier This can be guestSessionId or guestUuid
     * @param \App\Models\User $newUser
     * @return void
     */
    public function assignGuestCartToNewUser(string $guestIdentifier, User $newUser): void
    {
        $this->assignGuestCartToUser($newUser, $guestIdentifier);
        Log::info('Assign guest cart to new user called', ['new_user_id' => $newUser->id, 'guest_identifier' => $guestIdentifier]);
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
                return CartOperationResponse::fail('تعداد نمی‌تواند صفر یا منفی باشد.', 400); // Changed to fail
            }

            DB::beginTransaction();

            // Use the new findByIdWithLock method
            // از متد جدید findByIdWithLock استفاده کنید.
            $product = $this->productRepository->findByIdWithLock($productId);
            if (!$product) {
                DB::rollBack();
                Log::error('Product not found during add/update cart item.', ['product_id' => $productId]);
                return CartOperationResponse::fail('محصول یافت نشد.', 404); // Changed to fail
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
                return CartOperationResponse::fail($message, 400); // Changed to fail
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
            $this->cacheManager->clearCache($cart->user, $cart->session_id); // Keep session_id for now
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
     * @param string|null $guestUuid The guest UUID for guest carts.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function updateItemQuantity(CartItem $cartItem, int $newQuantity, ?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): CartOperationResponse
    {
        $startTime = microtime(true);
        try {
            if ($newQuantity < 0) {
                return CartOperationResponse::fail('تعداد نمی‌تواند منفی باشد.', 400); // Changed to fail
            }

            DB::beginTransaction();

            // Use the new findByIdWithLock method
            // از متد جدید findByIdWithLock استفاده کنید.
            $product = $this->productRepository->findByIdWithLock($cartItem->product_id);
            if (!$product) {
                DB::rollBack();
                Log::error('Product not found for cart item during quantity update (locked).', ['cart_item_id' => $cartItem->id]);
                return CartOperationResponse::fail('محصول مرتبط با آیتم سبد خرید یافت نشد.', 404); // Changed to fail
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
                return CartOperationResponse::fail($message, 400); // Changed to fail
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
            $this->cacheManager->clearCache($user ?? $cartItem->cart->user, $sessionId ?? $cartItem->cart->session_id); // Keep session_id for now
            $this->metricsManager->recordMetric('updateItemQuantity_duration', microtime(true) - $startTime, ['action' => 'updated']);
            Log::info('Cart item quantity updated', ['cart_item_id' => $cartItem->id, 'product_id' => $product->id, 'product_variant_id' => $cartItem->product_variant_id, 'old_quantity' => $oldQuantity, 'new_quantity' => $newQuantity]);
            return CartOperationResponse::success('تعداد آیتم سبد خرید با موفقیت به‌روزرسانی شد.', ['cart_item' => $cartItem]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updating cart item quantity: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
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
     * @param string|null $guestUuid
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function updateCartItemQuantity(CartItem $cartItem, int $newQuantity, ?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): CartOperationResponse
    {
        return $this->updateItemQuantity($cartItem, $newQuantity, $user, $sessionId, $guestUuid);
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
     * @param string|null $guestUuid The guest UUID for guest carts.
     * @return \App\Services\Responses\CartOperationResponse
     */
    public function removeCartItem(CartItem $cartItem, ?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): CartOperationResponse
    {
        try {
            DB::beginTransaction();
            if ($this->stockManager) {
                // Use the new findByIdWithLock method
                // از متد جدید findByIdWithLock استفاده کنید.
                $product = $this->productRepository->findByIdWithLock($cartItem->product_id);
                if ($product) {
                    $this->stockManager->releaseStock($product, $cartItem->quantity);
                } else {
                    Log::warning('Product not found for stock release during cart item removal.', ['cart_item_id' => $cartItem->id]);
                }
            }
            $this->cartRepository->deleteCartItem($cartItem);
            DB::commit();
            $this->cacheManager->clearCache($user ?? $cartItem->cart->user, $sessionId ?? $cartItem->cart->session_id); // Keep session_id for now
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
     * @param string|null $guestUuid The guest UUID for guest carts.
     * @return \App\Services\Responses\CartOperationResponse
     * @throws \App\Exceptions\BaseCartException
     */
    public function removeItem(CartItem $cartItem, ?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): CartOperationResponse
    {
        return $this->removeCartItem($cartItem, $user, $sessionId, $guestUuid);
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
            $guestUuid = $cart->guest_uuid; // Get guest_uuid from cart

            if ($this->stockManager) {
                foreach ($cart->items as $item) {
                    // Use the new findByIdWithLock method
                    // از متد جدید findByIdWithLock استفاده کنید.
                    $product = $this->productRepository->findByIdWithLock($item->product_id);
                    if ($product) {
                        $this->stockManager->releaseStock($product, $item->quantity);
                    } else {
                        Log::warning('Product not found for stock release during cart clear.', ['cart_item_id' => $item->id]);
                    }
                }
            }

            $this->cartRepository->clearCart($cart);
            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId, $guestUuid); // Pass guestUuid to clearCache
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

            // DEBUG LOG: Log the items collection before checking if it's empty
            Log::debug('CartService::getCartContents - Items collection before isEmpty check:', ['cart_id' => $cart->id, 'items_count' => $cart->items->count(), 'items_data' => $cart->items->toArray()]);


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
                    $this->updateItemQuantity($cartItem, $update['quantity'], $cart->user, $cart->session_id, $cart->guest_uuid); // Pass guestUuid
                } else {
                    Log::warning('Cart item not found for bulk update.', ['cart_item_id' => $update['cart_item_id']]);
                }
            }
            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id, $cart->guest_uuid); // Pass guestUuid
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
        $startTime = microtime(true);
        try {
            $cutoffDate = Carbon::now()->subDays($daysCutoff ?? config('cart.guest_cart_lifetime_days', 30));
            $expiredCarts = $this->cartRepository->getExpiredGuestCarts($cutoffDate);
            $cleanedCount = 0;

            foreach ($expiredCarts as $cart) {
                DB::beginTransaction();
                try {
                    // Release stock for items in the expired cart
                    if ($this->stockManager) {
                        foreach ($cart->items as $item) {
                            $product = $this->productRepository->findByIdWithLock($item->product_id);
                            if ($product) {
                                $this->stockManager->releaseStock($product, $item->quantity);
                            } else {
                                Log::warning('Product not found for stock release during expired cart cleanup.', ['cart_id' => $cart->id, 'cart_item_id' => $item->id]);
                            }
                        }
                    }
                    $this->cartRepository->delete($cart); // Delete the cart and its items (due to cascade delete if set up)
                    DB::commit();
                    $this->cacheManager->clearCache(null, $cart->session_id, $cart->guest_uuid); // Clear cache for the deleted cart
                    $cleanedCount++;
                    Log::info('Expired guest cart cleaned up.', ['cart_id' => $cart->id, 'session_id' => $cart->session_id, 'guest_uuid' => $cart->guest_uuid]);
                } catch (\Throwable $e) {
                    DB::rollBack();
                    Log::error('Error cleaning up expired cart: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
                }
            }
            $this->metricsManager->recordMetric('cleanupExpiredCarts_duration', microtime(true) - $startTime, ['cleaned_count' => $cleanedCount]);
            Log::info('CleanupExpiredCarts service method completed successfully.', [
                'cleaned_carts_count' => $cleanedCount,
                'days_cutoff' => $daysCutoff ?? config('cart.guest_cart_lifetime_days', 30) // Include days_cutoff in final log
            ]);
            return $cleanedCount;
        } catch (\Throwable $e) {
            Log::error('Error in cleanupExpiredCarts service method: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('cleanupExpiredCarts_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return 0;
        }
    }

    /**
     * Check if a user (or session or guest UUID) owns a specific cart item.
     * بررسی مالکیت یک آیتم سبد خرید توسط کاربر (یا جلسه یا UUID مهمان).
     *
     * @param \App\Models\CartItem $cartItem
     * @param \App\Models\User|null $user
     * @param string|null $sessionId
     * @param string|null $guestUuid
     * @return bool
     */
    public function userOwnsCartItem(CartItem $cartItem, ?User $user, ?string $sessionId, ?string $guestUuid = null): bool
    {
        if ($user && $cartItem->cart->user_id === $user->id) {
            return true;
        }
        if ($guestUuid && $cartItem->cart->guest_uuid === $guestUuid) {
            return true;
        }
        // Fallback to session_id for existing carts
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
     * @param string|null $guestUuid
     * @return \App\Models\Cart|null
     */
    public function getCartById(int $cartId, ?User $user = null, ?string $sessionId = null, ?string $guestUuid = null): ?Cart
    {
        $cart = $this->cartRepository->findById($cartId);
        // Ensure that if the cart exists, it belongs to the current user or session or guest UUID.
        if ($cart && (
            ($user && $cart->user_id === $user->id) ||
            ($guestUuid && $cart->guest_uuid === $guestUuid) ||
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

            // Use the new findByIdWithLock method
            // از متد جدید findByIdWithLock استفاده کنید.
            $product = $this->productRepository->findByIdWithLock($product->id);
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
                $this->cacheManager->clearCache($cart->user, $cart->session_id, $cart->guest_uuid); // Pass guestUuid
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
                $this->cacheManager->clearCache($cart->user, $cart->session_id, $cart->guest_uuid); // Pass guestUuid
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
            $this->cacheManager->clearCache($newOwner, null, $cart->guest_uuid); // Pass guestUuid
            Log::info('Cart ownership transferred', ['cart_id' => $cart->id, 'old_session_id' => $cart->session_id, 'new_user_id' => $newOwner->id, 'guest_uuid' => $cart->guest_uuid]);
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
     * این متد اکنون می تواند هم با session_id و هم با guest_uuid کار کند.
     *
     * @param User $user
     * @param string $guestIdentifier - می تواند guestSessionId یا guestUuid باشد.
     * @return CartOperationResponse
     */
    public function assignGuestCartToUser(User $user, string $guestIdentifier): CartOperationResponse
    {
        $startTime = microtime(true);
        try {
            DB::beginTransaction();

            // Try to find the guest cart by guest_uuid first, then by session_id
            $guestCart = $this->cartRepository->findByGuestUuid($guestIdentifier);
            if (!$guestCart) {
                $guestCart = $this->cartRepository->findBySessionId($guestIdentifier);
            }

            $userCart = $this->cartRepository->findByUserId($user->id);

            if ($guestCart) {
                if ($userCart) {
                    Log::info('CartService: Attempting to merge guest cart into existing user cart.', [
                        'user_id' => $user->id,
                        'user_cart_id' => $userCart->id,
                        'guest_cart_id' => $guestCart->id,
                        'guest_identifier' => $guestIdentifier,
                    ]);
                    foreach ($guestCart->items as $guestItem) {
                        // Use the new findByIdWithLock method
                        // از متد جدید findByIdWithLock استفاده کنید.
                        $product = $this->productRepository->findByIdWithLock($guestItem->product_id);
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
                            Log::info('CartService: Merged item updated in user cart.', [
                                'user_id' => $user->id,
                                'user_cart_item_id' => $existingUserItem->id,
                                'product_id' => $product->id,
                                'old_quantity' => $oldUserQuantity,
                                'new_quantity' => $finalNewQuantity,
                            ]);
                        } else {
                            $guestItem->cart_id = $userCart->id;
                            $guestItem->save();
                            if ($this->stockManager) {
                                $this->stockManager->reserveStock($product, $guestItem->quantity);
                            }
                            Log::info('CartService: Guest item moved to user cart.', [
                                'user_id' => $user->id,
                                'guest_cart_item_id' => $guestItem->id,
                                'product_id' => $product->id,
                                'quantity' => $guestItem->quantity,
                            ]);
                        }
                    }
                    $this->cartRepository->delete($guestCart);
                    Log::info('CartService: Guest cart merged with user cart successfully.', ['guest_cart_id' => $guestCart->id, 'user_cart_id' => $userCart->id, 'user_id' => $user->id]);
                } else {
                    // If no user cart exists, assign the guest cart to the user
                    $this->cartRepository->assignCartToUser($guestCart, $user);
                    // Also update the guest_uuid of the cart if it was assigned based on session_id
                    if (!$guestCart->guest_uuid && $guestIdentifier && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $guestIdentifier)) {
                        $guestCart->guest_uuid = $guestIdentifier;
                        $guestCart->save();
                        Log::info('CartService: Guest cart assigned to user and guest_uuid updated.', ['cart_id' => $guestCart->id, 'user_id' => $user->id, 'guest_uuid' => $guestIdentifier]);
                    } else {
                        Log::info('CartService: Guest cart assigned to user successfully (no existing user cart).', ['cart_id' => $guestCart->id, 'user_id' => $user->id, 'guest_identifier' => $guestIdentifier]);
                    }
                }
            } else {
                Log::info('CartService: No guest cart found for identifier to assign/merge.', ['user_id' => $user->id, 'guest_identifier' => $guestIdentifier]);
            }
            DB::commit();
            $this->cacheManager->clearCache($user, null, $guestIdentifier); // Clear cache using guestIdentifier
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
