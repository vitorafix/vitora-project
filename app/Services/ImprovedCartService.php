<?php
// File: app/Services/ImprovedCartService.php
namespace App\Services;

use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Contracts\Events\Dispatcher;
use Carbon\Carbon;

// Contracts
use App\Contracts\Services\CartServiceInterface;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface; // ایمپورت ProductRepositoryInterface

// Managers
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\StockManager;
use App\Services\Managers\CartValidator;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;

// Responses
use App\Services\Responses\CartOperationResponse; // استفاده از CartOperationResponse
use App\Services\Responses\CartContentsResponse;

// Custom Exceptions
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\UnauthorizedCartAccessException;
use App\Exceptions\CartOperationException;
use App\Exceptions\CartInvalidArgumentException;
use App\Exceptions\CartLimitExceededException;
use App\Exceptions\BaseCartException; // ایمپورت BaseCartException

// Events (شما باید این کلاس‌ها را در App\Events/ ایجاد کنید)
// namespace App\Events;
// class CartItemAdded { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public Product $product; public int $quantity; public function __construct(Cart $cart, Product $product, int $quantity) { $this->cart = $cart; $this->product = $product; $this->quantity = $quantity; } }
// class CartItemUpdated { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public CartItem $cartItem; public int $oldQuantity; public int $newQuantity; public function __construct(Cart $cart, CartItem $cartItem, int $oldQuantity, int $newQuantity) { $this->cart = $cart; $this->cartItem = $cartItem; $this->oldQuantity = $oldQuantity; $this->newQuantity = $newQuantity; } }
// class CartItemRemoved { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public CartItem $cartItem; public function __construct(Cart $cart, CartItem $cartItem) { $this->cart = $cart; $this->cartItem = $cartItem; } }
// class CartCleared { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public function __construct(Cart $cart) { $this->cart = $cart; } }
// class CartMerged { use \Illuminate\Foundation\Events\Dispatchable; public Cart $fromCart; public Cart $toCart; public User $user; public function __construct(Cart $fromCart, Cart $toCart, User $user) { $this->fromCart = $fromCart; $this->toCart = $toCart; $this->user = $user; } }


class ImprovedCartService implements CartServiceInterface
{
    protected CartRepositoryInterface $cartRepository; // تزریق ریپازیتوری
    protected ProductRepositoryInterface $productRepository; // تزریق ریپازیتوری
    protected CartCacheManager $cacheManager;
    protected StockManager $stockManager;
    protected CartValidator $validator;
    protected CartRateLimiter $rateLimiter;
    protected CartMetricsManager $metricsManager;
    protected Dispatcher $eventDispatcher;

    // حذف خصوصیات پیکربندی از اینجا، زیرا Managerها خودشان آن‌ها را از کانفیگ می‌خوانند
    // private int $cleanupDays;
    // private int $stockReservationMinutes;
    // private bool $keepCartOnClear;
    // private int $maxBulkOperations;

    public function __construct(
        CartRepositoryInterface $cartRepository, // تزریق CartRepositoryInterface
        ProductRepositoryInterface $productRepository, // تزریق ProductRepositoryInterface
        CartCacheManager $cacheManager,
        StockManager $stockManager,
        CartValidator $validator,
        CartRateLimiter $rateLimiter,
        CartMetricsManager $metricsManager,
        Dispatcher $eventDispatcher
    ) {
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->cacheManager = $cacheManager;
        $this->stockManager = $stockManager;
        $this->validator = $validator;
        $this->rateLimiter = $rateLimiter;
        $this->metricsManager = $metricsManager;
        $this->eventDispatcher = $eventDispatcher;

        // این خصوصیات می‌توانند حذف شوند زیرا Managerها خودشان آن‌ها را از کانفیگ می‌خوانند.
        // یا اگر منطق سرویس به آن‌ها نیاز دارد، مستقیماً از config() استفاده کند.
        // $this->cleanupDays = config('cart.cleanup_days', 30);
        // $this->stockReservationMinutes = config('cart.stock_reservation_minutes', 15);
        // $this->keepCartOnClear = config('cart.keep_cart_on_clear', false);
        // $this->maxBulkOperations = config('cart.max_bulk_operations', 100);
    }

    /**
     * متد کمکی برای دسترسی متمرکز به تنظیمات.
     * این متد می‌تواند حذف شود اگر خصوصیات پیکربندی از Constructor حذف شوند.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getConfig(string $key, $default = null): mixed
    {
        return config("cart.{$key}", $default);
    }

    /**
     * Get existing cart or create new one based on user or session.
     * سبد خرید موجود را دریافت می‌کند یا در صورت عدم وجود، یک سبد جدید ایجاد می‌کند.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @return Cart
     * @throws CartInvalidArgumentException
     */
    public function getOrCreateCart(?User $user = null, ?string $sessionId = null): Cart
    {
        $startTime = microtime(true);
        $this->validator->validateUserOrSession($user, $sessionId);

        $cacheKey = $this->cacheManager->getCacheKey($user, $sessionId);

        $cart = $this->cacheManager->remember($cacheKey, function () use ($user, $sessionId) {
            $cart = null;
            if ($user) {
                Log::info('Fetching or creating cart for user', ['user_id' => $user->id]);
                $cart = $this->cartRepository->findByUserId($user->id); // استفاده از ریپازیتوری
                if (!$cart) {
                    $cart = $this->cartRepository->create(['user_id' => $user->id]); // استفاده از ریپازیتوری
                }
            } elseif ($sessionId) {
                Log::info('Fetching or creating cart for guest session', ['session_id' => $sessionId]);
                $cart = $this->cartRepository->findBySessionId($sessionId); // استفاده از ریپازیتوری
                if (!$cart) {
                    $cart = $this->cartRepository->create(['session_id' => $sessionId]); // استفاده از ریپازیتوری
                }
            }
            return $cart;
        });

        // Ensure the cart relationship is loaded correctly from DB if not from cache
        if ($cart && !$cart->relationLoaded('items')) {
            $cart->load('items.product'); // Eager load items and their products
        }

        $this->metricsManager->recordMetric('getOrCreateCart_duration', microtime(true) - $startTime, ['user_id' => $user?->id, 'session_id' => $sessionId]);
        return $cart;
    }

    /**
     * Merge guest cart with user cart when user logs in.
     * سبد خرید مهمان را با سبد خرید کاربر پس از ورود به سیستم ادغام می‌کند.
     *
     * @param User $user
     * @param string $guestSessionId
     * @return void
     * @throws CartOperationException
     */
    public function mergeGuestCart(User $user, string $guestSessionId): void
    {
        $startTime = microtime(true);
        Log::info('Attempting to merge guest cart with user cart', ['user_id' => $user->id, 'guest_session_id' => $guestSessionId]);

        DB::beginTransaction();
        try {
            $guestCart = $this->cartRepository->findBySessionId($guestSessionId); // استفاده از ریپازیتوری

            if (!$guestCart || $guestCart->items->isEmpty()) {
                Log::info('No guest cart or empty guest cart to merge.', ['guest_session_id' => $guestSessionId]);
                DB::commit();
                return;
            }

            $userCart = $this->cartRepository->findByUserId($user->id); // استفاده از ریپازیتوری

            if (!$userCart) {
                // If user doesn't have a cart, assign guest cart to user
                $guestCart->user_id = $user->id;
                $guestCart->session_id = null;
                $this->cartRepository->save($guestCart); // استفاده از ریپازیتوری
                Log::info('Guest cart assigned to new user as they had no existing cart.', ['user_id' => $user->id, 'guest_cart_id' => $guestCart->id]);
                $this->cacheManager->clearCache($user); // Clear new user's cache
                $this->cacheManager->clearCache(null, $guestSessionId); // Clear guest cache
                $this->eventDispatcher->dispatch(new \App\Events\CartMerged($guestCart, $guestCart, $user)); // Fire event (fromCart, toCart, user)
                DB::commit();
                $this->metricsManager->recordMetric('mergeGuestCart_duration', microtime(true) - $startTime, ['user_id' => $user->id, 'action' => 'assigned']);
                return;
            }

            $guestCartItems = $guestCart->items->keyBy('product_id');
            $userCartItems = $userCart->items->keyBy('product_id');
            $itemsToUpdate = [];
            $itemsToCreate = [];

            foreach ($guestCartItems as $productId => $guestItem) {
                if ($userCartItems->has($productId)) {
                    // Update quantity if item exists in user's cart
                    $existingUserItem = $userCartItems[$productId];
                    $newQuantity = $existingUserItem->quantity + $guestItem->quantity;

                    try {
                        $product = $this->productRepository->find($productId); // استفاده از ریپازیتوری
                        // Validate stock for combined quantity, considering current reserved stock
                        $this->stockManager->validateStock($product, $newQuantity);
                        $this->validator->validateQuantity($newQuantity); // Validate max quantity per item

                        // Instead of direct update, collect for upsert
                        $itemsToUpdate[] = [
                            'id' => $existingUserItem->id,
                            'cart_id' => $userCart->id, // Add cart_id for upsert
                            'product_id' => $productId,
                            'quantity' => $newQuantity,
                            'price' => $product->price, // Use current product price
                            'created_at' => $existingUserItem->created_at,
                            'updated_at' => now(),
                        ];
                    } catch (InsufficientStockException | CartInvalidArgumentException $e) {
                        Log::warning('Skipping merge for product due to validation error: ' . $e->getMessage(), ['product_id' => $productId]);
                        // Optionally, add to a 'failed_merges' array in the response if we had one
                    }
                } else {
                    // Add new item to user's cart
                    try {
                        $product = $this->productRepository->find($productId); // استفاده از ریپازیتوری
                        $this->stockManager->validateStock($product, $guestItem->quantity);
                        $this->validator->validateQuantity($guestItem->quantity);

                        $itemsToCreate[] = [
                            'cart_id' => $userCart->id,
                            'product_id' => $guestItem->product_id,
                            'quantity' => $guestItem->quantity,
                            'price' => $product->price, // Use current product price
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    } catch (InsufficientStockException | CartInvalidArgumentException $e) {
                        Log::warning('Skipping merge for new product due to validation error: ' . $e->getMessage(), ['product_id' => $productId]);
                    }
                }
            }

            // Combine items to update and create for a single upsert call
            $allUpsertItems = array_merge($itemsToUpdate, $itemsToCreate);
            if (!empty($allUpsertItems)) {
                $this->cartRepository->upsertCartItems($allUpsertItems, ['cart_id', 'product_id'], ['quantity', 'price']);
            }

            // Delete guest cart
            $this->cartRepository->delete($guestCart); // استفاده از ریپازیتوری
            Log::info('Guest cart merged and deleted.', ['guest_cart_id' => $guestCart->id, 'user_cart_id' => $userCart->id]);

            $this->cacheManager->clearCache($user); // Clear user's cache
            $this->cacheManager->clearCache(null, $guestSessionId); // Clear guest cache
            $this->eventDispatcher->dispatch(new \App\Events\CartMerged($guestCart, $userCart, $user)); // Fire event (fromCart, toCart, user)

            DB::commit();
            $this->metricsManager->recordMetric('mergeGuestCart_duration', microtime(true) - $startTime, ['user_id' => $user->id, 'action' => 'merged']);

        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Error merging guest cart: ' . $e->getMessage(), ['user_id' => $user->id, 'guest_session_id' => $guestSessionId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('mergeGuestCart_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e; // Re-throw the specific cart exception
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error merging guest cart: ' . $e->getMessage(), ['user_id' => $user->id, 'guest_session_id' => $guestSessionId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('mergeGuestCart_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در ادغام سبد خرید مهمان.', 0, $e);
        }
    }

    /**
     * Assign guest cart to newly registered user.
     * سبد خرید مهمان را به کاربر تازه ثبت‌نام شده اختصاص می‌دهد.
     *
     * @param string $guestSessionId
     * @param User $newUser
     * @return void
     * @throws CartOperationException
     */
    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void
    {
        $startTime = microtime(true);
        Log::info('Attempting to assign guest cart to new user', ['guest_session_id' => $guestSessionId, 'new_user_id' => $newUser->id]);

        DB::beginTransaction();
        try {
            $guestCart = $this->cartRepository->findBySessionId($guestSessionId); // استفاده از ریپازیتوری

            if ($guestCart) {
                // Check if the new user already has a cart (shouldn't happen on fresh registration)
                $existingUserCart = $this->cartRepository->findByUserId($newUser->id); // استفاده از ریپازیتوری
                if ($existingUserCart) {
                    Log::warning('New user already has a cart, merging instead of assigning.', ['new_user_id' => $newUser->id, 'existing_cart_id' => $existingUserCart->id]);
                    DB::rollBack(); // Rollback current transaction to allow merge to start its own
                    $this->mergeGuestCart($newUser, $guestSessionId); // Call merge logic instead
                    return;
                }

                $guestCart->user_id = $newUser->id;
                $guestCart->session_id = null; // Clear session_id as it's now a user cart
                $this->cartRepository->save($guestCart); // استفاده از ریپازیتوری
                Log::info('Guest cart assigned to new user successfully', ['guest_cart_id' => $guestCart->id, 'new_user_id' => $newUser->id]);

                $this->cacheManager->clearCache($newUser); // Clear cache for the new user
                $this->cacheManager->clearCache(null, $guestSessionId); // Clear guest cache

                $this->eventDispatcher->dispatch(new \App\Events\CartMerged($guestCart, $guestCart, $newUser)); // Fire event (fromCart, toCart, user)
            } else {
                Log::info('No guest cart found for assignment.', ['guest_session_id' => $guestSessionId]);
            }

            DB::commit();
            $this->metricsManager->recordMetric('assignGuestCartToNewUser_duration', microtime(true) - $startTime, ['user_id' => $newUser->id]);
        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Error assigning guest cart to new user: ' . $e->getMessage(), ['guest_session_id' => $guestSessionId, 'new_user_id' => $newUser->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('assignGuestCartToNewUser_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e; // Re-throw the specific cart exception
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error assigning guest cart to new user: ' . $e->getMessage(), ['guest_session_id' => $guestSessionId, 'new_user_id' => $newUser->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('assignGuestCartToNewUser_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در اختصاص سبد خرید مهمان به کاربر جدید.', 0, $e);
        }
    }

    /**
     * Add new item or update existing item quantity in the cart.
     * یک آیتم جدید را به سبد خرید اضافه می‌کند یا تعداد یک آیتم موجود را به‌روزرسانی می‌کند.
     *
     * @param Cart $cart
     * @param int $productId
     * @param int $quantity
     * @return CartOperationResponse
     * @throws ProductNotFoundException
     * @throws InsufficientStockException
     * @throws CartInvalidArgumentException
     * @throws CartLimitExceededException
     * @throws CartOperationException
     */
    public function addOrUpdateCartItem(Cart $cart, int $productId, int $quantity): CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        DB::beginTransaction();
        try {
            $product = $this->productRepository->find($productId); // استفاده از ریپازیتوری
            if (!$product) {
                throw new ProductNotFoundException();
            }

            $validatedQuantity = $this->validator->validateQuantity($quantity);

            $cartItem = $this->cartRepository->findCartItem($cart->id, $productId); // استفاده از ریپازیتوری
            $oldQuantity = 0;
            $isNewItem = false;

            if ($cartItem) {
                $oldQuantity = $cartItem->quantity;
                $newQuantity = $validatedQuantity; // User is sending the desired final quantity
                $quantityChange = $newQuantity - $oldQuantity;

                if ($newQuantity === 0) {
                    $this->cartRepository->deleteCartItem($cartItem); // استفاده از ریپازیتوری
                    $this->stockManager->releaseStock($product, $oldQuantity); // Release previously reserved stock
                    $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cart, $cartItem));
                    Log::info('Cart item removed because new quantity is zero.', ['cart_id' => $cart->id, 'product_id' => $productId]);
                    DB::commit();
                    $this->cacheManager->clearCache($cart->user, $cart->session_id);
                    $this->metricsManager->recordMetric('addOrUpdateCartItem_duration', microtime(true) - $startTime, ['action' => 'removed_by_zero_quantity']);
                    return CartOperationResponse::success('محصول از سبد خرید حذف شد.', ['product_id' => $productId]); // استفاده از static constructor
                }

                // Validate stock for the *change*
                if ($quantityChange > 0) {
                    $this->stockManager->validateStock($product, $quantityChange);
                    $this->stockManager->reserveStock($product, $quantityChange); // Reserve additional stock
                } elseif ($quantityChange < 0) {
                    $this->stockManager->releaseStock($product, abs($quantityChange)); // Release excess stock
                }

                $this->cartRepository->updateCartItem($cartItem, ['quantity' => $newQuantity]); // استفاده از ریپازیتوری
                $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cart, $cartItem, $oldQuantity, $newQuantity));
                Log::info('Cart item quantity updated', ['cart_id' => $cart->id, 'product_id' => $productId, 'old_quantity' => $oldQuantity, 'new_quantity' => $newQuantity]);

            } else {
                $isNewItem = true;
                $this->validator->validateCartLimits($cart, 1); // Check unique item limit
                $this->stockManager->validateStock($product, $validatedQuantity);
                $this->stockManager->reserveStock($product, $validatedQuantity); // Reserve stock for new item

                $cartItem = $this->cartRepository->createCartItem([ // استفاده از ریپازیتوری
                    'cart_id' => $cart->id,
                    'product_id' => $productId,
                    'quantity' => $validatedQuantity,
                    'price' => $product->price, // Use current product price
                ]);
                $this->eventDispatcher->dispatch(new \App\Events\CartItemAdded($cart, $product, $validatedQuantity));
                Log::info('New cart item added', ['cart_id' => $cart->id, 'product_id' => $productId, 'quantity' => $validatedQuantity]);
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id); // Clear cart cache on modification
            $this->metricsManager->recordMetric('addOrUpdateCartItem_duration', microtime(true) - $startTime, ['action' => ($isNewItem ? 'added' : 'updated')]);

            return CartOperationResponse::success( // استفاده از static constructor
                $isNewItem ? 'محصول با موفقیت به سبد خرید اضافه شد!' : 'تعداد محصول در سبد خرید به‌روزرسانی شد.',
                [
                    'product_id' => $productId,
                    'quantity' => $cartItem->quantity,
                    'cart_item_id' => $cartItem->id,
                ]
            );

        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Cart operation error during add/update cart item: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('addOrUpdateCartItem_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return CartOperationResponse::fail($e->getMessage(), $e->getCode()); // استفاده از static constructor
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during add/update cart item: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('addOrUpdateCartItem_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطای سیستمی در اضافه کردن/به‌روزرسانی محصول به سبد خرید.', 500); // استفاده از static constructor
        }
    }

    /**
     * Update specific cart item quantity.
     * تعداد یک آیتم خاص در سبد خرید را به‌روزرسانی می‌کند.
     *
     * @param CartItem $cartItem
     * @param int $newQuantity
     * @param User|null $user
     * @param string|null $sessionId
     * @return CartOperationResponse
     * @throws ProductNotFoundException
     * @throws InsufficientStockException
     * @throws UnauthorizedCartAccessException
     * @throws CartInvalidArgumentException
     * @throws CartOperationException
     */
    public function updateCartItemQuantity(
        CartItem $cartItem,
        int $newQuantity,
        ?User $user = null,
        ?string $sessionId = null
    ): CartOperationResponse {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($user, $sessionId);

        // Validate ownership
        if (!$this->userOwnsCartItem($cartItem, $user, $sessionId)) {
            Log::warning('Unauthorized attempt to update cart item', ['cart_item_id' => $cartItem->id, 'user_id' => $user?->id, 'session_id' => $sessionId]);
            throw new UnauthorizedCartAccessException();
        }

        DB::beginTransaction();
        try {
            $product = $this->productRepository->find($cartItem->product_id); // استفاده از ریپازیتوری
            if (!$product) {
                throw new ProductNotFoundException(); // Should not happen if data integrity is maintained
            }

            $validatedNewQuantity = $this->validator->validateQuantity($newQuantity);
            $oldQuantity = $cartItem->quantity;
            $quantityChange = $validatedNewQuantity - $oldQuantity;

            if ($validatedNewQuantity === 0) {
                // If new quantity is 0, remove the item
                $this->cartRepository->deleteCartItem($cartItem); // استفاده از ریپازیتوری
                $this->stockManager->releaseStock($product, $oldQuantity); // Release previously reserved stock
                $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cartItem->cart, $cartItem));
                Log::info('Cart item removed as quantity set to zero', ['cart_item_id' => $cartItem->id, 'product_id' => $product->id]);
                DB::commit();
                $this->cacheManager->clearCache($user, $sessionId);
                $this->metricsManager->recordMetric('updateCartItemQuantity_duration', microtime(true) - $startTime, ['action' => 'removed']);
                return CartOperationResponse::success('محصول از سبد خرید حذف شد.', ['product_id' => $product->id, 'cart_item_id' => $cartItem->id]); // استفاده از static constructor
            }

            // Validate stock for the *change*
            if ($quantityChange > 0) {
                $this->stockManager->validateStock($product, $quantityChange);
                $this->stockManager->reserveStock($product, $quantityChange);
            } elseif ($quantityChange < 0) {
                $this->stockManager->releaseStock($product, abs($quantityChange));
            }

            $this->cartRepository->updateCartItem($cartItem, ['quantity' => $validatedNewQuantity]); // استفاده از ریپازیتوری
            $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cartItem->cart, $cartItem, $oldQuantity, $validatedNewQuantity));
            Log::info('Cart item quantity updated', ['cart_item_id' => $cartItem->id, 'product_id' => $product->id, 'old_quantity' => $oldQuantity, 'new_quantity' => $validatedNewQuantity]);

            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId);
            $this->metricsManager->recordMetric('updateCartItemQuantity_duration', microtime(true) - $startTime, ['action' => 'updated']);
            return CartOperationResponse::success( // استفاده از static constructor
                'تعداد محصول در سبد خرید به‌روزرسانی شد.',
                [
                    'product_id' => $product->id,
                    'quantity' => $validatedNewQuantity,
                    'cart_item_id' => $cartItem->id,
                ]
            );

        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Cart operation error during update cart item quantity: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateCartItemQuantity_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return CartOperationResponse::fail($e->getMessage(), $e->getCode()); // استفاده از static constructor
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during update cart item quantity: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateCartItemQuantity_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطای سیستمی در به‌روزرسانی تعداد محصول.', 500); // استفاده از static constructor
        }
    }

    /**
     * Remove specific item from cart.
     * یک آیتم خاص را از سبد خرید حذف می‌کند.
     *
     * @param CartItem $cartItem
     * @param User|null $user
     * @param string|null $sessionId
     * @return CartOperationResponse
     * @throws UnauthorizedCartAccessException
     * @throws CartOperationException
     */
    public function removeCartItem(
        CartItem $cartItem,
        ?User $user = null,
        ?string $sessionId = null
    ): CartOperationResponse {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($user, $sessionId);

        // Validate ownership
        if (!$this->userOwnsCartItem($cartItem, $user, $sessionId)) {
            Log::warning('Unauthorized attempt to remove cart item', ['cart_item_id' => $cartItem->id, 'user_id' => $user?->id, 'session_id' => $sessionId]);
            throw new UnauthorizedCartAccessException();
        }

        DB::beginTransaction();
        try {
            $product = $this->productRepository->find($cartItem->product_id); // استفاده از ریپازیتوری
            if ($product) {
                $this->stockManager->releaseStock($product, $cartItem->quantity); // Release reserved stock
            } else {
                Log::warning('Product for cart item not found during removal. Stock not released.', ['cart_item_id' => $cartItem->id, 'product_id' => $cartItem->product_id]);
            }

            $this->cartRepository->deleteCartItem($cartItem); // استفاده از ریپازیتوری
            $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cartItem->cart, $cartItem));
            Log::info('Cart item removed successfully', ['cart_item_id' => $cartItem->id, 'product_id' => $cartItem->product_id]);

            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId);
            $this->metricsManager->recordMetric('removeCartItem_duration', microtime(true) - $startTime);
            return CartOperationResponse::success('محصول با موفقیت از سبد خرید حذف شد.', ['product_id' => $cartItem->product_id, 'cart_item_id' => $cartItem->id]); // استفاده از static constructor

        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Cart operation error removing cart item: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('removeCartItem_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e; // Re-throw the specific cart exception
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error removing cart item: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('removeCartItem_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطا در حذف محصول از سبد خرید.', 500); // استفاده از static constructor
        }
    }

    /**
     * Clear all items from cart and optionally delete the cart itself.
     * تمام آیتم‌ها را از سبد خرید پاک می‌کند و به صورت اختیاری خود سبد را حذف می‌کند.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     * @throws CartOperationException
     */
    public function clearCart(Cart $cart): CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        DB::beginTransaction();
        try {
            // Release reserved stock for all items
            foreach ($cart->items as $item) {
                $product = $this->productRepository->find($item->product_id); // استفاده از ریپازیتوری
                if ($product) {
                    $this->stockManager->releaseStock($product, $item->quantity);
                } else {
                    Log::warning('Product for cart item not found during cart clear. Stock not released.', ['cart_item_id' => $item->id, 'product_id' => $item->product_id]);
                }
            }

            $this->cartRepository->deleteCartItemsByProductIds($cart, $cart->items->pluck('product_id')->toArray()); // استفاده از ریپازیتوری
            Log::info('All items cleared from cart', ['cart_id' => $cart->id]);

            // Optionally delete the cart itself
            if (!config('cart.keep_cart_on_clear', false)) {
                $this->cartRepository->delete($cart); // استفاده از ریپازیتوری
                Log::info('Cart itself deleted after clearing.', ['cart_id' => $cart->id]);
            }

            $this->eventDispatcher->dispatch(new \App\Events\CartCleared($cart));
            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('clearCart_duration', microtime(true) - $startTime);
            return CartOperationResponse::success('سبد خرید با موفقیت پاکسازی شد.'); // استفاده از static constructor

        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Cart operation error clearing cart: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('clearCart_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e; // Re-throw the specific cart exception
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error clearing cart: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('clearCart_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطا در پاکسازی سبد خرید.', 500); // استفاده از static constructor
        }
    }

    /**
     * Get complete cart contents with calculations for display.
     * محتویات کامل سبد خرید را به همراه محاسبات برای نمایش دریافت می‌کند.
     *
     * @param Cart $cart
     * @return CartContentsResponse
     */
    public function getCartContents(Cart $cart): CartContentsResponse
    {
        $startTime = microtime(true);
        // Ensure items and products are loaded
        $cart->loadMissing('items.product');

        $itemsData = [];
        $totalQuantity = 0;
        $totalPrice = 0.0;

        foreach ($cart->items as $item) {
            if ($item->product) { // Ensure product is not null (e.g., if deleted)
                $subtotal = $item->quantity * $item->product->price; // Use current product price
                $itemsData[] = [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $subtotal,
                    'image' => $item->product->image, // Assuming product has an image attribute
                    'slug' => $item->product->slug, // Assuming product has a slug attribute
                ];
                $totalQuantity += $item->quantity;
                $totalPrice += $subtotal;
            } else {
                Log::warning('Product associated with cart item not found.', ['cart_item_id' => $item->id, 'product_id' => $item->product_id]);
                // Optionally remove this orphaned item from cart or mark it.
            }
        }

        $this->metricsManager->recordMetric('getCartContents_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);
        return new CartContentsResponse($itemsData, $totalQuantity, $totalPrice);
    }

    /**
     * Update multiple cart items in a single bulk operation.
     * چندین آیتم سبد خرید را در یک عملیات گروهی به‌روزرسانی می‌کند.
     *
     * @param Cart $cart
     * @param array $updates An associative array where keys are product IDs and values are quantities.
     * @return CartOperationResponse
     * @throws CartOperationException
     */
    public function updateMultipleItems(Cart $cart, array $updates): CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        if (count($updates) > config('cart.max_bulk_operations', 100)) { // استفاده از config()
            throw new CartInvalidArgumentException('تعداد عملیات به‌روزرسانی گروهی بیش از حد مجاز است.');
        }

        DB::beginTransaction();
        try {
            $currentCartItems = $cart->items->keyBy('product_id');
            $productsToFetch = array_keys($updates);
            $products = $this->productRepository->findByIds($productsToFetch)->keyBy('id'); // استفاده از ریپازیتوری

            $upsertData = [];
            $itemsToDelete = [];

            foreach ($updates as $productId => $newQuantity) {
                $product = $products->get($productId);
                if (!$product) {
                    Log::warning('Product not found during bulk update, skipping.', ['product_id' => $productId]);
                    continue; // Skip if product not found
                }

                $validatedQuantity = $this->validator->validateQuantity($newQuantity);
                $cartItem = $currentCartItems->get($productId);

                $oldQuantity = $cartItem ? $cartItem->quantity : 0;
                $quantityChange = $validatedQuantity - $oldQuantity;

                if ($validatedQuantity === 0) {
                    if ($cartItem) {
                        $itemsToDelete[] = $cartItem->id;
                        $this->stockManager->releaseStock($product, $oldQuantity);
                        $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cart, $cartItem));
                    }
                    continue; // Skip to next update if quantity is 0
                }

                // Stock validation and reservation/release for change
                if ($quantityChange > 0) {
                    $this->stockManager->validateStock($product, $quantityChange);
                    $this->stockManager->reserveStock($product, $quantityChange);
                } elseif ($quantityChange < 0) {
                    $this->stockManager->releaseStock($product, abs($quantityChange));
                }

                $upsertData[] = [
                    'cart_id' => $cart->id,
                    'product_id' => $productId,
                    'quantity' => $validatedQuantity,
                    'price' => $product->price, // Always use current price for consistency
                    'created_at' => $cartItem ? $cartItem->created_at : now(),
                    'updated_at' => now(),
                ];
            }

            // Perform upsert for updates/additions
            if (!empty($upsertData)) {
                $this->cartRepository->upsertCartItems($upsertData, ['cart_id', 'product_id'], ['quantity', 'price', 'updated_at']); // استفاده از ریپازیتوری
                // Dispatch events for upserted items (simplified for bulk)
                foreach ($upsertData as $itemData) {
                    $cartItem = $this->cartRepository->findCartItem($itemData['cart_id'], $itemData['product_id']); // Re-fetch to get updated model
                    if ($cartItem) {
                        $product = $products->get($itemData['product_id']);
                        if (array_key_exists($itemData['product_id'], $currentCartItems->toArray())) {
                            // This was an update
                            $oldQuantity = $currentCartItems[$itemData['product_id']]->quantity;
                            $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cart, $cartItem, $oldQuantity, $itemData['quantity']));
                        } else {
                            // This was an addition
                            $this->eventDispatcher->dispatch(new \App\Events\CartItemAdded($cart, $product, $itemData['quantity']));
                        }
                    }
                }
                Log::info('Bulk cart items upserted.', ['cart_id' => $cart->id, 'updates' => count($upsertData)]);
            }

            // Perform deletions
            if (!empty($itemsToDelete)) {
                $this->cartRepository->deleteCartItemsByProductIds($cart, array_keys($itemsToDelete)); // استفاده از ریپازیتوری
                Log::info('Bulk cart items deleted.', ['cart_id' => $cart->id, 'deletions' => count($itemsToDelete)]);
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('updateMultipleItems_duration', microtime(true) - $startTime, ['updates_count' => count($updates)]);
            return CartOperationResponse::success('سبد خرید با موفقیت به‌روزرسانی شد.'); // استفاده از static constructor

        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Cart operation error during bulk update: ' . $e->getMessage(), ['cart_id' => $cart->id, 'updates' => $updates, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateMultipleItems_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return CartOperationResponse::fail($e->getMessage(), $e->getCode()); // استفاده از static constructor
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during bulk cart update: ' . $e->getMessage(), ['cart_id' => $cart->id, 'updates' => $updates, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateMultipleItems_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطای سیستمی در به‌روزرسانی گروهی سبد خرید.', 500); // استفاده از static constructor
        }
    }


    /**
     * Reserve product stock for cart items (typically in a temporary cache).
     * موجودی محصول را برای آیتم‌های سبد خرید (معمولاً در یک کش موقت) رزرو می‌کند.
     *
     * @param Product $product
     * @param int $quantity
     * @param int|null $minutes The duration in minutes for which the stock should be reserved.
     * @return bool True if stock was successfully reserved, false otherwise.
     */
    public function reserveStock(Product $product, int $quantity, ?int $minutes = null): bool
    {
        return $this->stockManager->reserveStock($product, $quantity, $minutes);
    }

    /**
     * Release reserved product stock.
     * موجودی رزرو شده محصول را آزاد می‌کند.
     *
     * @param Product $product
     * @param int $quantity
     * @return bool True if stock was successfully released, false otherwise.
     */
    public function releaseStock(Product $product, int $quantity): bool
    {
        return $this->stockManager->releaseStock($product, $quantity);
    }

    /**
     * Clean up expired guest carts and release their stock.
     * سبدهای خرید مهمان منقضی شده را پاکسازی و موجودی آن‌ها را آزاد می‌کند.
     *
     * @param int|null $daysCutoff Number of days after which a cart is considered expired.
     * @return int The number of expired carts cleaned up.
     * @throws CartOperationException
     */
    public function cleanupExpiredCarts(?int $daysCutoff = null): int
    {
        $startTime = microtime(true);
        Log::info('Starting cleanup of expired guest carts.');

        DB::beginTransaction();
        try {
            $daysCutoff = $daysCutoff ?? config('cart.cleanup_days', 30); // استفاده از config()
            $cutoffDate = Carbon::now()->subDays($daysCutoff);

            $expiredCarts = $this->cartRepository->getExpiredGuestCarts($cutoffDate); // استفاده از ریپازیتوری
            $deletedCount = 0;

            foreach ($expiredCarts as $cart) {
                // Release stock for items in the expired cart
                foreach ($cart->items as $item) {
                    $product = $this->productRepository->find($item->product_id); // استفاده از ریپازیتوری
                    if ($product) {
                        $this->stockManager->releaseStock($product, $item->quantity);
                    } else {
                        Log::warning('Product for cart item not found during cleanup. Stock not released.', ['cart_id' => $cart->id, 'product_id' => $item->product_id]);
                    }
                    $this->cartRepository->deleteCartItem($item); // استفاده از ریپازیتوری
                }
                $this->cartRepository->delete($cart); // استفاده از ریپازیتوری
                $this->cacheManager->clearCache(null, $cart->session_id); // Clear cache for expired guest cart
                $deletedCount++;
                Log::info('Cleaned up expired guest cart', ['cart_id' => $cart->id, 'session_id' => $cart->session_id]);
            }

            DB::commit();
            $this->metricsManager->recordMetric('cleanupExpiredCarts_duration', microtime(true) - $startTime, ['cleaned_carts' => $deletedCount]);
            Log::info("Finished cleaning up {$deletedCount} expired guest carts.");
        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Cart operation error during cleanupExpiredCarts: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('cleanupExpiredCarts_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e; // Re-throw the specific cart exception
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during cleanupExpiredCarts: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('cleanupExpiredCarts_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در پاکسازی سبدهای خرید منقضی شده.', 0, $e);
        }
        return $deletedCount;
    }

    /**
     * Calculates the total number of items in the cart.
     *
     * @param Cart|null $cartObject
     * @param User|null $user
     * @param string|null $sessionId
     * @return int
     */
    private function calculateTotalItems(?Cart $cartObject, ?User $user, ?string $sessionId): int
    {
        try {
            $cart = $cartObject ?? $this->getOrCreateCart($user, $sessionId);
            // Ensure items are loaded from the database for fresh calculation
            $cart->loadMissing('items');
            return $cart->items->sum('quantity');
        } catch (BaseCartException $e) { // Catch custom cart exceptions
            Log::error('Error calculating total items: ' . $e->getMessage(), [
                'user_id' => $user?->id ?? 'guest',
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return 0;
        } catch (\Throwable $e) {
            Log::error('Unexpected error calculating total items: ' . $e->getMessage(), [
                'user_id' => $user?->id ?? 'guest',
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Checks if a user or session owns a specific cart item.
     * بهبود: متد جدید برای بررسی مالکیت آیتم سبد خرید.
     *
     * @param \App\Models\CartItem $cartItem
     * @param User|null $user
     * @param string|null $sessionId
     * @return bool
     */
    public function userOwnsCartItem(CartItem $cartItem, ?User $user, ?string $sessionId): bool
    {
        // Ensure cart relationship is loaded
        if (!$cartItem->relationLoaded('cart')) {
            $cartItem->load('cart');
        }
        $cart = $cartItem->cart;

        if (!$cart) {
            return false; // Cart not found for item
        }

        if ($user && $cart->user_id === $user->id) {
            return true;
        }

        if ($sessionId && $cart->session_id === $sessionId) {
            return true;
        }

        return false;
    }

    /**
     * Get a cart by its ID with ownership validation.
     * سبد خرید را بر اساس شناسه آن و با اعتبارسنجی مالکیت دریافت می‌کند.
     *
     * @param int $cartId
     * @param User|null $user
     * @param string|null $sessionId
     * @return Cart|null The cart object if found and owned, null otherwise.
     * @throws UnauthorizedCartAccessException
     * @throws CartInvalidArgumentException
     */
    public function getCartById(int $cartId, ?User $user = null, ?string $sessionId = null): ?Cart
    {
        $this->validator->validateUserOrSession($user, $sessionId);

        $cart = $this->cartRepository->findCartWithItems($cartId); // استفاده از ریپازیتوری

        if (!$cart) {
            return null; // Cart not found
        }

        // Validate ownership
        if ($user && $cart->user_id === $user->id) {
            return $cart;
        }

        if ($sessionId && $cart->session_id === $sessionId) {
            return $cart;
        }

        Log::warning('Unauthorized access attempt to cart', ['cart_id' => $cartId, 'user_id' => $user?->id, 'session_id' => $sessionId]);
        throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این سبد خرید را ندارید.');
    }

    /**
     * Calculate cart totals, including subtotal, shipping, taxes, and discounts.
     * مجموع سبد خرید، شامل زیرمجموعه، هزینه ارسال، مالیات و تخفیفات را محاسبه می‌کند.
     * (منطق پیچیده مالی و تخفیف می‌تواند به یک Manager جداگانه منتقل شود)
     *
     * @param Cart $cart
     * @return array An associative array of calculated totals.
     */
    public function calculateCartTotals(Cart $cart): array
    {
        $startTime = microtime(true);
        $cart->loadMissing('items.product'); // Ensure items and products are loaded

        $subtotal = 0.0;
        $totalItems = 0;

        foreach ($cart->items as $item) {
            if ($item->product) {
                $subtotal += $item->quantity * $item->product->price;
                $totalItems += $item->quantity;
            }
        }

        // Dummy calculations for shipping and tax.
        // In a real application, these would involve complex logic (e.g., based on address, product type)
        // that should ideally be delegated to dedicated services/managers.
        $shippingCost = $totalItems > 0 ? 15.00 : 0.00; // Example fixed shipping
        $taxRate = 0.09; // Example 9% tax
        $taxes = $subtotal * $taxRate;
        $discount = 0.0; // Placeholder for future coupon/discount logic

        $finalTotal = $subtotal + $shippingCost + $taxes - $discount;

        $this->metricsManager->recordMetric('calculateCartTotals_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);
        return [
            'subtotal' => round($subtotal, 2),
            'total_items' => $totalItems,
            'shipping_cost' => round($shippingCost, 2),
            'taxes' => round($taxes, 2),
            'discount' => round($discount, 2),
            'final_total' => round($finalTotal, 2),
        ];
    }

    /**
     * Validate cart items for availability, stock, and current prices.
     * آیتم‌های سبد خرید را از نظر در دسترس بودن، موجودی و قیمت‌های فعلی اعتبارسنجی می‌کند.
     *
     * @param Cart $cart
     * @return array An array of validation results (e.g., items with issues).
     */
    public function validateCartItems(Cart $cart): array
    {
        $startTime = microtime(true);
        $cart->loadMissing('items.product');
        $issues = [];

        foreach ($cart->items as $item) {
            if (!$item->product) {
                $issues[] = [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'issue' => 'محصول یافت نشد یا حذف شده است.',
                    'code' => 'product_missing',
                ];
                continue;
            }

            // Check current price vs. price when added (if stored in cart_items)
            // if ($item->price !== $item->product->price) {
            //     $issues[] = [
            //         'cart_item_id' => $item->id,
            //         'product_id' => $item->product_id,
            //         'issue' => 'قیمت محصول تغییر کرده است. قیمت قبلی: ' . $item->price . '، قیمت فعلی: ' . $item->product->price,
            //         'code' => 'price_changed',
            //         'old_price' => $item->price,
            //         'current_price' => $item->product->price,
            //     ];
            // }

            // Check stock
            try {
                $this->stockManager->validateStock($item->product, $item->quantity);
            } catch (InsufficientStockException $e) {
                $issues[] = [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'issue' => $e->getMessage(),
                    'code' => 'insufficient_stock',
                    'available_stock' => $item->product->stock - $this->stockManager->getReservedStock($item->product->id),
                    'requested_quantity' => $item->quantity,
                ];
            }
        }

        $this->metricsManager->recordMetric('validateCartItems_duration', microtime(true) - $startTime, ['cart_id' => $cart->id, 'issues_count' => count($issues)]);
        return $issues;
    }

    /**
     * Apply a coupon/discount code to the cart.
     * (این متد در حال حاضر فقط یک استاب است و نیاز به منطق پیاده‌سازی کوپن دارد)
     *
     * @param Cart $cart
     * @param string $couponCode
     * @return CartOperationResponse
     */
    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse
    {
        // TODO: Implement coupon application logic. This would likely involve a CouponManager or a dedicated service.
        // It should validate the coupon, apply the discount to the cart model (e.g., store coupon_code and discount_amount),
        // and re-calculate totals.
        Log::info('Attempting to apply coupon', ['cart_id' => $cart->id, 'coupon_code' => $couponCode]);
        $this->metricsManager->recordMetric('applyCoupon_call', 1, ['cart_id' => $cart->id]);

        // For now, it's just a placeholder.
        if ($couponCode === 'DISCOUNT10') {
            return CartOperationResponse::success('کد تخفیف با موفقیت اعمال شد. (فقط برای تست)', ['coupon_code' => $couponCode, 'discount' => 10.00]);
        }
        return CartOperationResponse::fail('کد تخفیف نامعتبر است.', 400);
    }

    /**
     * Remove an applied coupon from the cart.
     * (این متد در حال حاضر فقط یک استاب است)
     *
     * @param Cart $cart
     * @return CartOperationResponse
     */
    public function removeCoupon(Cart $cart): CartOperationResponse
    {
        // TODO: Implement coupon removal logic.
        Log::info('Attempting to remove coupon', ['cart_id' => $cart->id]);
        $this->metricsManager->recordMetric('removeCoupon_call', 1, ['cart_id' => $cart->id]);
        return CartOperationResponse::success('کد تخفیف با موفقیت حذف شد. (فقط برای تست)');
    }

    /**
     * Get the total count of unique items in the cart.
     * تعداد کل آیتم‌های منحصر به فرد در سبد خرید را دریافت می‌کند.
     *
     * @param Cart $cart
     * @return int
     */
    public function getCartItemCount(Cart $cart): int
    {
        $startTime = microtime(true);
        $cart->loadMissing('items'); // Ensure items are loaded
        $count = $cart->items->count();
        $this->metricsManager->recordMetric('getCartItemCount_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);
        return $count;
    }

    /**
     * Transfer cart ownership from one user/session to another user.
     * مالکیت سبد خرید را از یک کاربر/سشن به کاربر دیگری منتقل می‌کند.
     * (این متد برای سناریوهایی که یک سبد خرید نیاز به تغییر مالکیت دارد، مفید است)
     *
     * @param Cart $cart The cart to transfer.
     * @param User $newOwner The new user to whom the cart will be assigned.
     * @return bool True on successful transfer, false otherwise.
     * @throws CartOperationException
     */
    public function transferCartOwnership(Cart $cart, User $newOwner): bool
    {
        $startTime = microtime(true);
        Log::info('Attempting to transfer cart ownership', ['cart_id' => $cart->id, 'current_user_id' => $cart->user_id, 'current_session_id' => $cart->session_id, 'new_owner_id' => $newOwner->id]);

        DB::beginTransaction();
        try {
            // Check if new owner already has a cart
            $existingNewOwnerCart = $this->cartRepository->findByUserId($newOwner->id); // استفاده از ریپازیتوری
            if ($existingNewOwnerCart) {
                // If new owner has a cart, merge current cart into it
                Log::info('New owner already has a cart, merging current cart into new owner\'s cart.', ['new_owner_id' => $newOwner->id, 'existing_cart_id' => $existingNewOwnerCart->id]);
                DB::rollBack();
                throw new CartOperationException('کاربر جدید از قبل سبد خرید دارد. از متد ادغام استفاده کنید.');
            }

            $cart->user_id = $newOwner->id;
            $cart->session_id = null; // Clear session ID as it's now owned by a user
            $this->cartRepository->save($cart); // استفاده از ریپازیتوری

            $this->cacheManager->clearCache($newOwner); // Clear cache for new owner
            $this->cacheManager->clearCache($cart->user, $cart->session_id); // Clear cache for old owner/session

            Log::info('Cart ownership transferred successfully', ['cart_id' => $cart->id, 'new_owner_id' => $newOwner->id]);
            DB::commit();
            $this->metricsManager->recordMetric('transferCartOwnership_duration', microtime(true) - $startTime);
            return true;
        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Cart operation error transferring cart ownership: ' . $e->getMessage(), ['cart_id' => $cart->id, 'new_owner_id' => $newOwner->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('transferCartOwnership_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e; // Re-throw the specific cart exception
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error transferring cart ownership: ' . $e->getMessage(), ['cart_id' => $cart->id, 'new_owner_id' => $newOwner->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('transferCartOwnership_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در انتقال مالکیت سبد خرید.', 0, $e);
        }
    }

    /**
     * Check if the cart is empty (contains no items).
     * بررسی می‌کند که آیا سبد خرید خالی است (هیچ آیتمی ندارد).
     *
     * @param Cart $cart
     * @return bool
     */
    public function isCartEmpty(Cart $cart): bool
    {
        $startTime = microtime(true);
        $cart->loadMissing('items'); // Ensure items are loaded
        $isEmpty = $cart->items->isEmpty();
        $this->metricsManager->recordMetric('isCartEmpty_duration', microtime(true) - $startTime, ['cart_id' => $cart->id, 'is_empty' => $isEmpty]);
        return $isEmpty;
    }

    /**
     * Get the estimated expiry date/time for a guest cart.
     * تاریخ/زمان انقضای تخمینی برای یک سبد خرید مهمان را دریافت می‌کند.
     *
     * @param Cart $cart
     * @return Carbon|null The expiry date/time, or null if not applicable (e.g., for user carts).
     */
    public function getCartExpiryDate(Cart $cart): ?Carbon
    {
        if ($cart->user_id !== null) {
            return null; // User carts typically don't expire based on time
        }

        $cleanupDays = config('cart.cleanup_days', 30); // استفاده از config()
        return $cart->updated_at?->addDays($cleanupDays);
    }

    /**
     * Refresh cart item prices from current product prices in the database.
     * قیمت آیتم‌های سبد خرید را بر اساس قیمت‌های فعلی محصولات در دیتابیس به‌روزرسانی می‌کند.
     *
     * @param Cart $cart
     * @return CartOperationResponse
     * @throws CartOperationException
     */
    public function refreshCartItemPrices(Cart $cart): CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        DB::beginTransaction();
        try {
            $cart->loadMissing('items'); // Load existing items
            $productIds = $cart->items->pluck('product_id')->unique()->toArray();
            $products = $this->productRepository->findByIds($productIds)->keyBy('id'); // استفاده از ریپازیتوری

            $itemsUpdated = 0;
            foreach ($cart->items as $cartItem) {
                $product = $products->get($cartItem->product_id);
                if ($product && $cartItem->price !== $product->price) {
                    $this->cartRepository->updateCartItem($cartItem, ['price' => $product->price]); // استفاده از ریپازیتوری
                    $itemsUpdated++;
                    Log::info('Cart item price refreshed', ['cart_item_id' => $cartItem->id, 'old_price' => $cartItem->price, 'new_price' => $product->price]);
                }
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('refreshCartItemPrices_duration', microtime(true) - $startTime, ['cart_id' => $cart->id, 'items_updated' => $itemsUpdated]);
            return CartOperationResponse::success("قیمت {$itemsUpdated} آیتم در سبد خرید به‌روزرسانی شد."); // استفاده از static constructor

        } catch (BaseCartException $e) { // Catch custom cart exceptions
            DB::rollBack();
            Log::error('Cart operation error refreshing cart item prices: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('refreshCartItemPrices_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e; // Re-throw the specific cart exception
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error refreshing cart item prices: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('refreshCartItemPrices_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطا در به‌روزرسانی قیمت آیتم‌های سبد خرید.', 500); // استفاده از static constructor
        }
    }
}
