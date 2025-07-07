<?php

namespace App\Services;

use App\Contracts\Services\CouponService as CouponServiceContract;
use App\Models\Cart;
use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CouponService implements CouponServiceContract
{
    public function applyCoupon(Cart $cart, string $couponCode): bool
    {
        $coupon = Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->first();

        if (!$coupon) {
            Log::warning('Attempted to apply invalid or expired coupon.', [
                'coupon_code' => $couponCode,
                'cart_id' => $cart->id,
            ]);
            return false;
        }

        if ($cart->coupon_id === $coupon->id) {
            Log::info('Coupon already applied to cart.', [
                'coupon_code' => $couponCode,
                'cart_id' => $cart->id,
            ]);
            return true;
        }

        $discountAmount = $this->calculateDiscount($cart, $coupon);
        $cart->coupon_id = $coupon->id;
        $cart->discount_amount = $discountAmount;
        $cart->save();

        Log::info('Coupon applied successfully.', [
            'coupon_code' => $couponCode,
            'cart_id' => $cart->id,
            'discount_amount' => $discountAmount,
        ]);
        return true;
    }

    public function removeCoupon(Cart $cart): bool
    {
        if (!$cart->coupon_id) {
            Log::info('No coupon applied to cart to remove.', [
                'cart_id' => $cart->id,
            ]);
            return false;
        }

        $cart->coupon_id = null;
        $cart->discount_amount = 0;
        $cart->save();

        Log::info('Coupon removed successfully.', [
            'cart_id' => $cart->id,
        ]);
        return true;
    }

    public function calculateDiscount(Cart $cart, Coupon $coupon): float
    {
        $cart->loadMissing(['items.product', 'items.productVariant']);

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

        $discount = 0.0;

        if ($coupon->type === 'percentage') {
            $discount = ($subtotal * $coupon->value) / 100;
            if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
                $discount = $coupon->max_discount_amount;
            }
        } elseif ($coupon->type === 'fixed') {
            $discount = $coupon->value;
        }

        return min($discount, $subtotal);
    }
}
