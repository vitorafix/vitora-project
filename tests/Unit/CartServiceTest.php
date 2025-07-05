<?php
// File: tests/Unit/CartServiceTest.php
namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Contracts\CartServiceInterface;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\CartInvalidArgumentException;
use App\Exceptions\CartLimitExceededException;

class CartServiceTest extends TestCase
{
    use RefreshDatabase; // برای هر تست، دیتابیس را ریست می‌کند

    protected CartServiceInterface $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        // Bindings should be set up in App\Providers\CartServiceProvider
        $this->cartService = app(CartServiceInterface::class);

        // تنظیمات config برای تست (اختیاری، اما توصیه می‌شود)
        config(['cart.stock_check_enabled' => true]);
        config(['cart.max_items_per_cart' => 100]);
        config(['cart.max_quantity_per_item' => 999]);
        config(['cart.rate_limit_cooldown' => 0]); // برای تست rate limit
    }

    /** @test */
    public function test_can_add_item_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 1000]);
        $cart = $this->cartService->getOrCreateCart($user);

        $result = $this->cartService->addOrUpdateCartItem($cart, $product->id, 2);

        $this->assertTrue($result['success']);
        $this->assertEquals('محصول با موفقیت به سبد خرید اضافه شد!', $result['message']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals(2, $result['totalItemsInCart']);

        // بررسی مستقیم دیتابیس
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /** @test */
    public function test_cannot_add_item_with_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 1, 'price' => 1000]);
        $cart = $this->cartService->getOrCreateCart($user);

        $this->expectException(InsufficientStockException::class);
        $this->cartService->addOrUpdateCartItem($cart, $product->id, 2);
    }

    /** @test */
    public function test_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 1000]);
        $cart = $this->cartService->getOrCreateCart($user);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $result = $this->cartService->updateCartItemQuantity($cartItem, 5, $user);

        $this->assertTrue($result['success']);
        $this->assertEquals('تعداد محصول با موفقیت به‌روزرسانی شد!', $result['message']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals(5, $result['totalItemsInCart']);

        $this->assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);
    }

    /** @test */
    public function test_cannot_update_quantity_with_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 3, 'price' => 1000]);
        $cart = $this->cartService->getOrCreateCart($user);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $this->expectException(InsufficientStockException::class);
        $this->cartService->updateCartItemQuantity($cartItem, 4, $user);
    }

    /** @test */
    public function test_can_remove_item_from_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 1000]);
        $cart = $this->cartService->getOrCreateCart($user);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $result = $this->cartService->removeCartItem($cartItem, $user);

        $this->assertTrue($result['success']);
        $this->assertEquals('محصول از سبد خرید حذف شد.', $result['message']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals(0, $result['totalItemsInCart']);

        $this->assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    }

    /** @test */
    public function test_can_clear_cart(): void
    {
        $user = User::factory()->create();
        $product1 = Product::factory()->create(['stock' => 10]);
        $product2 = Product::factory()->create(['stock' => 10]);
        $cart = $this->cartService->getOrCreateCart($user);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product1->id, 'quantity' => 1, 'price' => $product1->price]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product2->id, 'quantity' => 2, 'price' => $product2->price]);

        $result = $this->cartService->clearCart($cart);

        $this->assertTrue($result['success']);
        $this->assertEquals('سبد خرید شما خالی شد.', $result['message']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals(0, $result['totalItemsInCart']);

        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
    }

    /** @test */
    public function test_guest_cart_is_merged_on_login(): void
    {
        $guestSessionId = Session::getId();
        $guestCart = Cart::create(['session_id' => $guestSessionId]);
        $product1 = Product::factory()->create(['stock' => 10, 'price' => 100]);
        $product2 = Product::factory()->create(['stock' => 5, 'price' => 200]);
        CartItem::create(['cart_id' => $guestCart->id, 'product_id' => $product1->id, 'quantity' => 2, 'price' => $product1->price]);
        CartItem::create(['cart_id' => $guestCart->id, 'product_id' => $product2->id, 'quantity' => 1, 'price' => $product2->price]);

        $user = User::factory()->create();
        Auth::login($user); // Simulate user login

        $this->cartService->mergeGuestCart($user, $guestSessionId);

        $userCart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($userCart);
        $this->assertEquals(3, $userCart->items()->sum('quantity')); // 2 from product1 + 1 from product2
        $this->assertDatabaseMissing('carts', ['session_id' => $guestSessionId]);
    }

    /** @test */
    public function test_assign_guest_cart_to_new_user(): void
    {
        $guestSessionId = Session::getId();
        $guestCart = Cart::create(['session_id' => $guestSessionId]);
        $product = Product::factory()->create(['stock' => 10, 'price' => 100]);
        CartItem::create(['cart_id' => $guestCart->id, 'product_id' => $product->id, 'quantity' => 3, 'price' => $product->price]);

        $newUser = User::factory()->create();

        $this->cartService->assignGuestCartToNewUser($guestSessionId, $newUser);

        $assignedCart = Cart::where('user_id', $newUser->id)->first();
        $this->assertNotNull($assignedCart);
        $this->assertNull($assignedCart->session_id);
        $this->assertEquals(3, $assignedCart->items()->sum('quantity'));
        $this->assertDatabaseMissing('carts', ['session_id' => $guestSessionId]);
    }

    /** @test */
    public function test_rate_limiting_prevents_too_many_requests(): void
    {
        config(['cart.rate_limit_cooldown' => 1]); // Set cooldown to 1 second for testing

        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $cart = $this->cartService->getOrCreateCart($user);

        // First request should succeed
        $result = $this->cartService->addOrUpdateCartItem($cart, $product->id, 1);
        $this->assertTrue($result['success']);

        // Second request immediately should fail due to rate limit
        $this->expectException(CartOperationException::class);
        $this->expectExceptionCode(429);
        $this->cartService->addOrUpdateCartItem($cart, $product->id, 1);
    }

    /** @test */
    public function test_cart_limits_are_enforced(): void
    {
        config(['cart.max_items_per_cart' => 2]); // Max 2 items in cart

        $user = User::factory()->create();
        $product1 = Product::factory()->create(['stock' => 10]);
        $product2 = Product::factory()->create(['stock' => 10]);
        $product3 = Product::factory()->create(['stock' => 10]);
        $cart = $this->cartService->getOrCreateCart($user);

        $this->cartService->addOrUpdateCartItem($cart, $product1->id, 1);
        $this->cartService->addOrUpdateCartItem($cart, $product2->id, 1);

        $this->expectException(CartLimitExceededException::class);
        $this->cartService->addOrUpdateCartItem($cart, $product3->id, 1); // Should fail
    }

    /** @test */
    public function test_cleanup_expired_guest_carts(): void
    {
        // Create an active guest cart
        $activeCart = Cart::create(['session_id' => 'active_session']);
        CartItem::create(['cart_id' => $activeCart->id, 'product_id' => Product::factory()->create()->id, 'quantity' => 1]);

        // Create an expired guest cart
        $expiredCart = Cart::create(['session_id' => 'expired_session', 'updated_at' => Carbon::now()->subDays(31)]);
        CartItem::create(['cart_id' => $expiredCart->id, 'product_id' => Product::factory()->create()->id, 'quantity' => 1]);

        // Create a user cart (should not be deleted)
        $userCart = Cart::create(['user_id' => User::factory()->create()->id]);
        CartItem::create(['cart_id' => $userCart->id, 'product_id' => Product::factory()->create()->id, 'quantity' => 1]);

        $deletedCount = $this->cartService->cleanupExpiredCarts(30);

        $this->assertEquals(1, $deletedCount);
        $this->assertDatabaseMissing('carts', ['session_id' => 'expired_session']);
        $this->assertDatabaseHas('carts', ['session_id' => 'active_session']);
        $this->assertDatabaseHas('carts', ['user_id' => $userCart->user_id]);
    }
}
