<?php

namespace App\Services;

use App\Contracts\Services\CouponService as CouponServiceContract;
use App\Models\Cart;
use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\Responses\CartOperationResponse; // اضافه شده: برای استفاده از CartOperationResponse

class CouponService implements CouponServiceContract
{
    /**
     * Apply a coupon to the cart.
     * اعمال یک کد تخفیف به سبد خرید.
     *
     * @param Cart $cart The cart to apply the coupon to.
     * @param string $couponCode The code of the coupon to apply.
     * @return CartOperationResponse A response object indicating success or failure, and any relevant data.
     */
    public function applyCoupon(Cart $cart, string $couponCode): CartOperationResponse
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
            return CartOperationResponse::fail('کد تخفیف نامعتبر یا منقضی شده است.', 400);
        }

        if ($cart->coupon_id === $coupon->id) {
            Log::info('Coupon already applied to cart.', [
                'coupon_code' => $couponCode,
                'cart_id' => $cart->id,
            ]);
            return CartOperationResponse::success('کد تخفیف قبلاً اعمال شده است.', ['coupon_id' => $coupon->id]);
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
        return CartOperationResponse::success('کد تخفیف با موفقیت اعمال شد.', ['coupon_id' => $coupon->id, 'discount_amount' => $discountAmount]);
    }

    /**
     * Remove a coupon from the cart.
     * حذف یک کد تخفیف از سبد خرید.
     *
     * @param Cart $cart The cart to remove the coupon from.
     * @return CartOperationResponse A response object indicating success or failure, and any relevant data.
     */
    public function removeCoupon(Cart $cart): CartOperationResponse
    {
        if (!$cart->coupon_id) {
            Log::info('No coupon applied to cart to remove.', [
                'cart_id' => $cart->id,
            ]);
            return CartOperationResponse::fail('هیچ کد تخفیفی برای حذف از سبد خرید اعمال نشده است.', 400);
        }

        $cart->coupon_id = null;
        $cart->discount_amount = 0;
        $cart->save();

        Log::info('Coupon removed successfully.', [
            'cart_id' => $cart->id,
        ]);
        return CartOperationResponse::success('کد تخفیف با موفقیت حذف شد.');
    }

    /**
     * Calculate the discount amount for a given cart and coupon.
     * مقدار تخفیف را برای سبد خرید و کوپن مشخص شده محاسبه می‌کند.
     *
     * @param Cart $cart The cart for which to calculate the discount.
     * @param Coupon $coupon The coupon to use for calculation.
     * @return float The calculated discount amount.
     */
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

        // Ensure discount does not exceed subtotal
        return max(0, min($discount, $subtotal));
    }
}

