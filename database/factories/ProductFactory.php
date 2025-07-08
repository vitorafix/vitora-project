<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug,
            'price' => $this->faker->numberBetween(1000, 50000),
            'stock' => $this->faker->numberBetween(0, 100),
            'status' => 'active', // ✅ حالا هماهنگ با migration
            'category_id' => 1, // فرض کن دسته‌بندی موجوده
            'image' => 'test-image.jpg',
        ];
    }
}
