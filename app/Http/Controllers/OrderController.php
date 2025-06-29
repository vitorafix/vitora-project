<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // برای لاگ کردن خطاها

class OrderController extends Controller
{
    /**
     * Helper method to get the current user's cart or create one if it doesn't exist.
     * این متد هر دو نوع کاربر احراز هویت شده و مهمان را مدیریت می‌کند.
     *
     * @return \App\Models\Cart
     */
    private function getOrCreateCart(): Cart
    {
        if (Auth::check()) {
            // برای کاربران لاگین شده: سبد خرید بر اساس user_id
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        } else {
            // برای کاربران مهمان: سبد خرید بر اساس session_id
            $sessionId = Session::getId();
            $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        }
        return $cart;
    }

    /**
     * Display the checkout page with cart contents.
     * صفحه تسویه حساب را با محتویات سبد خرید نمایش می‌دهد.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $cart = $this->getOrCreateCart();
        // اطمینان از eager loading محصول برای جلوگیری از N+1 problem در view
        $cartItems = $cart->items()->with('product')->get();

        // اگر سبد خرید خالی است، به صفحه سبد خرید یا محصولات هدایت کنید
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'سبد خرید شما خالی است و نمی‌توانید سفارش ثبت کنید.');
        }

        // اگر کاربر لاگین نکرده است، به صفحه لاگین/ثبت نام هدایت کنید (اختیاری، بستگی به سیاست فروشگاه)
        // در این پروژه، فعلاً اجازه می‌دهیم مهمان‌ها نیز تسویه حساب کنند.

        return view('checkout', compact('cartItems', 'cart')); // ارسال $cart به ویو برای دسترسی به متدهایی مانند getTotalPrice
    }

    /**
     * Process placing an order from the cart.
     * عملیات ثبت سفارش را از سبد خرید پردازش می‌کند.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        // === خط دیباگ: برای لاگ کردن درخواست در ابتدای متد ===
        // این لاگ در storage/logs/laravel.log ظاهر می‌شود.
        Log::info('Entering PlaceOrder method. Request all: ' . json_encode($request->all()));

        try {
            // 1. اعتبار سنجی اطلاعات آدرس و سایر فیلدهای لازم
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'required|string|regex:/^09[0-9]{9}$/|max:11', // فرض بر فرمت شماره موبایل ایران
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'postal_code' => 'required|string|regex:/^[0-9]{10}$/', // کد پستی 10 رقمی
            ]);

            $cart = $this->getOrCreateCart();
            $cartItems = $cart->items()->with('product')->get();

            // 2. بررسی خالی نبودن سبد خرید قبل از شروع تراکنش
            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'سبد خرید شما خالی است و نمی‌توانید سفارش ثبت کنید.'], 400);
            }

            // 3. شروع تراکنش پایگاه داده
            DB::beginTransaction();

            $totalAmount = $cart->getTotalPrice();

            // 4. ایجاد رکورد سفارش در جدول orders
            $order = Order::create([
                'user_id' => Auth::id(), // اگر کاربر لاگین باشد، user_id ست می‌شود، در غیر این صورت null
                'session_id' => Auth::check() ? null : Session::getId(), // اگر کاربر مهمان باشد، session_id ست می‌شود
                'total_amount' => $totalAmount,
                'status' => 'pending', // وضعیت اولیه سفارش
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'phone_number' => $request->input('phone_number'),
                'address' => $request->input('address'),
                'city' => $request->input('city'),
                'province' => $request->input('province'),
                'postal_code' => $request->input('postal_code'),
            ]);

            // 5. ایجاد آیتم‌های سفارش و کاهش موجودی محصول
            foreach ($cartItems as $cartItem) {
                $product = Product::find($cartItem->product_id);

                // بررسی مجدد موجودی قبل از کاهش (Double Check)
                if (!$product || $product->stock < $cartItem->quantity) {
                    DB::rollBack(); // بازگرداندن تمام تغییرات دیتابیس در صورت کمبود موجودی
                    return response()->json(['message' => 'موجودی کافی برای محصول ' . ($product ? $product->title : 'ناشناس') . ' وجود ندارد.'], 400);
                }

                // ایجاد آیتم سفارش جدید
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price, // قیمت از CartItem گرفته می‌شود (ثابت در زمان اضافه شدن به سبد)
                ]);

                // کاهش موجودی محصول در انبار
                $product->decrement('stock', $cartItem->quantity);
            }

            // 6. خالی کردن سبد خرید و حذف آن پس از ثبت موفق سفارش
            $cart->items()->delete(); // حذف تمام آیتم‌های سبد خرید
            $cart->delete(); // حذف خود سبد خرید

            // 7. اتمام تراکنش و ذخیره تغییرات
            DB::commit();

            // 8. بازگرداندن پاسخ موفقیت‌آمیز
            return response()->json([
                'message' => 'سفارش شما با موفقیت ثبت شد!',
                'orderId' => $order->id
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // در صورت خطای اعتبارسنجی
            Log::error('Order Validation Error: ' . $e->getMessage(), ['errors' => $e->errors()]);
            // اگر از AJAX استفاده می‌کنید، بهتر است خطاها را به صورت structured برگردانید
            return response()->json([
                'message' => 'خطا در اطلاعات ورودی. لطفاً فیلدها را بررسی کنید.',
                'errors' => $e->errors()
            ], 422); // Unprocessable Entity
        } catch (\Exception $e) {
            // در صورت هر خطای دیگری، تراکنش را Rollback کنید
            DB::rollBack();
            // ثبت خطا در لاگ برای اشکال‌زدایی
            Log::error('Order Placement Error: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in file ' . $e->getFile());
            // بازگرداندن پاسخ خطا
            return response()->json(['message' => 'خطا در ثبت سفارش. لطفاً دوباره تلاش کنید.'], 500);
        }
    }

    /**
     * Display the order confirmation page.
     * صفحه تایید سفارش را نمایش می‌دهد.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\View\View
     */
    public function showConfirmation(Order $order): \Illuminate\View\View
    {
        // اطمینان از اینکه کاربر فقط می‌تواند سفارشات خودش را مشاهده کند (اختیاری اما توصیه می‌شود)
        // اگر کاربر لاگین است و سفارش مربوط به او نیست، یا اگر مهمان است و session_id تطابق ندارد.
        if (Auth::check() && $order->user_id !== Auth::id()) {
            abort(403, 'شما اجازه دسترسی به این سفارش را ندارید.');
        } elseif (!Auth::check() && $order->session_id !== Session::getId()) {
            abort(403, 'شما اجازه دسترسی به این سفارش را ندارید.');
        }

        // eager loading آیتم‌های سفارش و محصولات مرتبط برای نمایش در صفحه تایید
        $order->load('items.product');
        return view('order-confirmation', compact('order'));
    }
}
