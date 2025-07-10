<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Category; // اضافه شده: برای ایجاد دسته‌بندی پیش‌فرض
use App\Services\Contracts\CartServiceInterface;
use App\Services\CartCalculationService;
use App\Services\Responses\CartOperationResponse;
use App\Services\Responses\CartContentsResponse;
use App\DTOs\CartTotalsDTO; // برای ساخت CartTotalsDTO در تست‌ها

class CartControllerTest extends TestCase
{
    use RefreshDatabase; // برای بازنشانی دیتابیس قبل از هر تست

    protected $cartService;
    protected $cartCalculationService;

    // متد setUp برای آماده‌سازی قبل از هر تست
    protected function setUp(): void
    {
        parent::setUp();

        // Bind کردن سرویس‌ها به کانتینر برای تزریق در کنترلر
        $this->cartService = $this->mock(CartServiceInterface::class);
        $this->cartCalculationService = $this->mock(CartCalculationService::class);

        // ایجاد یک دسته‌بندی پیش‌فرض با ID 1 برای ProductFactory
        Category::factory()->create(['id' => 1]);

        // تنظیم پیش‌فرض برای CartContentsResponse در صورت نیاز
        // این DTO برای CartContentsResponse استفاده می‌شود
        $dummyCartTotalsDTO = new CartTotalsDTO(0, 0, 0, 0, 0);

        // Mock کردن متد getCartContents برای سناریوهای پیش‌فرض
        $this->cartService->shouldReceive('getCartContents')
            ->andReturnUsing(function ($cart) use ($dummyCartTotalsDTO) {
                // یک CartContentsResponse خالی برمی‌گرداند
                return new CartContentsResponse([], 0, 0, $dummyCartTotalsDTO);
            });
    }

    /**
     * تست نمایش صفحه سبد خرید (index) برای کاربر احراز هویت شده.
     *
     * @return void
     */
    public function test_authenticated_user_can_view_cart_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Mock کردن getOrCreateCart برای اطمینان از اینکه سبد خرید ایجاد می‌شود
        $cart = Cart::factory()->forUser($user)->create();
        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        // Mock کردن getCartContents برای بازگرداندن محتویات سبد خرید
        $dummyCartTotalsDTO = new CartTotalsDTO(1000, 0, 0, 0, 1000);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                [['id' => 1, 'product_id' => 1, 'quantity' => 1, 'price' => 1000, 'product' => ['title' => 'Product 1']]],
                1,
                1000,
                $dummyCartTotalsDTO
            ));

        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertViewIs('cart'); // مطمئن شوید که ویو 'cart' رندر می‌شود
        $response->assertViewHas('cartItems');
        $response->assertViewHas('cartTotals');
        $response->assertViewHas('isEmpty');
        $response->assertViewHas('totalQuantity');
        $response->assertViewHas('totalPrice');
    }

    /**
     * تست دریافت محتویات سبد خرید به صورت JSON (getContents) برای کاربر احراز هویت شده.
     *
     * @return void
     */
    public function test_authenticated_user_can_get_cart_contents_json()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = Cart::factory()->forUser($user)->create();
        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        $dummyCartTotalsDTO = new CartTotalsDTO(1000, 0, 0, 0, 1000);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                [['id' => 1, 'product_id' => 1, 'quantity' => 1, 'price' => 1000, 'product' => ['title' => 'Product 1', 'slug' => 'product-1', 'image' => 'img.jpg', 'stock' => 10]]],
                1,
                1000,
                $dummyCartTotalsDTO
            ));

        $response = $this->getJson(route('cart.contents'));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'items' => [
                             '*' => [
                                 'id',
                                 'product' => [
                                     'id', 'name', 'inStock', 'slug', 'image', 'stockQuantity' // فیلدهای کامل برای غیرموبایل
                                 ],
                                 'quantity',
                                 'unitPrice',
                                 'totalPrice',
                                 'formattedUnitPrice',
                                 'formattedTotalPrice',
                                 'addedAt',
                                 'updatedAt',
                             ]
                         ],
                         'summary' => [
                             'totalQuantity',
                             'totalPrice',
                             'isEmpty',
                             'formattedTotalPrice',
                             'currency'
                         ],
                         'metadata' => [
                             'itemCount',
                             'lastUpdated',
                             'version',
                             'requestId'
                         ]
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'سبد خرید با موفقیت دریافت شد', // پیام از CartResource::with()
                     'data' => [
                         'summary' => [
                             'totalQuantity' => 1,
                             'totalPrice' => 1000,
                             'isEmpty' => false,
                         ]
                     ]
                 ]);
    }

    /**
     * تست افزودن محصول به سبد خرید با موفقیت.
     *
     * @return void
     */
    public function test_user_can_add_item_to_cart_successfully()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 500]); // استفاده از 'stock' به جای 'stock_quantity'
        $this->actingAs($user);

        $cart = Cart::factory()->forUser($user)->create();
        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        // Mock کردن addOrUpdateCartItem
        $this->cartService->shouldReceive('addOrUpdateCartItem')
            ->once()
            ->andReturn(CartOperationResponse::success('محصول اضافه شد.'));

        // Mock کردن getCartContents پس از افزودن آیتم
        $dummyCartTotalsDTO = new CartTotalsDTO(500, 0, 0, 0, 500);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                [['id' => 1, 'product_id' => $product->id, 'quantity' => 1, 'price' => 500, 'product' => $product->toArray()]],
                1,
                500,
                $dummyCartTotalsDTO
            ));

        $response = $this->postJson(route('cart.add', ['product' => $product->id]), [ // استفاده از ID محصول
            'quantity' => 1,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'items',
                         'summary',
                         'metadata'
                     ],
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'محصول با موفقیت به سبد خرید اضافه شد.',
                     'data' => [
                         'summary' => [
                             'totalQuantity' => 1,
                             'totalPrice' => 500,
                         ]
                     ]
                 ]);
    }

    /**
     * تست افزودن محصول با موجودی ناکافی.
     *
     * @return void
     */
    public function test_user_cannot_add_item_with_insufficient_stock()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5, 'price' => 500]);
        $this->actingAs($user);

        $cart = Cart::factory()->forUser($user)->create();
        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        // Mock کردن addOrUpdateCartItem برای پرتاب استثنای موجودی ناکافی
        $this->cartService->shouldReceive('addOrUpdateCartItem')
            ->once()
            ->andThrow(new \App\Exceptions\Cart\InsufficientStockException('موجودی کافی نیست.', 400));

        $response = $this->postJson(route('cart.add', ['product' => $product->id]), [
            'quantity' => 10, // درخواست بیش از موجودی
        ]);

        $response->assertStatus(400) // یا 422 اگر اعتبارسنجی سمت سرور باشد
                 ->assertJson([
                     'success' => false,
                     'message' => 'موجودی کافی نیست.',
                 ]);
    }

    /**
     * تست به‌روزرسانی تعداد آیتم در سبد خرید با موفقیت.
     *
     * @return void
     */
    public function test_user_can_update_cart_item_quantity_successfully()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10, 'price' => 100]);
        $cart = Cart::factory()->forUser($user)->create();
        $cartItem = CartItem::factory()->for($cart)->for($product)->create(['quantity' => 1, 'price' => 100]);
        $this->actingAs($user);

        $this->cartService->shouldReceive('userOwnsCartItem')
            ->once()
            ->andReturn(true);

        // Mock کردن updateCartItemQuantity
        $this->cartService->shouldReceive('updateCartItemQuantity')
            ->once()
            ->andReturn(CartOperationResponse::success('تعداد به‌روزرسانی شد.'));

        // Mock کردن getCartById و getCartContents پس از به‌روزرسانی
        $this->cartService->shouldReceive('getCartById')
            ->once()
            ->andReturn($cart); // باید نمونه Cart را برگرداند

        $dummyCartTotalsDTO = new CartTotalsDTO(200, 0, 0, 0, 200);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                [['id' => $cartItem->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => 100, 'product' => $product->toArray()]],
                2,
                200,
                $dummyCartTotalsDTO
            ));

        $response = $this->putJson(route('cart.update', ['cartItem' => $cartItem->id]), [
            'quantity' => 2,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'items',
                         'summary',
                         'metadata'
                     ],
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'تعداد آیتم سبد خرید با موفقیت به‌روزرسانی شد.',
                     'data' => [
                         'summary' => [
                             'totalQuantity' => 2,
                             'totalPrice' => 200,
                         ]
                     ]
                 ]);
    }

    /**
     * تست حذف آیتم از سبد خرید با موفقیت.
     *
     * @return void
     */
    public function test_user_can_remove_cart_item_successfully()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 100]);
        $cart = Cart::factory()->forUser($user)->create();
        $cartItem = CartItem::factory()->for($cart)->for($product)->create(['quantity' => 1, 'price' => 100]);
        $this->actingAs($user);

        $this->cartService->shouldReceive('userOwnsCartItem')
            ->once()
            ->andReturn(true);

        // Mock کردن removeCartItem
        $this->cartService->shouldReceive('removeCartItem')
            ->once()
            ->andReturn(CartOperationResponse::success('آیتم حذف شد.'));

        // Mock کردن getCartContents پس از حذف (سبد خالی)
        $dummyCartTotalsDTO = new CartTotalsDTO(0, 0, 0, 0, 0);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse([], 0, 0, $dummyCartTotalsDTO));

        $response = $this->deleteJson(route('cart.remove', ['cartItem' => $cartItem->id]));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'items',
                         'summary',
                         'metadata'
                     ],
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'آیتم با موفقیت از سبد خرید حذف شد.',
                     'data' => [
                         'summary' => [
                             'totalQuantity' => 0,
                             'totalPrice' => 0,
                             'isEmpty' => true,
                         ]
                     ]
                 ]);
    }

    /**
     * تست پاک کردن کامل سبد خرید با موفقیت.
     *
     * @return void
     */
    public function test_user_can_clear_cart_successfully()
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        // ایجاد چند آیتم در سبد خرید
        CartItem::factory()->for($cart)->create(['quantity' => 1, 'price' => 100]);
        CartItem::factory()->for($cart)->create(['quantity' => 2, 'price' => 200]);
        $this->actingAs($user);

        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        // Mock کردن clearCart
        $this->cartService->shouldReceive('clearCart')
            ->once()
            ->andReturn(CartOperationResponse::success('سبد خرید پاک شد.'));

        // Mock کردن getCartContents پس از پاکسازی (سبد خالی)
        $dummyCartTotalsDTO = new CartTotalsDTO(0, 0, 0, 0, 0);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse([], 0, 0, $dummyCartTotalsDTO));

        $response = $this->postJson(route('cart.clear'));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'items',
                         'summary',
                         'metadata'
                     ],
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'سبد خرید با موفقیت پاک شد.',
                     'data' => [
                         'summary' => [
                             'totalQuantity' => 0,
                             'totalPrice' => 0,
                             'isEmpty' => true,
                         ]
                     ]
                 ]);
    }

    /**
     * تست اعمال کوپن تخفیف با موفقیت.
     *
     * @return void
     */
    public function test_user_can_apply_coupon_successfully()
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['code' => 'TESTCOUPON', 'type' => 'fixed', 'value' => 100]);
        $cart = Cart::factory()->forUser($user)->create();
        $this->actingAs($user);

        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        // Mock کردن applyCoupon
        $this->cartService->shouldReceive('applyCoupon')
            ->once()
            ->andReturn(CartOperationResponse::success('کوپن اعمال شد.'));

        // Mock کردن getCartContents پس از اعمال کوپن
        $dummyCartTotalsDTO = new CartTotalsDTO(1000, 100, 0, 0, 900); // فرض کنید 1000 تومان بوده و 100 تومان تخفیف خورده
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                [['id' => 1, 'product_id' => 1, 'quantity' => 1, 'price' => 1000, 'product' => ['title' => 'Product 1']]],
                1,
                1000,
                $dummyCartTotalsDTO
            ));

        $response = $this->postJson(route('cart.apply-coupon'), [
            'coupon_code' => 'TESTCOUPON',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'items',
                         'summary',
                         'metadata'
                     ],
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'کد تخفیف با موفقیت اعمال شد.',
                     'data' => [
                         'summary' => [
                             'totalPrice' => 900, // قیمت پس از تخفیف
                         ]
                     ]
                 ]);
    }

    /**
     * تست حذف کوپن تخفیف با موفقیت.
     *
     * @return void
     */
    public function test_user_can_remove_coupon_successfully()
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create(['coupon_id' => 1]); // سبد خرید با کوپن
        $this->actingAs($user);

        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        // Mock کردن removeCoupon
        $this->cartService->shouldReceive('removeCoupon')
            ->once()
            ->andReturn(CartOperationResponse::success('کوپن حذف شد.'));

        // Mock کردن getCartContents پس از حذف کوپن (بازگشت به قیمت اصلی)
        $dummyCartTotalsDTO = new CartTotalsDTO(1000, 0, 0, 0, 1000); // فرض کنید 1000 تومان بوده و تخفیف حذف شده
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                [['id' => 1, 'product_id' => 1, 'quantity' => 1, 'price' => 1000, 'product' => ['title' => 'Product 1']]],
                1,
                1000,
                $dummyCartTotalsDTO
            ));

        $response = $this->postJson(route('cart.remove-coupon'));

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'items',
                         'summary',
                         'metadata'
                     ],
                 ])
                 ->assertJson([
                     'success' => true,
                     'message' => 'کد تخفیف با موفقیت حذف شد.',
                     'data' => [
                         'summary' => [
                             'totalPrice' => 1000, // قیمت پس از حذف تخفیف
                         ]
                     ]
                 ]);
    }

    //
    // تست‌های سناریوهای خطا
    //

    /**
     * تست افزودن محصول به سبد خرید بدون احراز هویت.
     *
     * @return void
     */
    public function test_guest_cannot_add_item_to_cart_without_authentication()
    {
        $product = Product::factory()->create(['stock' => 10]);

        // انتظار داریم که متد getOrCreateCart فراخوانی نشود یا با خطای احراز هویت مواجه شود
        // در این سناریو، لاراول به طور خودکار به صفحه لاگین ریدایرکت می‌کند یا 401 برمی‌گرداند.
        // ما اینجا فرض می‌کنیم که middleware 'auth' روی این route اعمال شده است.
        $response = $this->postJson(route('cart.add', ['product' => $product->id]), [
            'quantity' => 1,
        ]);

        $response->assertStatus(401); // Unauthenticated
    }

    /**
     * تست به‌روزرسانی آیتم سبد خرید توسط کاربر غیرمجاز.
     *
     * @return void
     */
    public function test_unauthorized_user_cannot_update_cart_item()
    {
        $owner = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::factory()->forUser($owner)->create();
        $cartItem = CartItem::factory()->for($cart)->for($product)->create(['quantity' => 1]);

        $this->actingAs($unauthorizedUser);

        $this->cartService->shouldReceive('userOwnsCartItem')
            ->once()
            ->andReturn(false); // شبیه‌سازی دسترسی غیرمجاز

        $response = $this->putJson(route('cart.update', ['cartItem' => $cartItem->id]), [
            'quantity' => 2,
        ]);

        $response->assertStatus(403) // Forbidden
                 ->assertJson([
                     'success' => false,
                     'message' => 'شما اجازه دسترسی به این آیتم سبد خرید را ندارید.',
                 ]);
    }

    /**
     * تست حذف آیتم سبد خرید توسط کاربر غیرمجاز.
     *
     * @return void
     */
    public function test_unauthorized_user_cannot_remove_cart_item()
    {
        $owner = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::factory()->forUser($owner)->create();
        $cartItem = CartItem::factory()->for($cart)->for($product)->create(['quantity' => 1]);

        $this->actingAs($unauthorizedUser);

        $this->cartService->shouldReceive('userOwnsCartItem')
            ->once()
            ->andReturn(false); // شبیه‌سازی دسترسی غیرمجاز

        $response = $this->deleteJson(route('cart.remove', ['cartItem' => $cartItem->id]));

        $response->assertStatus(403) // Forbidden
                 ->assertJson([
                     'success' => false,
                     'message' => 'شما اجازه دسترسی به این آیتم سبد خرید را ندارید.',
                 ]);
    }

    /**
     * تست اعتبارسنجی تعداد نامعتبر هنگام به‌روزرسانی آیتم سبد خرید.
     *
     * @return void
     */
    public function test_update_cart_item_quantity_with_invalid_quantity()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $cartItem = CartItem::factory()->for($cart)->for($product)->create(['quantity' => 1]);
        $this->actingAs($user);

        $this->cartService->shouldReceive('userOwnsCartItem')
            ->once()
            ->andReturn(true);

        $response = $this->putJson(route('cart.update', ['cartItem' => $cartItem->id]), [
            'quantity' => 'abc', // تعداد نامعتبر
        ]);

        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * تست اعتبارسنجی کد کوپن نامعتبر هنگام اعمال کوپن.
     *
     * @return void
     */
    public function test_apply_coupon_with_invalid_coupon_code()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $cart = Cart::factory()->forUser($user)->create();
        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        $response = $this->postJson(route('cart.apply-coupon'), [
            'coupon_code' => '', // کد کوپن خالی
        ]);

        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJsonValidationErrors(['coupon_code']);
    }

    /**
     * تست اعمال کوپن ناموجود.
     *
     * @return void
     */
    public function test_cannot_apply_non_existent_coupon()
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create();
        $this->actingAs($user);

        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        // Mock کردن applyCoupon برای بازگرداندن پاسخ ناموفق
        $this->cartService->shouldReceive('applyCoupon')
            ->once()
            ->andReturn(CartOperationResponse::error('کد تخفیف نامعتبر است.', 404));

        $response = $this->postJson(route('cart.apply-coupon'), [
            'coupon_code' => 'NONEXISTENT',
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'کد تخفیف نامعتبر است.',
                 ]);
    }

    /**
     * تست حذف کوپن از سبد خرید بدون کوپن.
     *
     * @return void
     */
    public function test_cannot_remove_coupon_from_cart_without_coupon()
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->forUser($user)->create(['coupon_id' => null]); // سبد خرید بدون کوپن
        $this->actingAs($user);

        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        // Mock کردن removeCoupon برای بازگرداندن پاسخ ناموفق
        $this->cartService->shouldReceive('removeCoupon')
            ->once()
            ->andReturn(CartOperationResponse::error('هیچ کد تخفیفی برای حذف وجود ندارد.', 400));

        $response = $this->postJson(route('cart.remove-coupon'));

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'هیچ کد تخفیفی برای حذف وجود ندارد.',
                 ]);
    }
}