<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class OrderService
{
    protected OrderRepository $orderRepository;
    protected CartService $cartService; // برای تعامل با منطق سبد خرید

    /**
     * Constructor for OrderService.
     * سازنده کلاس OrderService.
     *
     * @param OrderRepository $orderRepository
     * @param CartService $cartService
     */
    public function __construct(OrderRepository $orderRepository, CartService $cartService)
    {
        $this->orderRepository = $orderRepository;
        $this->cartService = $cartService;
    }

    /**
     * Place a new order from the current cart.
     * یک سفارش جدید را از سبد خرید فعلی ثبت می‌کند.
     *
     * @param array $orderData داده‌های سفارش شامل اطلاعات مشتری و آدرس.
     * @return Order
     * @throws \Exception در صورت بروز خطا در فرآیند ثبت سفارش.
     */
    public function placeOrder(array $orderData): Order
    {
        Log::info('Attempting to place new order', ['order_data' => $orderData]);

        // شروع تراکنش پایگاه داده برای اطمینان از یکپارچگی عملیات
        DB::beginTransaction();

        try {
            // دریافت سبد خرید فعلی کاربر (یا مهمان)
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $cartItems = $cart->items()->with('product')->get();

            // بررسی خالی نبودن سبد خرید
            if ($cartItems->isEmpty()) {
                throw new \Exception('سبد خرید شما خالی است و نمی‌توانید سفارش ثبت کنید.');
            }

            // محاسبه جمع کل سفارش
            $totalAmount = $cart->getTotalPrice();

            // آماده‌سازی داده‌های سفارش برای ذخیره در دیتابیس
            $orderCreationData = [
                'user_id' => Auth::id(), // اگر کاربر لاگین باشد، user_id ست می‌شود، در غیر این صورت null
                'session_id' => Auth::check() ? null : Session::getId(), // اگر کاربر مهمان باشد، session_id ست می‌شود
                'total_amount' => $totalAmount,
                'status' => 'pending', // وضعیت اولیه سفارش
                'first_name' => $orderData['first_name'],
                'last_name' => $orderData['last_name'],
                'phone_number' => $orderData['phone_number'],
                'address' => $orderData['address'],
                'city' => $orderData['city'],
                'province' => $orderData['province'],
                'postal_code' => $orderData['postal_code'],
                'shipping_method' => $orderData['shipping_method'], // اضافه شدن روش ارسال
                'payment_method' => $orderData['payment_method'],   // اضافه شدن روش پرداخت
                'delivery_notes' => $orderData['delivery_notes'] ?? null, // اضافه شدن یادداشت برای پیک
            ];

            // ایجاد رکورد سفارش در جدول orders از طریق Repository
            $order = $this->orderRepository->createOrder($orderCreationData);
            Log::info('Order created successfully', ['order_id' => $order->id]);

            // ایجاد آیتم‌های سفارش و کاهش موجودی محصول
            foreach ($cartItems as $cartItem) {
                $product = Product::find($cartItem->product_id);

                // بررسی مجدد موجودی قبل از کاهش (Double Check)
                if (!$product || $product->stock < $cartItem->quantity) {
                    throw new \Exception('موجودی کافی برای محصول ' . ($product ? $product->title : 'ناشناس') . ' وجود ندارد.');
                }

                // کاهش موجودی محصول در انبار
                $product->decrement('stock', $cartItem->quantity);
                Log::info('Product stock decremented', ['product_id' => $product->id, 'quantity' => $cartItem->quantity, 'new_stock' => $product->stock]);
            }

            // اضافه کردن آیتم‌های سبد خرید به سفارش از طریق Repository
            $this->orderRepository->addOrderItems($order, $cartItems);
            Log::info('Order items added to order', ['order_id' => $order->id]);

            // خالی کردن سبد خرید و حذف آن پس از ثبت موفق سفارش
            $cart->items()->delete(); // حذف تمام آیتم‌های سبد خرید
            $cart->delete(); // حذف خود سبد خرید
            Log::info('Cart cleared and deleted after successful order placement', ['cart_id' => $cart->id]);

            // اتمام تراکنش و ذخیره تغییرات
            DB::commit();
            Log::info('Order placement transaction committed successfully', ['order_id' => $order->id]);

            // می‌توانید در اینجا منطق ارسال ایمیل تایید، پیامک و ... را اضافه کنید
            // dispatch(new SendOrderConfirmationEmail($order));

            return $order;

        } catch (\Exception $e) {
            // در صورت بروز خطا، تراکنش را Rollback کنید
            DB::rollBack();
            Log::error('Order placement failed: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'order_data' => $orderData
            ]);
            throw $e; // پرتاب مجدد استثنا برای مدیریت در سطح بالاتر (کنترلر)
        }
    }
}
