<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     * نام مدل مربوط به این Factory.
     *
     * @var string
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     * تعریف وضعیت پیش‌فرض مدل.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word() . $this->faker->randomNumber(3), // کد کوپن منحصر به فرد
            'type' => $this->faker->randomElement(['percentage', 'fixed']), // نوع کوپن: درصدی یا ثابت
            'value' => $this->faker->randomFloat(2, 5, 50), // مقدار تخفیف
            'is_active' => true, // به طور پیش‌فرض فعال
            'expires_at' => null, // به طور پیش‌فرض منقضی نمی‌شود
            'usage_limit' => null, // بدون محدودیت استفاده کلی
            'times_used' => 0, // تعداد دفعات استفاده شده
            'min_order_amount' => null, // بدون حداقل مبلغ سفارش
            'max_discount_amount' => null, // بدون حداکثر مبلغ تخفیف
            'user_usage_limit' => null, // بدون محدودیت استفاده کاربر
        ];
    }

    /**
     * Indicate that the coupon is inactive.
     * نشان می‌دهد که کوپن غیرفعال است.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inactive(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Indicate that the coupon is expired.
     * نشان می‌دهد که کوپن منقضی شده است.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function expired(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => Carbon::yesterday(),
            ];
        });
    }

    /**
     * Indicate that the coupon has a specific usage limit.
     * نشان می‌دهد که کوپن دارای محدودیت استفاده خاصی است.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withUsageLimit(int $limit): Factory
    {
        return $this->state(function (array $attributes) use ($limit) {
            return [
                'usage_limit' => $limit,
            ];
        });
    }

    /**
     * Indicate that the coupon has a specific user usage limit.
     * نشان می‌دهد که کوپن دارای محدودیت استفاده خاص کاربر است.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withUserUsageLimit(int $limit): Factory
    {
        return $this->state(function (array $attributes) use ($limit) {
            return [
                'user_usage_limit' => $limit,
            ];
        });
    }

    /**
     * Indicate that the coupon requires a minimum order amount.
     * نشان می‌دهد که کوپن به حداقل مبلغ سفارش نیاز دارد.
     *
     * @param float $amount
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withMinOrderAmount(float $amount): Factory
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'min_order_amount' => $amount,
            ];
        });
    }

    /**
     * Indicate that the coupon has a maximum discount amount.
     * نشان می‌دهد که کوپن دارای حداکثر مبلغ تخفیف است.
     *
     * @param float $amount
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withMaxDiscountAmount(float $amount): Factory
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'max_discount_amount' => $amount,
            ];
        });
    }
}

