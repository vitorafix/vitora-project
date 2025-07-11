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
use App\Services\Responses\CartOperationResponse; // مطمئن شوید که این کلاس متدهای استاتیک success() و fail() را دارد
use App\Services\Responses\CartContentsResponse;
use App\DTOs\CartTotalsDTO; // برای ساخت CartTotalsDTO در تست‌ها
use Carbon\Carbon; // اضافه شده برای Carbon

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

        // Mock پیش‌فرض برای getCartContents:
        // این Mock تضمین می‌کند که getCartContents همیشه یک پاسخ معتبر (حتی اگر خالی) برمی‌گرداند.
        // این از خطاهای Mockery\Exception\InvalidCountException در تست‌هایی که getCartContents به صورت ضمنی فراخوانی می‌شود
        // (مثلاً توسط CartResource در متد additional) جلوگیری می‌کند.
        $this->cartService->shouldReceive('getCartContents')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($cart) {
                // یک CartContentsResponse خالی با totals صفر برمی‌گرداند
                $dummyCartTotalsDTO = new CartTotalsDTO(0, 0, 0, 0, 0);
                return new CartContentsResponse(
                    items: [],
                    totalQuantity: 0,
                    totalPrice: 0,
                    cartTotals: $dummyCartTotalsDTO
                );
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

        // Mock دقیق برای getCartContents در این تست
        $dummyCartTotalsDTO = new CartTotalsDTO(1000, 0, 0, 0, 1000);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                [
                    [
                        'id' => 1,
                        'product_id' => 1,
                        'quantity' => 1,
                        'price' => 1000,
                        'subtotal' => 1000, // اضافه شدن subtotal برای آیتم
                        'product' => [
                            'id' => 1,
                            'title' => 'Product 1',
                            'slug' => 'product-1',
                            'image' => 'img.jpg',
                            'stock' => 10
                        ],
                        'created_at' => now()->toDateTimeString(), // برای formatItems
                        'updated_at' => now()->toDateTimeString(), // برای formatItems
                    ]
                ],
                1,
                1000,
                $dummyCartTotalsDTO
            ));

        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertViewIs('cart'); // مطمئن شوید که ویو 'cart' رندر می‌شود
        $response->assertViewHas('cartContents');
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

        // Realistic Product Data
        $product = Product::factory()->create([
            'id' => 1,
            'title' => 'Test Product',
            'slug' => 'test-product',
            'image' => 'img.jpg',
            'stock' => 10,
            'price' => 1000, // Ensure product has a price for item calculation
        ]);

        $itemQuantity = 2;
        $itemUnitPrice = $product->price; // 1000
        $itemTotalPrice = $itemUnitPrice * $itemQuantity; // 2000

        // Realistic Cart Item Data (as expected by CartContentsResponse)
        $cartItemsData = [
            [
                'id' => 1, // CartItem ID
                'product_id' => $product->id,
                'quantity' => $itemQuantity,
                'price' => $itemUnitPrice, // Unit price of the item
                'subtotal' => $itemTotalPrice, // Total price for this item (quantity * unit price)
                'product' => $product->toArray(), // Full product data
                'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        ];

        // Realistic Cart Totals DTO
        $cartTotalsDTO = new CartTotalsDTO(
            subtotal: $itemTotalPrice, // 2000
            discount: 100,
            shipping: 50,
            tax: 150,
            total: $itemTotalPrice - 100 + 50 + 150 // 2000 - 100 + 50 + 150 = 2100
        );

        // Calculate overall totals for CartContentsResponse constructor
        $totalQuantity = $itemQuantity;
        $totalPrice = $cartTotalsDTO->total; // Use the final calculated total

        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                items: $cartItemsData,
                totalQuantity: $totalQuantity,
                totalPrice: $totalPrice,
                cartTotals: $cartTotalsDTO
            ));

        $response = $this->getJson(route('api.cart.getContents')); // Changed route to api.cart.getContents

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'سبد خرید با موفقیت بارگذاری شد', // From ApiCartController::getContents
                     'data' => [
                         'items' => [
                             [
                                 'id' => 1,
                                 'product' => [
                                     'id' => $product->id,
                                     'name' => $product->title, // 'title' from product, 'name' in resource
                                     'inStock' => true, // stock > 0
                                     'slug' => $product->slug,
                                     'image' => asset('storage/' . $product->image),
                                     'stockQuantity' => $product->stock,
                                 ],
                                 'quantity' => $itemQuantity,
                                 'unitPrice' => $itemUnitPrice,
                                 'totalPrice' => $itemTotalPrice,
                                 'formattedUnitPrice' => number_format($itemUnitPrice, 0, '.', ',') . ' تومان',
                                 'formattedTotalPrice' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                                 'addedAt' => $cartItemsData[0]['created_at'], // Match exact string
                                 'updatedAt' => $cartItemsData[0]['updated_at'], // Match exact string
                             ]
                         ],
                         'summary' => [
                             'totalQuantity' => $totalQuantity,
                             'totalPrice' => $totalPrice, // Final total
                             'isEmpty' => false,
                             'formattedTotalPrice' => number_format($totalPrice, 0, '.', ',') . ' تومان',
                             'currency' => 'IRR',
                             'subtotal' => $cartTotalsDTO->subtotal,
                             'formattedSubtotal' => number_format($cartTotalsDTO->subtotal, 0, '.', ',') . ' تومان',
                             'discount' => $cartTotalsDTO->discount,
                             'formattedDiscount' => number_format($cartTotalsDTO->discount, 0, '.', ',') . ' تومان',
                             'shipping' => $cartTotalsDTO->shipping,
                             'formattedShipping' => number_format($cartTotalsDTO->shipping, 0, '.', ',') . ' تومان',
                             'tax' => $cartTotalsDTO->tax,
                             'formattedTax' => number_format($cartTotalsDTO->tax, 0, '.', ',') . ' تومان',
                         ],
                         'metadata' => [
                             'itemCount' => count($cartItemsData), // Updated: itemCount is number of distinct items
                             // 'lastUpdated' and 'requestId' are dynamic, so not asserting exact values here,
                             // but structure can be asserted.
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
        $product = Product::factory()->create(['stock' => 10, 'price' => 500]);
        $this->actingAs($user);

        $cart = Cart::factory()->forUser($user)->create();
        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        $this->cartService->shouldReceive('addOrUpdateCartItem')
            ->once()
            ->andReturn(CartOperationResponse::success('محصول اضافه شد.'));

        $itemQuantity = 1;
        $itemUnitPrice = $product->price;
        $itemTotalPrice = $itemUnitPrice * $itemQuantity;

        $cartItemsData = [
            [
                'id' => 1,
                'product_id' => $product->id,
                'quantity' => $itemQuantity,
                'price' => $itemUnitPrice,
                'subtotal' => $itemTotalPrice,
                'product' => $product->toArray(),
                'created_at' => Carbon::now()->subMinutes(1)->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        ];

        $cartTotalsDTO = new CartTotalsDTO(
            subtotal: $itemTotalPrice,
            discount: 0,
            shipping: 0,
            tax: 0,
            total: $itemTotalPrice
        );

        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                items: $cartItemsData,
                totalQuantity: $itemQuantity,
                totalPrice: $itemTotalPrice,
                cartTotals: $cartTotalsDTO
            ));

        $response = $this->postJson(route('api.cart.add', ['product' => $product->id]), [ // Changed route to api.cart.add
            'quantity' => $itemQuantity,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'محصول با موفقیت به سبد خرید اضافه شد.',
                     'data' => [
                         'items' => [
                             [
                                 'id' => 1,
                                 'product' => [
                                     'id' => $product->id,
                                     'name' => $product->title,
                                     'inStock' => true,
                                     'slug' => $product->slug,
                                     'image' => asset('storage/' . $product->image),
                                     'stockQuantity' => $product->stock,
                                 ],
                                 'quantity' => $itemQuantity,
                                 'unitPrice' => $itemUnitPrice,
                                 'totalPrice' => $itemTotalPrice,
                                 'formattedUnitPrice' => number_format($itemUnitPrice, 0, '.', ',') . ' تومان',
                                 'formattedTotalPrice' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                                 'addedAt' => $cartItemsData[0]['created_at'],
                                 'updatedAt' => $cartItemsData[0]['updated_at'],
                             ]
                         ],
                         'summary' => [
                             'totalQuantity' => $itemQuantity,
                             'totalPrice' => $itemTotalPrice,
                             'isEmpty' => false,
                             'formattedTotalPrice' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                             'currency' => 'IRR',
                             'subtotal' => $itemTotalPrice,
                             'formattedSubtotal' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                             'discount' => 0,
                             'formattedDiscount' => '0 تومان',
                             'shipping' => 0,
                             'formattedShipping' => '0 تومان',
                             'tax' => 0,
                             'formattedTax' => '0 تومان',
                         ],
                         'metadata' => [
                             'itemCount' => count($cartItemsData), // Updated: itemCount is number of distinct items
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
            ->andReturn(CartOperationResponse::fail('موجودی کافی نیست.', 400)); // Changed to return CartOperationResponse::fail

        $response = $this->postJson(route('api.cart.add', ['product' => $product->id]), [ // Changed route to api.cart.add
            'quantity' => 10, // درخواست بیش از موجودی
        ]);

        $response->assertStatus(400)
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

        $this->cartService->shouldReceive('updateItemQuantity') // Changed to updateItemQuantity
            ->once()
            ->andReturn(CartOperationResponse::success('تعداد به‌روزرسانی شد.'));

        $this->cartService->shouldReceive('getCartById')
            ->once()
            ->andReturn($cart);

        $itemQuantity = 2;
        $itemUnitPrice = $product->price;
        $itemTotalPrice = $itemUnitPrice * $itemQuantity;

        $cartItemsData = [
            [
                'id' => $cartItem->id,
                'product_id' => $product->id,
                'quantity' => $itemQuantity,
                'price' => $itemUnitPrice,
                'subtotal' => $itemTotalPrice,
                'product' => $product->toArray(),
                'created_at' => Carbon::now()->subMinutes(1)->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        ];

        $cartTotalsDTO = new CartTotalsDTO(
            subtotal: $itemTotalPrice,
            discount: 0,
            shipping: 0,
            tax: 0,
            total: $itemTotalPrice
        );

        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                items: $cartItemsData,
                totalQuantity: $itemQuantity,
                totalPrice: $itemTotalPrice,
                cartTotals: $cartTotalsDTO
            ));

        $response = $this->postJson(route('api.cart.updateQuantity', ['cartItem' => $cartItem->id]), [ // Changed route to api.cart.updateQuantity and method to POST
            'quantity' => $itemQuantity,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'تعداد آیتم سبد خرید با موفقیت به‌روزرسانی شد.',
                     'data' => [
                         'items' => [
                             [
                                 'id' => $cartItem->id,
                                 'product' => [
                                     'id' => $product->id,
                                     'name' => $product->title,
                                     'inStock' => true,
                                     'slug' => $product->slug,
                                     'image' => asset('storage/' . $product->image),
                                     'stockQuantity' => $product->stock,
                                 ],
                                 'quantity' => $itemQuantity,
                                 'unitPrice' => $itemUnitPrice,
                                 'totalPrice' => $itemTotalPrice,
                                 'formattedUnitPrice' => number_format($itemUnitPrice, 0, '.', ',') . ' تومان',
                                 'formattedTotalPrice' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                                 'addedAt' => $cartItemsData[0]['created_at'],
                                 'updatedAt' => $cartItemsData[0]['updated_at'],
                             ]
                         ],
                         'summary' => [
                             'totalQuantity' => $itemQuantity,
                             'totalPrice' => $itemTotalPrice,
                             'isEmpty' => false,
                             'formattedTotalPrice' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                             'currency' => 'IRR',
                             'subtotal' => $itemTotalPrice,
                             'formattedSubtotal' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                             'discount' => 0,
                             'formattedDiscount' => '0 تومان',
                             'shipping' => 0,
                             'formattedShipping' => '0 تومان',
                             'tax' => 0,
                             'formattedTax' => '0 تومان',
                         ],
                         'metadata' => [
                             'itemCount' => count($cartItemsData), // Updated: itemCount is number of distinct items
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

        $this->cartService->shouldReceive('removeCartItem')
            ->once()
            ->andReturn(CartOperationResponse::success('آیتم حذف شد.'));

        // Mock کردن getCartContents پس از حذف (سبد خالی)
        $dummyCartTotalsDTO = new CartTotalsDTO(0, 0, 0, 0, 0);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse([], 0, 0, $dummyCartTotalsDTO));

        $response = $this->postJson(route('api.cart.removeItem', ['cartItem' => $cartItem->id])); // Changed route to api.cart.removeItem and method to POST

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'آیتم با موفقیت از سبد خرید حذف شد.',
                     'data' => [
                         'items' => [], // انتظار آرایه خالی
                         'summary' => [
                             'totalQuantity' => 0,
                             'totalPrice' => 0,
                             'isEmpty' => true,
                             'subtotal' => 0,
                             'discount' => 0,
                             'shipping' => 0,
                             'tax' => 0,
                             'formattedTotalPrice' => '0 تومان',
                             'formattedSubtotal' => '0 تومان',
                             'formattedDiscount' => '0 تومان',
                             'formattedShipping' => '0 تومان',
                             'formattedTax' => '0 تومان',
                         ],
                         'metadata' => [
                             'itemCount' => 0, // Updated: itemCount is number of distinct items
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

        $this->cartService->shouldReceive('clearCart')
            ->once()
            ->andReturn(CartOperationResponse::success('سبد خرید پاک شد.'));

        // Mock کردن getCartContents پس از پاکسازی (سبد خالی)
        $dummyCartTotalsDTO = new CartTotalsDTO(0, 0, 0, 0, 0);
        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse([], 0, 0, $dummyCartTotalsDTO));

        $response = $this->postJson(route('api.cart.clear')); // Changed route to api.cart.clear

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'سبد خرید با موفقیت پاک شد.',
                     'data' => [
                         'items' => [], // انتظار آرایه خالی
                         'summary' => [
                             'totalQuantity' => 0,
                             'totalPrice' => 0,
                             'isEmpty' => true,
                             'subtotal' => 0,
                             'discount' => 0,
                             'shipping' => 0,
                             'tax' => 0,
                             'formattedTotalPrice' => '0 تومان',
                             'formattedSubtotal' => '0 تومان',
                             'formattedDiscount' => '0 تومان',
                             'formattedShipping' => '0 تومان',
                             'formattedTax' => '0 تومان',
                         ],
                         'metadata' => [
                             'itemCount' => 0, // Updated: itemCount is number of distinct items
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

        $this->cartService->shouldReceive('applyCoupon')
            ->once()
            ->andReturn(CartOperationResponse::success('کوپن اعمال شد.'));

        $itemQuantity = 1;
        $itemUnitPrice = 1000;
        $itemTotalPrice = $itemUnitPrice * $itemQuantity; // 1000

        // Mock کردن getCartContents پس از اعمال کوپن
        $cartTotalsDTO = new CartTotalsDTO(
            subtotal: $itemTotalPrice, // 1000
            discount: $coupon->value, // 100
            shipping: 0,
            tax: 0,
            total: $itemTotalPrice - $coupon->value // 900
        );

        $cartItemsData = [
            [
                'id' => 1,
                'product_id' => 1,
                'quantity' => $itemQuantity,
                'price' => $itemUnitPrice,
                'subtotal' => $itemTotalPrice,
                'product' => [
                    'id' => 1,
                    'title' => 'Product 1',
                    'slug' => 'product-1',
                    'image' => 'img.jpg',
                    'stock' => 10
                ],
                'created_at' => Carbon::now()->subMinutes(1)->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        ];

        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                items: $cartItemsData,
                totalQuantity: $itemQuantity,
                totalPrice: $cartTotalsDTO->total,
                cartTotals: $cartTotalsDTO
            ));

        $response = $this->postJson(route('api.cart.applyCoupon'), [ // Changed route to api.cart.applyCoupon
            'coupon_code' => 'TESTCOUPON',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'کد تخفیف با موفقیت اعمال شد.',
                     'data' => [
                         'items' => [
                             [
                                 'id' => 1,
                                 'product' => [
                                     'id' => 1,
                                     'name' => 'Product 1',
                                     'inStock' => true,
                                     'slug' => 'product-1',
                                     'image' => asset('storage/img.jpg'),
                                     'stockQuantity' => 10,
                                 ],
                                 'quantity' => $itemQuantity,
                                 'unitPrice' => $itemUnitPrice,
                                 'totalPrice' => $itemTotalPrice,
                                 'formattedUnitPrice' => number_format($itemUnitPrice, 0, '.', ',') . ' تومان',
                                 'formattedTotalPrice' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                                 'addedAt' => $cartItemsData[0]['created_at'],
                                 'updatedAt' => $cartItemsData[0]['updated_at'],
                             ]
                         ],
                         'summary' => [
                             'totalQuantity' => $itemQuantity,
                             'totalPrice' => $cartTotalsDTO->total,
                             'isEmpty' => false,
                             'formattedTotalPrice' => number_format($cartTotalsDTO->total, 0, '.', ',') . ' تومان',
                             'currency' => 'IRR',
                             'subtotal' => $cartTotalsDTO->subtotal,
                             'formattedSubtotal' => number_format($cartTotalsDTO->subtotal, 0, '.', ',') . ' تومان',
                             'discount' => $cartTotalsDTO->discount,
                             'formattedDiscount' => number_format($cartTotalsDTO->discount, 0, '.', ',') . ' تومان',
                             'shipping' => $cartTotalsDTO->shipping,
                             'formattedShipping' => number_format($cartTotalsDTO->shipping, 0, '.', ',') . ' تومان',
                             'tax' => $cartTotalsDTO->tax,
                             'formattedTax' => number_format($cartTotalsDTO->tax, 0, '.', ',') . ' تومان',
                         ],
                         'metadata' => [
                             'itemCount' => count($cartItemsData), // Updated: itemCount is number of distinct items
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

        $this->cartService->shouldReceive('removeCoupon')
            ->once()
            ->andReturn(CartOperationResponse::success('کوپن حذف شد.'));

        $itemQuantity = 1;
        $itemUnitPrice = 1000;
        $itemTotalPrice = $itemUnitPrice * $itemQuantity; // 1000

        // Mock کردن getCartContents پس از حذف کوپن (بازگشت به قیمت اصلی)
        $cartTotalsDTO = new CartTotalsDTO(
            subtotal: $itemTotalPrice, // 1000
            discount: 0, // تخفیف حذف شده
            shipping: 0,
            tax: 0,
            total: $itemTotalPrice // 1000
        );

        $cartItemsData = [
            [
                'id' => 1,
                'product_id' => 1,
                'quantity' => $itemQuantity,
                'price' => $itemUnitPrice,
                'subtotal' => $itemTotalPrice,
                'product' => [
                    'id' => 1,
                    'title' => 'Product 1',
                    'slug' => 'product-1',
                    'image' => 'img.jpg',
                    'stock' => 10
                ],
                'created_at' => Carbon::now()->subMinutes(1)->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        ];

        $this->cartService->shouldReceive('getCartContents')
            ->once()
            ->andReturn(new CartContentsResponse(
                items: $cartItemsData,
                totalQuantity: $itemQuantity,
                totalPrice: $cartTotalsDTO->total,
                cartTotals: $cartTotalsDTO
            ));

        $response = $this->postJson(route('api.cart.removeCoupon')); // Changed route to api.cart.removeCoupon

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'کد تخفیف با موفقیت حذف شد.',
                     'data' => [
                         'items' => [
                             [
                                 'id' => 1,
                                 'product' => [
                                     'id' => 1,
                                     'name' => 'Product 1',
                                     'inStock' => true,
                                     'slug' => 'product-1',
                                     'image' => asset('storage/' . 'img.jpg'),
                                     'stockQuantity' => 10,
                                 ],
                                 'quantity' => $itemQuantity,
                                 'unitPrice' => $itemUnitPrice,
                                 'totalPrice' => $itemTotalPrice,
                                 'formattedUnitPrice' => number_format($itemUnitPrice, 0, '.', ',') . ' تومان',
                                 'formattedTotalPrice' => number_format($itemTotalPrice, 0, '.', ',') . ' تومان',
                                 'addedAt' => $cartItemsData[0]['created_at'],
                                 'updatedAt' => $cartItemsData[0]['updated_at'],
                             ]
                         ],
                         'summary' => [
                             'totalQuantity' => $itemQuantity,
                             'totalPrice' => $cartTotalsDTO->total,
                             'isEmpty' => false,
                             'formattedTotalPrice' => number_format($cartTotalsDTO->total, 0, '.', ',') . ' تومان',
                             'currency' => 'IRR',
                             'subtotal' => $cartTotalsDTO->subtotal,
                             'formattedSubtotal' => number_format($cartTotalsDTO->subtotal, 0, '.', ',') . ' تومان',
                             'discount' => $cartTotalsDTO->discount,
                             'formattedDiscount' => number_format($cartTotalsDTO->discount, 0, '.', ',') . ' تومان',
                             'shipping' => $cartTotalsDTO->shipping,
                             'formattedShipping' => number_format($cartTotalsDTO->shipping, 0, '.', ',') . ' تومان',
                             'tax' => $cartTotalsDTO->tax,
                             'formattedTax' => number_format($cartTotalsDTO->tax, 0, '.', ',') . ' تومان',
                         ],
                         'metadata' => [
                             'itemCount' => count($cartItemsData), // Updated: itemCount is number of distinct items
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

        // در این سناریو، لاراول به طور خودکار به صفحه لاگین ریدایرکت می‌کند یا 401 برمی‌گرداند.
        // ما اینجا فرض می‌کنیم که middleware 'auth' روی این route اعمال شده است.
        // نیازی به Mock کردن getOrCreateCart نیست، زیرا middleware آن را قبل از کنترلر متوقف می‌کند.
        $response = $this->postJson(route('api.cart.add', ['product' => $product->id]), [ // Changed route to api.cart.add
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

        $response = $this->postJson(route('api.cart.updateQuantity', ['cartItem' => $cartItem->id]), [ // Changed route to api.cart.updateQuantity and method to POST
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

        $response = $this->postJson(route('api.cart.removeItem', ['cartItem' => $cartItem->id])); // Changed route to api.cart.removeItem and method to POST

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

        $response = $this->postJson(route('api.cart.updateQuantity', ['cartItem' => $cartItem->id]), [ // Changed route to api.cart.updateQuantity and method to POST
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

        $response = $this->postJson(route('api.cart.applyCoupon'), [ // Changed route to api.cart.applyCoupon
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

        $this->cartService->shouldReceive('applyCoupon')
            ->once()
            ->andReturn(CartOperationResponse::fail('کد تخفیف نامعتبر است.', 404));

        $response = $this->postJson(route('api.cart.applyCoupon'), [ // Changed route to api.cart.applyCoupon
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
        $cart = Cart::factory()->forUser($user)->create(['coupon_id' => null]);
        $this->actingAs($user);

        $this->cartService->shouldReceive('getOrCreateCart')
            ->once()
            ->andReturn($cart);

        $this->cartService->shouldReceive('removeCoupon')
            ->once()
            ->andReturn(CartOperationResponse::fail('هیچ کد تخفیفی برای حذف وجود ندارد.', 400));

        $response = $this->postJson(route('api.cart.removeCoupon')); // Changed route to api.cart.removeCoupon

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'هیچ کد تخفیفی برای حذف وجود ندارد.',
                 ]);
    }
}
