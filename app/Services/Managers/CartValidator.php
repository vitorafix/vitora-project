<?php

namespace App\Services\Managers;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CartInvalidArgumentException; // Custom exception
use App\Exceptions\CartLimitExceededException; // Custom exception
use App\Exceptions\UnauthorizedCartAccessException; // Added: For ensureCartOwnership

class CartValidator
{
    private int $maxItemsPerCart;
    private int $maxQuantityPerItem;

    public function __construct()
    {
        $this->maxItemsPerCart = config('cart.max_items_per_cart', 100);
        $this->maxQuantityPerItem = config('cart.max_quantity_per_item', 999);
    }

    /**
     * Validates the quantity of a product.
     * تعداد یک محصول را اعتبارسنجی می‌کند.
     *
     * @param int $quantity
     * @return int The sanitized quantity.
     * @throws CartInvalidArgumentException
     */
    public function validateQuantity(int $quantity): int
    {
        if ($quantity < 0) {
            throw new CartInvalidArgumentException('تعداد محصول نمی‌تواند منفی باشد.'); // Quantity cannot be negative.
        }
        if ($quantity > $this->maxQuantityPerItem) {
            Log::warning('Requested quantity exceeds max quantity per item', ['quantity' => $quantity, 'max_quantity' => $this->maxQuantityPerItem]);
            throw new CartInvalidArgumentException('تعداد درخواستی برای هر آیتم بیش از حد مجاز است. حداکثر: ' . $this->maxQuantityPerItem); // Requested quantity per item exceeds max allowed.
        }
        return $quantity;
    }

    /**
     * Validates if adding new items would exceed the maximum number of unique items allowed in the cart.
     * اعتبارسنجی می‌کند که آیا اضافه کردن آیتم‌های جدید، حداکثر تعداد آیتم‌های منحصر به فرد مجاز در سبد خرید را نقض می‌کند یا خیر.
     *
     * @param Cart $cart
     * @param int $itemsToAdd The number of unique items being added.
     * @throws CartLimitExceededException
     */
    public function validateCartLimits(Cart $cart, int $itemsToAdd = 1): void
    {
        $cart->loadMissing('items'); // Ensure items are loaded to count them
        if (($cart->items->count() + $itemsToAdd) > $this->maxItemsPerCart) {
            Log::warning('Cart item limit exceeded', ['cart_id' => $cart->id, 'current_items' => $cart->items->count(), 'max_items' => $this->maxItemsPerCart]);
            throw new CartLimitExceededException('تعداد آیتم‌های منحصر به فرد در سبد خرید بیش از حد مجاز است. حداکثر: ' . $this->maxItemsPerCart); // Max unique items per cart exceeded.
        }
    }

    /**
     * Validates that either a user or a session ID is provided.
     * اعتبارسنجی می‌کند که یا شناسه کاربر یا شناسه سشن ارائه شده باشد.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @throws CartInvalidArgumentException
     */
    public function validateUserOrSession(?User $user = null, ?string $sessionId = null): void
    {
        if (is_null($user) && is_null($sessionId)) {
            Log::error('Attempted to get or create cart without user or session ID');
            throw new CartInvalidArgumentException('برای دریافت یا ایجاد سبد خرید، شناسه کاربر یا شناسه سشن الزامی است.'); // User or session ID is required to get or create a cart.
        }
    }

    /**
     * Ensures that either a user or a session ID is provided for cart operations.
     * این متد اطمینان حاصل می‌کند که برای عملیات سبد خرید، یا شناسه کاربر یا شناسه سشن ارائه شده باشد.
     * این متد برای رفع خطای "Call to undefined method ensureValidCartIdentifier" اضافه شده است.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @throws CartInvalidArgumentException
     */
    public function ensureValidCartIdentifier(?User $user = null, ?string $sessionId = null): void
    {
        // از متد موجود validateUserOrSession برای انجام اعتبارسنجی استفاده می‌کنیم.
        $this->validateUserOrSession($user, $sessionId);
    }

    /**
     * Ensures the given user or session ID owns the cart.
     * این متد اطمینان حاصل می‌کند که کاربر یا شناسه سشن داده شده، مالک سبد خرید است.
     *
     * @param Cart $cart
     * @param User|null $user
     * @param string|null $sessionId
     * @return bool
     * @throws UnauthorizedCartAccessException
     */
    public function ensureCartOwnership(Cart $cart, ?User $user, ?string $sessionId): bool
    {
        if ($user && $cart->user_id === $user->id) {
            return true;
        }

        if ($sessionId && $cart->session_id === $sessionId) {
            return true;
        }

        Log::warning('Unauthorized cart access attempt.', [
            'cart_id' => $cart->id,
            'attempted_user_id' => $user ? $user->id : null,
            'attempted_session_id' => $sessionId,
            'cart_owner_user_id' => $cart->user_id,
            'cart_owner_session_id' => $cart->session_id,
        ]);
        throw new UnauthorizedCartAccessException('شما مجاز به دسترسی به این سبد خرید نیستید.');
    }

    /**
     * Ensures a product has sufficient stock.
     * این متد اطمینان حاصل می‌کند که یک محصول موجودی کافی دارد.
     *
     * @param \App\Models\Product $product
     * @param int $requestedQuantity
     * @param int $availableStock
     * @param string $entityName
     * @throws \App\Exceptions\Cart\InsufficientStockException
     */
    public function ensureProductHasSufficientStock(\App\Models\Product $product, int $requestedQuantity, int $availableStock, string $entityName): void
    {
        if ($requestedQuantity > $availableStock) {
            throw new \App\Exceptions\Cart\InsufficientStockException("موجودی کافی برای {$entityName} وجود ندارد. موجودی فعلی: {$availableStock}");
        }
    }


    /**
     * Performs a health check for the cart validator.
     * یک بررسی سلامت برای اعتبارسنج سبد خرید انجام می‌دهد.
     *
     * @return array
     */
    public function healthCheck(): array
    {
        // Simple check to ensure config values are loaded correctly
        // یک بررسی ساده برای اطمینان از بارگذاری صحیح مقادیر پیکربندی
        if ($this->maxItemsPerCart > 0 && $this->maxQuantityPerItem > 0) {
            return ['status' => 'ok', 'message' => 'Cart validator is configured correctly.'];
        }
        return ['status' => 'failed', 'message' => 'Cart validator configuration error.'];
    }
}
