<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            // 'name' به 'title' تغییر یافت تا با ساختار جدول products هماهنگ باشد.
            'title' => $this->faker->word(),
            'price' => $this->faker->numberBetween(1000, 10000),
            'stock' => $this->faker->numberBetween(0, 100),
            // سایر فیلدهای لازم مدل Product
        ];
    }
}
