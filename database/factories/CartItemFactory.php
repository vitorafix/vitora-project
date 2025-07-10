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
        // اطمینان حاصل کنید که Cart و Product وجود دارند یا به صورت Lazy ایجاد شوند.
        // اگر در تست‌ها از for() استفاده می‌کنید، نیازی به ایجاد اینجا نیست.
        // اما برای اطمینان، می‌توانیم از findOrNew استفاده کنیم یا فرض کنیم که توسط تست ایجاد می‌شوند.
        $cart = Cart::factory()->create();
        $product = Product::factory()->create();

        return [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => $product->price, // قیمت آیتم را از محصول مرتبط بگیرید
            'product_variant_id' => null, // اگر تنوع محصول ندارید، null باشد
            'user_id' => $cart->user_id, // اگر سبد خرید مرتبط با کاربر باشد
        ];
    }

    /**
     * Indicate that the cart item belongs to a specific cart.
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

