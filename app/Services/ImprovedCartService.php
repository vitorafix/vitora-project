<?php

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
use App\Services\Contracts\CartServiceInterface; // تصحیح شد: استفاده از فضای نام صحیح
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;

// Managers
use App\Services\Managers\CartCacheManager;
use App\Services\Managers\StockManager;
use App\Services\Managers\CartValidator;
use App\Services\Managers\CartRateLimiter;
use App\Services\Managers\CartMetricsManager;

// Responses
use App\Services\Responses\CartOperationResponse;
use App\Services\Responses\CartContentsResponse;

// Custom Exceptions
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\UnauthorizedCartAccessException;
use App\Exceptions\CartOperationException;
use App\Exceptions\CartInvalidArgumentException;
use App\Exceptions\CartLimitExceededException;
use App\Exceptions\BaseCartException;

// Events (شما باید این کلاس‌ها را در App\Events/ ایجاد کنید)
// namespace App\Events;
// class CartItemAdded { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public Product $product; public int $quantity; public function __construct(Cart $cart, Product $product, int $quantity) { $this->cart = $cart; public int $quantity; public function __construct(Cart $cart, Product $product, int $quantity) { $this->cart = $cart; $this->product = $product; $this->quantity = $quantity; } }
// class CartItemUpdated { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public CartItem $cartItem; public int $oldQuantity; public int $newQuantity; public function __construct(Cart $cart, CartItem $cartItem, int $oldQuantity, int $newQuantity) { $this->cart = $cart; public CartItem $cartItem; public int $oldQuantity; public int $newQuantity; public function __construct(Cart $cart, CartItem $cartItem, int $oldQuantity, int $newQuantity) { $this->cart = $cart; $this->cartItem = $cartItem; $this->oldQuantity = $oldQuantity; $this->newQuantity = $newQuantity; } }
// class CartItemRemoved { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public CartItem $cartItem; public function __construct(Cart $cart, CartItem $cartItem) { $this->cart = $cart; public CartItem $cartItem; public function __construct(Cart $cart, CartItem $cartItem) { $this->cart = $cart; $this->cartItem = $cartItem; } }
// class CartCleared { use \Illuminate\Foundation\Events\Dispatchable; public Cart $cart; public function __construct(Cart $cart) { $this->cart = $cart; } }
// class CartMerged { use \Illuminate\Foundation\Events\Dispatchable; public Cart $fromCart; public Cart $toCart; public User $user; public function __construct(Cart $fromCart, Cart $toCart, User $user) { $this->fromCart = $fromCart; $this->toCart = $toCart; $this->user = $user; } }


class ImprovedCartService implements CartServiceInterface
{
    protected CartRepositoryInterface $cartRepository;
    protected ProductRepositoryInterface $productRepository;
    protected CartCacheManager $cacheManager;
    protected StockManager $stockManager;
    protected CartValidator $validator;
    protected CartRateLimiter $rateLimiter;
    protected CartMetricsManager $metricsManager;
    protected Dispatcher $eventDispatcher;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ProductRepositoryInterface $productRepository,
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
    }

    private function getConfig(string $key, $default = null): mixed
    {
        return config("cart.{$key}", $default);
    }

    public function getOrCreateCart(?User $user = null, ?string $sessionId = null): Cart
    {
        $startTime = microtime(true);
        $this->validator->validateUserOrSession($user, $sessionId);

        $cacheKey = $this->cacheManager->getCacheKey($user, $sessionId);

        $cart = $this->cacheManager->remember($cacheKey, function () use ($user, $sessionId) {
            $cart = null;
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
            return $cart;
        });

        if ($cart && !$cart->relationLoaded('items')) {
            $cart->load('items.product');
        }

        $this->metricsManager->recordMetric('getOrCreateCart_duration', microtime(true) - $startTime, ['user_id' => $user?->id, 'session_id' => $sessionId]);
        return $cart;
    }

    public function mergeGuestCart(User $user, string $guestSessionId): void
    {
        $startTime = microtime(true);
        Log::info('Attempting to merge guest cart with user cart', ['user_id' => $user->id, 'guest_session_id' => $guestSessionId]);

        DB::beginTransaction();
        try {
            $guestCart = $this->cartRepository->findBySessionId($guestSessionId);

            if (!$guestCart || $guestCart->items->isEmpty()) {
                Log::info('No guest cart or empty guest cart to merge.', ['guest_session_id' => $guestSessionId]);
                DB::commit();
                return;
            }

            $userCart = $this->cartRepository->findByUserId($user->id);

            if (!$userCart) {
                $guestCart->user_id = $user->id;
                $guestCart->session_id = null;
                $this->cartRepository->save($guestCart);
                Log::info('Guest cart assigned to new user as they had no existing cart.', ['user_id' => $user->id, 'guest_cart_id' => $guestCart->id]);
                $this->cacheManager->clearCache($user);
                $this->cacheManager->clearCache(null, $guestSessionId);
                $this->eventDispatcher->dispatch(new \App\Events\CartMerged($guestCart, $guestCart, $user));
                DB::commit();
                $this->metricsManager->recordMetric('mergeGuestCart_duration', microtime(true) - $startTime, ['action' => 'assigned']);
                return;
            }

            $guestCartItems = $guestCart->items->keyBy('product_id');
            $userCartItems = $userCart->items->keyBy('product_id');
            $itemsToUpdate = [];
            $itemsToCreate = [];

            foreach ($guestCartItems as $productId => $guestItem) {
                if ($userCartItems->has($productId)) {
                    $existingUserItem = $userCartItems[$productId];
                    $newQuantity = $existingUserItem->quantity + $guestItem->quantity;

                    try {
                        $product = $this->productRepository->find($productId);
                        $this->stockManager->validateStock($product, $newQuantity);
                        $this->validator->validateQuantity($newQuantity);

                        $itemsToUpdate[] = [
                            'id' => $existingUserItem->id,
                            'cart_id' => $userCart->id,
                            'product_id' => $productId,
                            'quantity' => $newQuantity,
                            'price' => $product->price,
                            'created_at' => $existingUserItem->created_at,
                            'updated_at' => now(),
                        ];
                    } catch (InsufficientStockException | CartInvalidArgumentException $e) {
                        Log::warning('Skipping merge for product due to validation error: ' . $e->getMessage(), ['product_id' => $productId]);
                    }
                } else {
                    try {
                        $product = $this->productRepository->find($productId);
                        $this->stockManager->validateStock($product, $guestItem->quantity);
                        $this->validator->validateQuantity($guestItem->quantity);

                        $itemsToCreate[] = [
                            'cart_id' => $userCart->id,
                            'product_id' => $guestItem->product_id,
                            'quantity' => $guestItem->quantity,
                            'price' => $product->price,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    } catch (InsufficientStockException | CartInvalidArgumentException $e) {
                        Log::warning('Skipping merge for new product due to validation error: ' . $e->getMessage(), ['product_id' => $productId]);
                    }
                }
            }

            $allUpsertItems = array_merge($itemsToUpdate, $itemsToCreate);
            if (!empty($allUpsertItems)) {
                $this->cartRepository->upsertCartItems($allUpsertItems, ['cart_id', 'product_id'], ['quantity', 'price']);
            }

            $this->cartRepository->delete($guestCart);
            Log::info('Guest cart merged and deleted.', ['guest_cart_id' => $guestCart->id, 'user_cart_id' => $userCart->id]);

            $this->cacheManager->clearCache($user);
            $this->cacheManager->clearCache(null, $guestSessionId);
            $this->eventDispatcher->dispatch(new \App\Events\CartMerged($guestCart, $userCart, $user));

            DB::commit();
            $this->metricsManager->recordMetric('mergeGuestCart_duration', microtime(true) - $startTime, ['action' => 'merged']);

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Error merging guest cart: ' . $e->getMessage(), ['user_id' => $user->id, 'guest_session_id' => $guestSessionId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('mergeGuestCart_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error merging guest cart: ' . $e->getMessage(), ['user_id' => $user->id, 'guest_session_id' => $guestSessionId, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('mergeGuestCart_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در ادغام سبد خرید مهمان.', 0, $e);
        }
    }

    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void
    {
        $startTime = microtime(true);
        Log::info('Attempting to assign guest cart to new user', ['guest_session_id' => $guestSessionId, 'new_user_id' => $newUser->id]);

        DB::beginTransaction();
        try {
            $guestCart = $this->cartRepository->findBySessionId($guestSessionId);

            if ($guestCart) {
                $existingUserCart = $this->cartRepository->findByUserId($newUser->id);
                if ($existingUserCart) {
                    Log::warning('New user already has a cart, merging instead of assigning.', ['new_user_id' => $newUser->id, 'existing_cart_id' => $existingUserCart->id]);
                    DB::rollBack();
                    $this->mergeGuestCart($newUser, $guestSessionId);
                    return;
                }

                $guestCart->user_id = $newUser->id;
                $guestCart->session_id = null;
                $this->cartRepository->save($guestCart);
                Log::info('Guest cart assigned to new user successfully', ['guest_cart_id' => $guestCart->id, 'new_user_id' => $newUser->id]);

                $this->cacheManager->clearCache($newUser);
                $this->cacheManager->clearCache(null, $guestSessionId);

                $this->eventDispatcher->dispatch(new \App\Events\CartMerged($guestCart, $guestCart, $newUser));
            } else {
                Log::info('No guest cart found for assignment.', ['guest_session_id' => $guestSessionId]);
            }

            DB::commit();
            $this->metricsManager->recordMetric('assignGuestCartToNewUser_duration', microtime(true) - $startTime, ['user_id' => $newUser->id]);
        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Error assigning guest cart to new user: ' . $e->getMessage(), ['guest_session_id' => $guestSessionId, 'new_user_id' => $newUser->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('assignGuestCartToNewUser_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error assigning guest cart to new user: ' . $e->getMessage(), ['guest_session_id' => $guestSessionId, 'new_user_id' => $newUser->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('assignGuestCartToNewUser_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در اختصاص سبد خرید مهمان به کاربر جدید.', 0, $e);
        }
    }

    public function addOrUpdateCartItem(Cart $cart, int $productId, int $quantity): CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        DB::beginTransaction();
        try {
            $product = $this->productRepository->find($productId);
            if (!$product) {
                throw new ProductNotFoundException();
            }

            $validatedQuantity = $this->validator->validateQuantity($quantity);

            $cartItem = $this->cartRepository->findCartItem($cart->id, $productId);
            $oldQuantity = 0;
            $isNewItem = false;

            if ($cartItem) {
                $oldQuantity = $cartItem->quantity;
                $newQuantity = $validatedQuantity;
                $quantityChange = $newQuantity - $oldQuantity;

                if ($newQuantity === 0) {
                    $this->cartRepository->deleteCartItem($cartItem);
                    $this->stockManager->releaseStock($product, $oldQuantity);
                    $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cart, $cartItem));
                    Log::info('Cart item removed because new quantity is zero.', ['cart_id' => $cart->id, 'product_id' => $productId]);
                    DB::commit();
                    $this->cacheManager->clearCache($cart->user, $cart->session_id);
                    $this->metricsManager->recordMetric('addOrUpdateCartItem_duration', microtime(true) - $startTime, ['action' => 'removed_by_zero_quantity']);
                    return CartOperationResponse::success('محصول از سبد خرید حذف شد.', ['product_id' => $productId]);
                }

                if ($quantityChange > 0) {
                    $this->stockManager->validateStock($product, $quantityChange);
                    $this->stockManager->reserveStock($product, $quantityChange);
                } elseif ($quantityChange < 0) {
                    $this->stockManager->releaseStock($product, abs($quantityChange));
                }

                $this->cartRepository->updateCartItem($cartItem, ['quantity' => $newQuantity]);
                $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cart, $cartItem, $oldQuantity, $newQuantity));
                Log::info('Cart item quantity updated', ['cart_id' => $cart->id, 'product_id' => $productId, 'old_quantity' => $oldQuantity, 'new_quantity' => $newQuantity]);

            } else {
                $isNewItem = true;
                $this->validator->validateCartLimits($cart, 1);
                $this->stockManager->validateStock($product, $validatedQuantity);
                $this->stockManager->reserveStock($product, $validatedQuantity);

                $cartItem = $this->cartRepository->createCartItem([
                    'cart_id' => $cart->id,
                    'product_id' => $productId,
                    'quantity' => $validatedQuantity,
                    'price' => $product->price,
                ]);
                $this->eventDispatcher->dispatch(new \App\Events\CartItemAdded($cart, $product, $validatedQuantity));
                Log::info('New cart item added', ['cart_id' => $cart->id, 'product_id' => $productId, 'quantity' => $validatedQuantity]);
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('addOrUpdateCartItem_duration', microtime(true) - $startTime, ['action' => ($isNewItem ? 'added' : 'updated')]);

            return CartOperationResponse::success(
                $isNewItem ? 'محصول با موفقیت به سبد خرید اضافه شد!' : 'تعداد محصول در سبد خرید به‌روزرسانی شد.',
                [
                    'product_id' => $productId,
                    'quantity' => $cartItem->quantity,
                    'cart_item_id' => $cartItem->id,
                ]
            );

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error during add/update cart item: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('addOrUpdateCartItem_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return CartOperationResponse::fail($e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during add/update cart item: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('addOrUpdateCartItem_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطای سیستمی در اضافه کردن/به‌روزرسانی محصول به سبد خرید.', 500);
        }
    }

    public function updateCartItemQuantity(
        CartItem $cartItem,
        int $newQuantity,
        ?User $user = null,
        ?string $sessionId = null
    ): CartOperationResponse {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($user, $sessionId);

        if (!$this->userOwnsCartItem($cartItem, $user, $sessionId)) {
            Log::warning('Unauthorized attempt to update cart item', ['cart_item_id' => $cartItem->id, 'user_id' => $user?->id, 'session_id' => $sessionId]);
            throw new UnauthorizedCartAccessException();
        }

        DB::beginTransaction();
        try {
            $product = $this->productRepository->find($cartItem->product_id);
            if (!$product) {
                throw new ProductNotFoundException();
            }

            $validatedNewQuantity = $this->validator->validateQuantity($newQuantity);
            $oldQuantity = $cartItem->quantity;
            $quantityChange = $validatedNewQuantity - $oldQuantity;

            if ($validatedNewQuantity === 0) {
                $this->cartRepository->deleteCartItem($cartItem);
                $this->stockManager->releaseStock($product, $oldQuantity);
                $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cartItem->cart, $cartItem));
                Log::info('Cart item removed as quantity set to zero', ['cart_item_id' => $cartItem->id, 'product_id' => $product->id]);
                DB::commit();
                $this->cacheManager->clearCache($user, $sessionId);
                $this->metricsManager->recordMetric('updateCartItemQuantity_duration', microtime(true) - $startTime, ['action' => 'removed']);
                return CartOperationResponse::success('محصول از سبد خرید حذف شد.', ['product_id' => $product->id, 'cart_item_id' => $cartItem->id]);
            }

            if ($quantityChange > 0) {
                $this->stockManager->validateStock($product, $quantityChange);
                $this->stockManager->reserveStock($product, $quantityChange);
            } elseif ($quantityChange < 0) {
                $this->stockManager->releaseStock($product, abs($quantityChange));
            }

            $this->cartRepository->updateCartItem($cartItem, ['quantity' => $validatedNewQuantity]);
            $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cartItem->cart, $cartItem, $oldQuantity, $validatedNewQuantity));
            Log::info('Cart item quantity updated', ['cart_item_id' => $cartItem->id, 'product_id' => $product->id, 'old_quantity' => $oldQuantity, 'new_quantity' => $validatedNewQuantity]);

            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId);
            $this->metricsManager->recordMetric('updateCartItemQuantity_duration', microtime(true) - $startTime, ['action' => 'updated']);
            return CartOperationResponse::success(
                'تعداد محصول در سبد خرید به‌روزرسانی شد.',
                [
                    'product_id' => $product->id,
                    'quantity' => $validatedNewQuantity,
                    'cart_item_id' => $cartItem->id,
                ]
            );

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error during update cart item quantity: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateCartItemQuantity_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return CartOperationResponse::fail($e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during update cart item quantity: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateCartItemQuantity_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطای سیستمی در به‌روزرسانی تعداد محصول.', 500);
        }
    }

    public function removeCartItem(
        CartItem $cartItem,
        ?User $user = null,
        ?string $sessionId = null
    ): CartOperationResponse {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($user, $sessionId);

        if (!$this->userOwnsCartItem($cartItem, $user, $sessionId)) {
            Log::warning('Unauthorized attempt to remove cart item', ['cart_item_id' => $cartItem->id, 'user_id' => $user?->id, 'session_id' => $sessionId]);
            throw new UnauthorizedCartAccessException();
        }

        DB::beginTransaction();
        try {
            $product = $this->productRepository->find($cartItem->product_id);
            if ($product) {
                $this->stockManager->releaseStock($product, $cartItem->quantity);
            } else {
                Log::warning('Product for cart item not found during removal. Stock not released.', ['cart_item_id' => $cartItem->id, 'product_id' => $cartItem->product_id]);
            }

            $this->cartRepository->deleteCartItem($cartItem);
            $this->eventDispatcher->dispatch(new \App\Events\CartItemRemoved($cartItem->cart, $cartItem));
            Log::info('Cart item removed successfully', ['cart_item_id' => $cartItem->id, 'product_id' => $cartItem->product_id]);

            DB::commit();
            $this->cacheManager->clearCache($user, $sessionId);
            $this->metricsManager->recordMetric('removeCartItem_duration', microtime(true) - $startTime);
            return CartOperationResponse::success('محصول با موفقیت از سبد خرید حذف شد.', ['product_id' => $cartItem->product_id, 'cart_item_id' => $cartItem->id]);

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error removing cart item: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('removeCartItem_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error removing cart item: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('removeCartItem_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطا در حذف محصول از سبد خرید.', 500);
        }
    }

    public function clearCart(Cart $cart): CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        DB::beginTransaction();
        try {
            foreach ($cart->items as $item) {
                $product = $this->productRepository->find($item->product_id);
                if ($product) {
                    $this->stockManager->releaseStock($product, $item->quantity);
                } else {
                    Log::warning('Product for cart item not found during cart clear. Stock not released.', ['cart_item_id' => $item->id, 'product_id' => $item->product_id]);
                }
            }

            $this->cartRepository->deleteCartItemsByProductIds($cart, $cart->items->pluck('product_id')->toArray());
            Log::info('All items cleared from cart', ['cart_id' => $cart->id]);

            if (!config('cart.keep_cart_on_clear', false)) {
                $this->cartRepository->delete($cart);
                Log::info('Cart itself deleted after clearing.', ['cart_id' => $cart->id]);
            }

            $this->eventDispatcher->dispatch(new \App\Events\CartCleared($cart));
            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('clearCart_duration', microtime(true) - $startTime);
            return CartOperationResponse::success('سبد خرید با موفقیت پاکسازی شد.');

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error clearing cart: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('clearCart_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error clearing cart: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('clearCart_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطا در پاکسازی سبد خرید.', 500);
        }
    }

    public function getCartContents(Cart $cart): CartContentsResponse
    {
        $startTime = microtime(true);
        $cart->loadMissing('items.product');

        $itemsData = [];
        $totalQuantity = 0;
        $totalPrice = 0.0;

        foreach ($cart->items as $item) {
            if ($item->product) {
                $subtotal = $item->quantity * $item->product->price;
                $itemsData[] = [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $subtotal,
                    'image' => $item->product->image,
                    'slug' => $item->product->slug,
                ];
                $totalQuantity += $item->quantity;
                $totalPrice += $subtotal;
            } else {
                Log::warning('Product associated with cart item not found.', ['cart_item_id' => $item->id, 'product_id' => $item->product_id]);
            }
        }

        $this->metricsManager->recordMetric('getCartContents_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);
        return new CartContentsResponse($itemsData, $totalQuantity, $totalPrice);
    }

    public function updateMultipleItems(Cart $cart, array $updates): CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        if (count($updates) > config('cart.max_bulk_operations', 100)) {
            throw new CartInvalidArgumentException('تعداد عملیات به‌روزرسانی گروهی بیش از حد مجاز است.');
        }

        DB::beginTransaction();
        try {
            $currentCartItems = $cart->items->keyBy('product_id');
            $productsToFetch = array_keys($updates);
            $products = $this->productRepository->findByIds($productsToFetch)->keyBy('id');

            $upsertData = [];
            $itemsToDelete = [];

            foreach ($updates as $productId => $newQuantity) {
                $product = $products->get($productId);
                if (!$product) {
                    Log::warning('Product not found during bulk update, skipping.', ['product_id' => $productId]);
                    continue;
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
                    continue;
                }

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
                    'price' => $product->price,
                    'created_at' => $cartItem ? $cartItem->created_at : now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($upsertData)) {
                $this->cartRepository->upsertCartItems($upsertData, ['cart_id', 'product_id'], ['quantity', 'price', 'updated_at']);
                foreach ($upsertData as $itemData) {
                    $cartItem = $this->cartRepository->findCartItem($itemData['cart_id'], $itemData['product_id']);
                    if ($cartItem) {
                        $product = $products->get($itemData['product_id']);
                        if (array_key_exists($itemData['product_id'], $currentCartItems->toArray())) {
                            $oldQuantity = $currentCartItems[$itemData['product_id']]->quantity;
                            $this->eventDispatcher->dispatch(new \App\Events\CartItemUpdated($cart, $cartItem, $oldQuantity, $itemData['quantity']));
                        } else {
                            $this->eventDispatcher->dispatch(new \App\Events\CartItemAdded($cart, $product, $itemData['quantity']));
                        }
                    }
                }
                Log::info('Bulk cart items upserted.', ['cart_id' => $cart->id, 'updates' => count($upsertData)]);
            }

            if (!empty($itemsToDelete)) {
                $this->cartRepository->deleteCartItemsByProductIds($cart, array_keys($itemsToDelete));
                Log::info('Bulk cart items deleted.', ['cart_id' => $cart->id, 'deletions' => count($itemsToDelete)]);
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('updateMultipleItems_duration', microtime(true) - $startTime, ['updates_count' => count($updates)]);
            return CartOperationResponse::success('سبد خرید با موفقیت به‌روزرسانی شد.');

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error during bulk update: ' . $e->getMessage(), ['cart_id' => $cart->id, 'updates' => $updates, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateMultipleItems_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            return CartOperationResponse::fail($e->getMessage(), $e->getCode());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during bulk cart update: ' . $e->getMessage(), ['cart_id' => $cart->id, 'updates' => $updates, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('updateMultipleItems_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطای سیستمی در به‌روزرسانی گروهی سبد خرید.', 500);
        }
    }

    public function reserveStock(Product $product, int $quantity, ?int $minutes = null): bool
    {
        return $this->stockManager->reserveStock($product, $quantity, $minutes);
    }

    public function releaseStock(Product $product, int $quantity): bool
    {
        return $this->stockManager->releaseStock($product, $quantity);
    }

    public function cleanupExpiredCarts(?int $daysCutoff = null): int
    {
        $startTime = microtime(true);
        Log::info('Starting cleanup of expired guest carts.');

        DB::beginTransaction();
        try {
            $daysCutoff = $daysCutoff ?? config('cart.cleanup_days', 30);
            $cutoffDate = Carbon::now()->subDays($daysCutoff);

            $expiredCarts = $this->cartRepository->getExpiredGuestCarts($cutoffDate);
            $deletedCount = 0;

            foreach ($expiredCarts as $cart) {
                foreach ($cart->items as $item) {
                    $product = $this->productRepository->find($item->product_id);
                    if ($product) {
                        $this->stockManager->releaseStock($product, $item->quantity);
                    } else {
                        Log::warning('Product for cart item not found during cleanup. Stock not released.', ['cart_id' => $cart->id, 'product_id' => $item->product_id]);
                    }
                    $this->cartRepository->deleteCartItem($item);
                }
                $this->cartRepository->delete($cart);
                $this->cacheManager->clearCache(null, $cart->session_id);
                $deletedCount++;
                Log::info('Cleaned up expired guest cart', ['cart_id' => $cart->id, 'session_id' => $cart->session_id]);
            }

            DB::commit();
            $this->metricsManager->recordMetric('cleanupExpiredCarts_duration', microtime(true) - $startTime, ['cleaned_carts' => $deletedCount]);
            Log::info("Finished cleaning up {$deletedCount} expired guest carts.");
        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error during cleanupExpiredCarts: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('cleanupExpiredCarts_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error during cleanupExpiredCarts: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('cleanupExpiredCarts_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در پاکسازی سبدهای خرید منقضی شده.', 0, $e);
        }
        return $deletedCount;
    }

    private function calculateTotalItems(?Cart $cartObject, ?User $user, ?string $sessionId): int
    {
        try {
            $cart = $cartObject ?? $this->getOrCreateCart($user, $sessionId);
            $cart->loadMissing('items');
            return $cart->items->sum('quantity');
        } catch (BaseCartException $e) {
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

    public function userOwnsCartItem(CartItem $cartItem, ?User $user, ?string $sessionId): bool
    {
        if (!$cartItem->relationLoaded('cart')) {
            $cartItem->load('cart');
        }
        $cart = $cartItem->cart;

        if (!$cart) {
            return false;
        }

        if ($user && $cart->user_id === $user->id) {
            return true;
        }

        if ($sessionId && $cart->session_id === $sessionId) {
            return true;
        }

        return false;
    }

    public function getCartById(int $cartId, ?User $user = null, ?string $sessionId = null): ?Cart
    {
        $this->validator->validateUserOrSession($user, $sessionId);

        $cart = $this->cartRepository->findCartWithItems($cartId);

        if (!$cart) {
            return null;
        }

        if ($user && $cart->user_id === $user->id) {
            return $cart;
        }

        if ($sessionId && $cart->session_id === $sessionId) {
            return $cart;
        }

        Log::warning('Unauthorized access attempt to cart', ['cart_id' => $cartId, 'user_id' => $user?->id, 'session_id' => $sessionId]);
        throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این سبد خرید را ندارید.');
    }

    public function calculateCartTotals(Cart $cart): array
    {
        $startTime = microtime(true);
        $cart->loadMissing('items.product');

        $subtotal = 0.0;
        $totalItems = 0;

        foreach ($cart->items as $item) {
            if ($item->product) {
                $subtotal += $item->quantity * $item->product->price;
                $totalItems += $item->quantity;
            }
        }

        $shippingCost = $totalItems > 0 ? 15.00 : 0.00;
        $taxRate = 0.09;
        $taxes = $subtotal * $taxRate;
        $discount = 0.0;

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

    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse
    {
        Log::info('Attempting to apply coupon', ['cart_id' => $cart->id, 'coupon_code' => $couponCode]);
        $this->metricsManager->recordMetric('applyCoupon_call', 1, ['cart_id' => $cart->id]);

        if ($couponCode === 'DISCOUNT10') {
            return CartOperationResponse::success('کد تخفیف با موفقیت اعمال شد. (فقط برای تست)', ['coupon_code' => $couponCode, 'discount' => 10.00]);
        }
        return CartOperationResponse::fail('کد تخفیف نامعتبر است.', 400);
    }

    public function removeCoupon(Cart $cart): CartOperationResponse
    {
        Log::info('Attempting to remove coupon', ['cart_id' => $cart->id]);
        $this->metricsManager->recordMetric('removeCoupon_call', 1, ['cart_id' => $cart->id]);
        return CartOperationResponse::success('کد تخفیف با موفقیت حذف شد. (فقط برای تست)');
    }

    public function getCartItemCount(Cart $cart): int
    {
        $startTime = microtime(true);
        $cart->loadMissing('items');
        $count = $cart->items->count();
        $this->metricsManager->recordMetric('getCartItemCount_duration', microtime(true) - $startTime, ['cart_id' => $cart->id]);
        return $count;
    }

    public function transferCartOwnership(Cart $cart, User $newOwner): bool
    {
        $startTime = microtime(true);
        Log::info('Attempting to transfer cart ownership', ['cart_id' => $cart->id, 'current_user_id' => $cart->user_id, 'current_session_id' => $cart->session_id, 'new_owner_id' => $newOwner->id]);

        DB::beginTransaction();
        try {
            $existingNewOwnerCart = $this->cartRepository->findByUserId($newOwner->id);
            if ($existingNewOwnerCart) {
                Log::info('New owner already has a cart, merging current cart into new owner\'s cart.', ['new_owner_id' => $newOwner->id, 'existing_cart_id' => $existingNewOwnerCart->id]);
                DB::rollBack();
                throw new CartOperationException('کاربر جدید از قبل سبد خرید دارد. از متد ادغام استفاده کنید.');
            }

            $cart->user_id = $newOwner->id;
            $cart->session_id = null;
            $this->cartRepository->save($cart);

            $this->cacheManager->clearCache($newOwner);
            $this->cacheManager->clearCache($cart->user, $cart->session_id);

            Log::info('Cart ownership transferred successfully', ['cart_id' => $cart->id, 'new_owner_id' => $newOwner->id]);
            DB::commit();
            $this->metricsManager->recordMetric('transferCartOwnership_duration', microtime(true) - $startTime);
            return true;
        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error transferring cart ownership: ' . $e->getMessage(), ['cart_id' => $cart->id, 'new_owner_id' => $newOwner->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('transferCartOwnership_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error transferring cart ownership: ' . $e->getMessage(), ['cart_id' => $cart->id, 'new_owner_id' => $newOwner->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('transferCartOwnership_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            throw new CartOperationException('خطا در انتقال مالکیت سبد خرید.', 0, $e);
        }
    }

    public function isCartEmpty(Cart $cart): bool
    {
        $startTime = microtime(true);
        $cart->loadMissing('items');
        $isEmpty = $cart->items->isEmpty();
        $this->metricsManager->recordMetric('isCartEmpty_duration', microtime(true) - $startTime, ['cart_id' => $cart->id, 'is_empty' => $isEmpty]);
        return $isEmpty;
    }

    public function getCartExpiryDate(Cart $cart): ?Carbon
    {
        if ($cart->user_id !== null) {
            return null;
        }

        $cleanupDays = config('cart.cleanup_days', 30);
        return $cart->updated_at?->addDays($cleanupDays);
    }

    public function refreshCartItemPrices(Cart $cart): CartOperationResponse
    {
        $startTime = microtime(true);
        $this->rateLimiter->checkRateLimit($cart->user, $cart->session_id);

        DB::beginTransaction();
        try {
            $cart->loadMissing('items');
            $productIds = $cart->items->pluck('product_id')->unique()->toArray();
            $products = $this->productRepository->findByIds($productIds)->keyBy('id');

            $itemsUpdated = 0;
            foreach ($cart->items as $cartItem) {
                $product = $products->get($cartItem->product_id);
                if ($product && $cartItem->price !== $product->price) {
                    $this->cartRepository->updateCartItem($cartItem, ['price' => $product->price]);
                    $itemsUpdated++;
                    Log::info('Cart item price refreshed', ['cart_item_id' => $cartItem->id, 'old_price' => $cartItem->price, 'new_price' => $product->price]);
                }
            }

            DB::commit();
            $this->cacheManager->clearCache($cart->user, $cart->session_id);
            $this->metricsManager->recordMetric('refreshCartItemPrices_duration', microtime(true) - $startTime, ['cart_id' => $cart->id, 'items_updated' => $itemsUpdated]);
            return CartOperationResponse::success("قیمت {$itemsUpdated} آیتم در سبد خرید به‌روزرسانی شد.");

        } catch (BaseCartException $e) {
            DB::rollBack();
            Log::error('Cart operation error refreshing cart item prices: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('refreshCartItemPrices_exception', microtime(true) - $startTime, ['error_type' => get_class($e)]);
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Unexpected error refreshing cart item prices: ' . $e->getMessage(), ['cart_id' => $cart->id, 'exception' => $e->getTraceAsString()]);
            $this->metricsManager->recordMetric('refreshCartItemPrices_exception', microtime(true) - $startTime, ['error_type' => 'unexpected']);
            return CartOperationResponse::fail('خطا در به‌روزرسانی قیمت آیتم‌های سبد خرید.', 500);
        }
    }
}
