<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition()
    {
        $type = $this->faker->randomElement(['fixed', 'percentage']);

        return [
            // کد کوپن یکتا و ترکیبی از حروف بزرگ و اعداد
            'code' => strtoupper($this->faker->unique()->bothify('COUPON-####??')),
            
            'type' => $type,
            
            // اگر درصدی است بین 1 تا 50 درصد
            // اگر مبلغ ثابت است بین 1000 تا 50000 تومان
            'value' => $type === 'percentage' 
                ? $this->faker->numberBetween(1, 50) 
                : $this->faker->randomFloat(2, 1000, 50000),
            
            // تاریخ انقضا: گاهی null، گاهی در آینده بین 1 تا 30 روز
            'expires_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+30 days'),
            
            'is_active' => $this->faker->boolean(90), // 90٪ احتمال فعال بودن
            
            // سقف تخفیف: فقط برای نوع درصدی (nullable)
            'max_discount_amount' => $type === 'percentage' 
                ? $this->faker->optional()->randomFloat(2, 10000, 50000) 
                : null,
            
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * کوپن فعال و بدون انقضا
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
                'expires_at' => null,
            ];
        });
    }

    /**
     * کوپن منقضی شده
     */
    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => $this->faker->dateTimeBetween('-30 days', '-1 days'),
            ];
        });
    }
}
