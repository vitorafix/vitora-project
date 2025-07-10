<?php

namespace Database\Factories;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     * نام مدل مربوط به این Factory.
     *
     * @var string
     */
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     * تعریف وضعیت پیش‌فرض مدل.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // به طور پیش‌فرض سبد خرید مهمان است
            'coupon_id' => null, // به طور پیش‌فرض بدون کوپن
            // 'discount_amount' => 0, // این خط حذف شد زیرا ستون در جدول carts وجود ندارد
            // 'total_amount' => $this->faker->randomFloat(2, 100, 10000), // می‌توانید این را بر اساس آیتم‌های سبد خرید محاسبه کنید
        ];
    }

    /**
     * Indicate that the cart belongs to a specific user.
     * نشان می‌دهد که سبد خرید متعلق به یک کاربر خاص است.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forUser(\App\Models\User $user): Factory
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }

    /**
     * Indicate that the cart has a specific coupon applied.
     * نشان می‌دهد که سبد خرید دارای یک کوپن اعمال شده است.
     *
     * @param \App\Models\Coupon $coupon
     * @param float $discountAmount
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withCoupon(\App\Models\Coupon $coupon, float $discountAmount = 0): Factory
    {
        return $this->state(function (array $attributes) use ($coupon, $discountAmount) {
            return [
                'coupon_id' => $coupon->id,
                // 'discount_amount' => $discountAmount, // این خط حذف شد زیرا ستون در جدول carts وجود ندارد
            ];
        });
    }
}
