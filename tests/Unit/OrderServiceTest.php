<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\OrderService;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Models\Product; // اضافه شده: برای ساخت Product در تست
use App\Models\CartItem; // اضافه شده: برای ساخت CartItem در تست
use App\Repositories\OrderRepository; // اضافه شده: برای Mock کردن OrderRepository
use App\Services\Contracts\CartServiceInterface; // اضافه شده: برای Mock کردن CartService
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Illuminate\Support\Facades\Event; // اضافه شده: برای Mock کردن Event Facade
use Illuminate\Support\Facades\Config; // اضافه شده: برای Mock کردن Config Facade
use App\Exceptions\Cart\InsufficientStockException; // اضافه شده: برای تست موجودی ناکافی

class OrderServiceTest extends TestCase
{
    use RefreshDatabase; // استفاده از RefreshDatabase برای بازنشانی دیتابیس بین تست‌ها

    protected OrderService $orderService;
    protected OrderRepository $orderRepositoryMock; // Mock برای OrderRepository
    protected CartServiceInterface $cartServiceMock; // Mock برای CartService

    protected function setUp(): void
    {
        parent::setUp();

        // Mock کردن OrderRepository
        $this->orderRepositoryMock = Mockery::mock(OrderRepository::class);
        // Mock کردن CartServiceInterface
        $this->cartServiceMock = Mockery::mock(CartServiceInterface::class);

        // Mock کردن Event و Config Facades
        Event::fake(); // برای جلوگیری از ارسال رویدادهای واقعی در تست
        Config::shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
            // شبیه سازی رفتار config() helper در تست
            if ($key === 'cart.guest_cart_expiry_days') return 7;
            // می توانید سایر مقادیر config مورد نیاز را اینجا اضافه کنید
            return $default;
        });


        // نمونه‌سازی OrderService با وابستگی‌های Mock شده
        $this->orderService = new OrderService($this->orderRepositoryMock);

        // تنظیمات پیش‌فرض برای Mock ها
        $this->orderRepositoryMock->shouldReceive('createOrder')->andReturnUsing(function ($data) {
            return Order::create($data); // استفاده از مدل واقعی برای ایجاد سفارش
        });
        $this->orderRepositoryMock->shouldReceive('addOrderItem')->andReturn(true); // Mock کردن addOrderItem
        $this->orderRepositoryMock->shouldReceive('addOrderItems')->andReturn(true); // Mock کردن addOrderItems (اگر استفاده می‌شود)

        $this->cartServiceMock->shouldReceive('getOrCreateCart')->andReturnUsing(function ($user, $sessionId) {
            return Cart::firstOrCreate(['user_id' => $user?->id, 'session_id' => $sessionId]);
        });
        $this->cartServiceMock->shouldReceive('getCartContents')->andReturnUsing(function ($cart) {
            $items = $cart->items->map(function ($item) {
                return [
                    'cart_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->quantity * $item->price,
                    'product_name' => $item->product->title, // فرض می‌کنیم محصول دارای 'title' است
                    'product_price' => $item->price,
                    'stock' => $item->product->stock,
                    'image' => 'https://placehold.co/64x64/E0F2F1/004D40?text=Product',
                ];
            })->toArray();
            $totalQuantity = $cart->items->sum('quantity');
            $totalPrice = $cart->items->sum(fn($item) => $item->quantity * $item->price);
            return new \App\Services\Responses\CartContentsResponse($items, $totalQuantity, $totalPrice);
        });
        $this->cartServiceMock->shouldReceive('calculateCartTotals')->andReturnUsing(function ($cart) {
            $subtotal = $cart->items->sum(fn($item) => $item->quantity * $item->price);
            return ['subtotal' => $subtotal, 'shipping' => 0, 'tax' => 0, 'discount' => 0, 'total' => $subtotal];
        });
        $this->cartServiceMock->shouldReceive('clearCart')->andReturn(\App\Services\Responses\CartOperationResponse::success('Cart cleared.'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * A basic unit test example for placing an order.
     *
     * @return void
     */
    public function testPlaceOrder(): void
    {
        // ایجاد کاربر و سبد خرید
        $user = User::factory()->create();
        $cart = Cart::factory()->for($user)->create([
            'subtotal' => 100.00,
            'total' => 100.00,
        ]);

        // ایجاد محصولات و آیتم‌های سبد خرید
        $product1 = Product::factory()->create(['title' => 'چای سبز', 'price' => 50.00, 'stock' => 10]);
        $product2 = Product::factory()->create(['title' => 'چای سیاه', 'price' => 25.00, 'stock' => 5]);

        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product1->id, 'quantity' => 2, 'price' => $product1->price]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product2->id, 'quantity' => 2, 'price' => $product2->price]);

        $cart->load('items.product'); // اطمینان از لود شدن آیتم‌ها و محصولاتشان

        // Mock کردن متد createOrder در OrderRepository
        $this->orderRepositoryMock->shouldReceive('createOrder')
            ->once()
            ->andReturnUsing(function ($data) use ($user, $cart) {
                // شبیه‌سازی ایجاد سفارش واقعی
                return Order::create([
                    'user_id' => $user->id,
                    'total_amount' => $cart->getTotalPrice(), // استفاده از متد getTotalPrice مدل Cart
                    'status' => 'pending',
                    'shipping_method' => $data['shipping_method'],
                    'payment_method' => $data['payment_method'],
                    'address_id' => $data['selected_address_id'] ?? null,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone_number' => $data['phone_number'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'province' => $data['province'],
                    'postal_code' => $data['postal_code'],
                ]);
            });

        // Mock کردن متد addOrderItem در OrderRepository برای هر آیتم
        $this->orderRepositoryMock->shouldReceive('addOrderItem')
            ->times($cart->items->count())
            ->andReturn(true);

        // Mock کردن کاهش موجودی محصول
        $this->partialMock(Product::class, function (Mockery\MockInterface $mock) use ($product1, $product2) {
            $mock->shouldReceive('decrement')
                ->with('stock', 2)
                ->times(2); // برای هر دو محصول
        });

        // فراخوانی متد تحت تست
        $order = $this->orderService->createOrder([ // تغییر placeOrder به createOrder
            'shipping_method' => 'standard',
            'payment_method' => 'credit_card',
            'selected_address_id' => null, // یا یک ID واقعی
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '09123456789',
            'address' => 'Test Street',
            'city' => 'Test City',
            'province' => 'Test Province',
            'postal_code' => '1234567890',
        ], $cart, $user); // ارسال cart و user به متد

        // Assertions
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($cart->getTotalPrice(), $order->total_amount);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals('pending', $order->status);

        // Assert that the cart is cleared (این منطق در CheckoutController است، نه در OrderService)
        // OrderService فقط مسئول ایجاد سفارش و کاهش موجودی است.
        // بنابراین، این assertion از اینجا حذف می‌شود یا به تست CheckoutController منتقل می‌شود.
        // $cart->refresh();
        // $this->assertNull($cart->user_id);
    }

    /**
     * Test placing an order with insufficient stock.
     *
     * @return void
     */
    public function testPlaceOrderWithInsufficientStock(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->for($user)->create();

        // ایجاد محصول با موجودی کم
        $product = Product::factory()->create(['title' => 'چای کم موجودی', 'price' => 100, 'stock' => 1]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5, 'price' => $product->price]);
        $cart->load('items.product');

        // Mock کردن متد decrement در Product برای شبیه‌سازی خطای موجودی
        $this->partialMock(Product::class, function (Mockery\MockInterface $mock) {
            $mock->shouldReceive('decrement')
                ->andThrow(new InsufficientStockException('موجودی کافی نیست.')); // استفاده از InsufficientStockException واقعی
        });

        $this->expectException(InsufficientStockException::class); // انتظار InsufficientStockException
        $this->orderService->createOrder([ // تغییر placeOrder به createOrder
            'shipping_method' => 'standard',
            'payment_method' => 'credit_card',
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone_number' => '09123456789',
            'address' => 'Test Address',
            'city' => 'Test City',
            'province' => 'Test Province',
            'postal_code' => '1234567890',
        ], $cart, $user); // ارسال cart و user به متد
    }
}
