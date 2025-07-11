<?php

namespace Database\Factories;

use App\Models\CartItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // ایجاد یک سبد خرید و محصول پیش‌فرض در صورتی که به صورت صریح در فراخوانی factory مشخص نشده باشند.
        // این کار تضمین می‌کند که آیتم سبد خرید همیشه به یک سبد و محصول معتبر مرتبط است.
        $cart = Cart::factory()->create();
        $product = Product::factory()->create();

        return [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => $product->price, // قیمت واحد آیتم از محصول مرتبط گرفته می‌شود
            'product_variant_id' => null, // اگر محصول دارای تنوع است، اینجا تنظیم شود؛ در غیر این صورت null
            'user_id' => $cart->user_id, // همگام‌سازی user_id آیتم با user_id سبد خرید
        ];
    }

    /**
     * Indicate that the cart item belongs to a specific cart.
     * نشان می‌دهد که آیتم سبد خرید متعلق به یک سبد خرید خاص است.
     *
     * @param \App\Models\Cart $cart
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forCart(Cart $cart): Factory
    {
        return $this->state(fn (array $attributes) => [
            'cart_id' => $cart->id,
            'user_id' => $cart->user_id, // اطمینان از همگام‌سازی user_id با cart
        ]);
    }

    /**
     * Indicate that the cart item belongs to a specific product.
     * نشان می‌دهد که آیتم سبد خرید متعلق به یک محصول خاص است.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forProduct(Product $product): Factory
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'price' => $product->price, // اطمینان از همگام‌سازی قیمت با محصول
        ]);
    }
}
