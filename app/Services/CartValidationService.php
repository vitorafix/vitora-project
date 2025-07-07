<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Exceptions\CartInvalidArgumentException;
use App\Exceptions\CartLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\UnauthorizedCartAccessException;
use App\Contracts\Repositories\ProductRepositoryInterface;

class CartValidationService
{
    protected ProductRepositoryInterface $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * اعتبارسنجی پارامترهای اصلی برای عملیات سبد خرید (کاربر یا جلسه).
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @throws CartInvalidArgumentException
     */
    public function ensureValidCartIdentifier(?User $user = null, ?string $sessionId = null): void
    {
        if (!$user && !$sessionId) {
            throw new CartInvalidArgumentException('برای انجام عملیات سبد خرید، باید کاربر احراز هویت شده یا شناسه جلسه ارائه شود.');
        }
    }

    /**
     * بررسی و اعتبارسنجی مالکیت یک سبد خرید توسط کاربر یا جلسه.
     *
     * @param Cart $cart
     * @param User|null $user
     * @param string|null $sessionId
     * @throws UnauthorizedCartAccessException
     */
    public function ensureCartOwnership(Cart $cart, ?User $user, ?string $sessionId): void
    {
        if ($user && $cart->user_id !== $user->id) {
            throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این سبد خرید را ندارید.');
        }
        if ($sessionId && $cart->session_id !== $sessionId) {
            throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این سبد خرید را ندارید.');
        }
        if (!$user && !$sessionId) {
             throw new UnauthorizedCartAccessException('برای دسترسی به سبد خرید، کاربر یا شناسه جلسه الزامی است.');
        }
    }

    /**
     * اعتبارسنجی یکپارچگی و محدوده تعداد محصول.
     *
     * @param int $quantity
     * @return int
     * @throws CartInvalidArgumentException
     */
    public function validateItemQuantity(int $quantity): int
    {
        if ($quantity < 0) {
            throw new CartInvalidArgumentException('تعداد محصول نمی‌تواند منفی باشد.');
        }
        if ($quantity > config('cart.max_item_quantity', 99)) {
            throw new CartInvalidArgumentException('تعداد محصول از حد مجاز بیشتر است. حداکثر ' . config('cart.max_item_quantity', 99) . ' عدد مجاز است.');
        }
        return $quantity;
    }

    /**
     * اعتبارسنجی اینکه آیا افزودن آیتم‌های جدید باعث تجاوز از محدودیت‌های سبد خرید می‌شود.
     *
     * @param Cart $cart
     * @param int $additionalItemsCount
     * @throws CartLimitExceededException
     */
    public function ensureCartItemLimitNotExceeded(Cart $cart, int $additionalItemsCount = 1): void
    {
        $maxItems = config('cart.max_cart_items', 20);
        if ($cart->items->count() + $additionalItemsCount > $maxItems) {
            throw new CartLimitExceededException("سبد خرید شما پر است. حداکثر تعداد آیتم‌ها " . $maxItems . " عدد است.");
        }
    }

    /**
     * اعتبارسنجی موجودی محصول در انبار.
     *
     * @param Product $product
     * @param int $requestedQuantity
     * @throws InsufficientStockException
     */
    public function ensureProductHasSufficientStock(Product $product, int $requestedQuantity): void
    {
        if ($product->stock < $requestedQuantity) {
            throw new InsufficientStockException("متاسفانه موجودی کافی برای محصول '{$product->title}' وجود ندارد. موجودی فعلی: {$product->stock}");
        }
    }

    /**
     * اعتبارسنجی وجود محصول بر اساس شناسه.
     *
     * @param int $productId
     * @return Product
     * @throws ProductNotFoundException
     */
    public function ensureProductExists(int $productId): Product
    {
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new ProductNotFoundException("محصول با شناسه {$productId} در سیستم یافت نشد.");
        }
        return $product;
    }
}
