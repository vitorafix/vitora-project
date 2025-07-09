<?php

namespace App\Contracts\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Services\Responses\CartOperationResponse; // اضافه شده: برای استفاده از نوع بازگشتی CartOperationResponse

interface CouponService
{
    /**
     * Apply a coupon to the cart.
     * اعمال یک کد تخفیف به سبد خرید.
     *
     * @param Cart $cart The cart to apply the coupon to.
     * @param string $couponCode The code of the coupon to apply.
     * @return CartOperationResponse A response object indicating success or failure, and any relevant data.
     */
    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse;

    /**
     * Remove a coupon from the cart.
     * حذف یک کد تخفیف از سبد خرید.
     *
     * @param Cart $cart The cart to remove the coupon from.
     * @return CartOperationResponse A response object indicating success or failure, and any relevant data.
     */
    public function removeCoupon(Cart $cart): CartOperationResponse;

    /**
     * Calculate the discount amount for a given cart and coupon.
     * مقدار تخفیف را برای سبد خرید و کوپن مشخص شده محاسبه می‌کند.
     *
     * @param Cart $cart The cart for which to calculate the discount.
     * @param Coupon $coupon The coupon to use for calculation.
     * @return float The calculated discount amount.
     */
    public function calculateDiscount(Cart $cart, Coupon $coupon): float;
}

