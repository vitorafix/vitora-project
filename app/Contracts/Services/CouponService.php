<?php

namespace App\Contracts\Services;

use App\Models\Cart;
use App\Models\Coupon;

interface CouponService
{
    public function applyCoupon(Cart $cart, string $couponCode): bool;

    public function removeCoupon(Cart $cart): bool;

    public function calculateDiscount(Cart $cart, Coupon $coupon): float;
}
