<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product; // برای کاهش موجودی محصول
use App\Models\User; // برای استفاده از مدل User
use App\Repositories\OrderRepository;
// use App\Services\Contracts\CartServiceInterface; // حذف شد: دیگر نیازی به تزریق CartService نیست
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Auth; // حذف شد: user از طریق پارامتر دریافت می‌شود
// use Illuminate\Support\Facades\Session; // حذف شد: session_id از طریق cart یا user دریافت می‌شود

// اگر اینترفیس App\Contracts\Services\OrderService را دارید، باید آن را implement کنید
// use App\Contracts\Services\OrderService as OrderServiceInterface;

class OrderService // implements OrderServiceInterface // اگر اینترفیس را دارید، این خط را فعال کنید
{
    protected OrderRepository $orderRepository;
    // protected CartService $cartService; // حذف شد: سبد خرید مستقیماً به متد createOrder ارسال می‌شود

    /**
     * Constructor for OrderService.
     * سازنده کلاس OrderService.
     *
     * @param OrderRepository $orderRepository
     * @param CartService $cartService // حذف شد
     */
    public function __construct(OrderRepository $orderRepository /*, CartService $cartService */)
    {
        $this->orderRepository = $orderRepository;
        // $this->cartService = $cartService; // حذف شد
    }

    /**
     * Create a new order from the given cart and order data.
     * یک سفارش جدید را از سبد خرید و داده‌های سفارش مشخص شده ثبت می‌کند.
     *
     * @param array $orderData داده‌های سفارش شامل اطلاعات مشتری و آدرس (از PlaceOrderRequest).
     * @param Cart $cart سبد خرید فعلی که سفارش از آن ایجاد می‌شود.
     * @param User|null $user کاربر احراز هویت شده، در صورت وجود.
     * @return Order
     * @throws \Exception در صورت بروز خطا در فرآیند ثبت سفارش.
     */
    public function createOrder(array $orderData, Cart $cart, ?User $user = null): Order
    {
        Log::info('Attempting to create new order', ['order_data' => $orderData, 'cart_id' => $cart->id, 'user_id' => $user ? $user->id : null]);

        // شروع تراکنش پایگاه داده برای اطمینان از یکپارچگی عملیات
        DB::beginTransaction();

        try {
            // اطمینان از لود شدن آیتم‌های سبد خرید با محصولات مرتبط
            $cart->loadMissing('items.product', 'items.productVariant');
            $cartItems = $cart->items;

            // بررسی خالی نبودن سبد خرید (اگرچه CheckoutController هم این کار را می‌کند، اینجا برای اطمینان مجدد است)
            if ($cartItems->isEmpty()) {
                throw new \Exception('سبد خرید شما خالی است و نمی‌توانید سفارش ثبت کنید.');
            }

            // محاسبه جمع کل سفارش (می‌تواند از cart->total یا cart->subtotal و محاسبه حمل و نقل/تخفیف باشد)
            // با توجه به اینکه cartTotals از CheckoutController هم می‌آید، می‌توانید از آن استفاده کنید
            // یا getTotalPrice() را از مدل Cart فراخوانی کنید.
            $totalAmount = $cart->getTotalPrice(); // فرض می‌کنیم این متد قیمت کل را محاسبه می‌کند

            // آماده‌سازی داده‌های سفارش برای ذخیره در دیتابیس
            $orderCreationData = [
                'user_id' => $user ? $user->id : null, // اگر کاربر لاگین باشد
                'session_id' => $user ? null : $cart->session_id, // اگر کاربر مهمان باشد، session_id سبد خرید را استفاده کنید
                'total_amount' => $totalAmount,
                'status' => 'pending', // وضعیت اولیه سفارش
                // اطلاعات آدرس و تماس از $orderData (که از PlaceOrderRequest می‌آید)
                'first_name' => $orderData['first_name'] ?? ($user->name ?? null), // اگر از آدرس انتخاب شده باشد، این فیلدها ممکن است در $orderData نباشند
                'last_name' => $orderData['last_name'] ?? ($user->lastname ?? null),
                'phone_number' => $orderData['phone_number'] ?? ($user->phone_number ?? null),
                'address' => $orderData['address'] ?? null,
                'city' => $orderData['city'] ?? null,
                'province' => $orderData['province'] ?? null,
                'postal_code' => $orderData['postal_code'] ?? null,
                'shipping_method' => $orderData['shipping_method'],
                'payment_method' => $orderData['payment_method'],
                'delivery_notes' => $orderData['delivery_notes'] ?? null,
                'address_id' => $orderData['selected_address_id'] ?? null, // اگر آدرس موجود انتخاب شده باشد
            ];

            // اگر آدرس از طریق selected_address_id انتخاب شده است، جزئیات آن را از مدل Address دریافت کنید
            if (isset($orderData['selected_address_id']) && $orderData['selected_address_id'] !== 'new') {
                $address = $user->addresses()->find($orderData['selected_address_id']);
                if ($address) {
                    $orderCreationData['first_name'] = $address->first_name;
                    $orderCreationData['last_name'] = $address->last_name;
                    $orderCreationData['phone_number'] = $address->phone_number;
                    $orderCreationData['address'] = $address->address;
                    $orderCreationData['city'] = $address->city;
                    $orderCreationData['province'] = $address->province;
                    $orderCreationData['postal_code'] = $address->postal_code;
                }
            }


            // ایجاد رکورد سفارش در جدول orders از طریق Repository
            $order = $this->orderRepository->createOrder($orderCreationData);
            Log::info('Order created successfully', ['order_id' => $order->id]);

            // ایجاد آیتم‌های سفارش و کاهش موجودی محصول
            foreach ($cartItems as $cartItem) {
                // استفاده از product یا productVariant لود شده از cartItem
                $entityForStock = $cartItem->productVariant ?? $cartItem->product;

                // بررسی مجدد موجودی قبل از کاهش (Double Check)
                if (!$entityForStock || $entityForStock->stock < $cartItem->quantity) {
                    throw new \Exception('موجودی کافی برای محصول ' . ($entityForStock ? $entityForStock->title : 'ناشناس') . ' وجود ندارد.');
                }

                // کاهش موجودی محصول در انبار
                $entityForStock->decrement('stock', $cartItem->quantity);
                Log::info('Product stock decremented', ['product_id' => $entityForStock->id, 'quantity' => $cartItem->quantity, 'new_stock' => $entityForStock->stock]);

                // اضافه کردن آیتم به سفارش
                $this->orderRepository->addOrderItem($order, [
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    // می‌توانید اطلاعات بیشتری از محصول را اینجا ذخیره کنید
                ]);
            }

            // اتمام تراکنش و ذخیره تغییرات
            DB::commit();
            Log::info('Order creation transaction committed successfully', ['order_id' => $order->id]);

            // می‌توانید در اینجا منطق ارسال ایمیل تایید، پیامک و ... را اضافه کنید
            // dispatch(new SendOrderConfirmationEmail($order));

            return $order;

        } catch (\Throwable $e) {
            // در صورت بروز خطا، تراکنش را Rollback کنید
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'order_data' => $orderData,
                'cart_id' => $cart->id,
                'user_id' => $user ? $user->id : null
            ]);
            throw $e; // پرتاب مجدد استثنا برای مدیریت در سطح بالاتر (کنترلر)
        }
    }
}
