<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Address; // اضافه کردن ایمپورت Address model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // برای لاگ کردن خطاها
use Illuminate\View\View; // اضافه کردن ایمپورت View برای بازگشت مناسب متد
use Illuminate\Http\RedirectResponse; // اضافه کردن ایمپورت RedirectResponse
use App\Http\Requests\PlaceOrderRequest; // ایمپورت کردن Form Request جدید
use App\Services\OrderService; // ایمپورت کردن OrderService جدید

class OrderController extends Controller
{
    protected OrderService $orderService;

    /**
     * Constructor for OrderController.
     * سازنده کنترلر OrderController.
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

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
     * Display the checkout page with cart contents OR Display user's orders.
     * این متد حالا برای دو منظور استفاده می‌شود: نمایش صفحه تسویه حساب (checkout.index) و نمایش سفارشات کاربر (profile.orders.index).
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request): View|RedirectResponse
    {
        // تشخیص اینکه این درخواست از کدام route name آمده است
        $routeName = $request->route()->getName();

        if ($routeName === 'checkout.index') {
            // منطق برای نمایش صفحه تسویه حساب (checkout)
            $cart = $this->getOrCreateCart();
            $cartItems = $cart->items()->with('product')->get();

            if ($cartItems->isEmpty()) {
                return redirect()->route('cart.index')->with('error', 'سبد خرید شما خالی است و نمی‌توانید سفارش ثبت کنید.');
            }

            // --- اضافه شده: واکشی آدرس‌های کاربر و آدرس پیش‌فرض ---
            $addresses = collect(); // پیش‌فرض یک کالکشن خالی
            $defaultAddress = null;

            if (Auth::check()) {
                $userAddresses = Auth::user()->addresses()->orderBy('is_default', 'desc')->get();
                $addresses = $userAddresses;
                // پیدا کردن آدرس پیش‌فرض یا اولین آدرس اگر پیش‌فرضی تنظیم نشده باشد
                $defaultAddress = $userAddresses->where('is_default', true)->first() ?? $userAddresses->first();
            }
            // --- پایان اضافه شده ---

            return view('checkout', compact('cartItems', 'cart', 'addresses', 'defaultAddress'));
        } elseif ($routeName === 'profile.orders.index') {
            // منطق برای نمایش لیست سفارشات کاربر در داشبورد
            if (!Auth::check()) {
                return redirect()->route('auth.mobile-login-form')->with('error', 'برای مشاهده سفارشات خود، ابتدا وارد شوید.');
            }

            $user = Auth::user();
            // واکشی سفارشات کاربر با آیتم‌ها و محصولات مرتبط (order items and their products)
            // از orderBy برای نمایش جدیدترین سفارشات در ابتدا استفاده شده است.
            $orders = $user->orders()->with('items.product')->orderBy('created_at', 'desc')->get(); 

            return view('profile.orders', compact('orders'));
        }

        // اگر route name نامشخص بود، به صفحه اصلی هدایت کنید یا خطای 404 بدهید.
        return redirect()->route('home')->with('error', 'مسیر نامعتبر.');
    }


    /**
     * Process placing an order from the cart.
     * عملیات ثبت سفارش را از سبد خرید پردازش می‌کند.
     *
     * @param  \App\Http\Requests\PlaceOrderRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder(PlaceOrderRequest $request): \Illuminate\Http\JsonResponse
    {
        // === خط دیباگ: برای لاگ کردن درخواست در ابتدای متد ===
        Log::info('Entering PlaceOrder method. Request all: ' . json_encode($request->all()));

        try {
            // اعتبارسنجی توسط PlaceOrderRequest انجام شده است.
            // داده‌های اعتبارسنجی شده را از Form Request دریافت می‌کنیم.
            $validatedData = $request->validated();

            // فراخوانی سرویس برای ثبت سفارش
            $order = $this->orderService->placeOrder($validatedData);

            // بازگرداندن پاسخ موفقیت‌آمیز
            return response()->json([
                'message' => 'سفارش شما با موفقیت ثبت شد!',
                'orderId' => $order->id
            ], 200);

        } catch (\Exception $e) {
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
