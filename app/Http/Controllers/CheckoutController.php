<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Services\Contracts\CartServiceInterface; // فرض می‌کنیم از CartServiceInterface استفاده می‌کنید
use App\Contracts\Services\OrderService; // اضافه شده: برای استفاده از OrderService
use App\Http\Requests\Checkout\PlaceOrderRequest; // اضافه شده: برای اعتبارسنجی درخواست ثبت سفارش

class CheckoutController extends Controller
{
    protected CartServiceInterface $cartService;
    protected OrderService $orderService; // اضافه شده: تزریق OrderService

    /**
     * Constructor for CheckoutController.
     * سازنده کنترلر CheckoutController.
     *
     * @param CartServiceInterface $cartService
     * @param OrderService $orderService // اضافه شده: تزریق OrderService
     */
    public function __construct(CartServiceInterface $cartService, OrderService $orderService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService; // مقداردهی OrderService
    }

    /**
     * Display the checkout page.
     * صفحه تسویه حساب را نمایش می‌دهد.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // دریافت یا ایجاد سبد خرید برای کاربر/سشن فعلی
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            // دریافت محتویات و مجموع قیمت سبد خرید
            $cartContents = $this->cartService->getCartContents($cart);
            $cartTotals = $this->cartService->calculateCartTotals($cart);
            $validationIssues = $this->cartService->validateCartItems($cart);

            // اگر سبد خرید خالی است یا مشکلات اعتبارسنجی جدی دارد، ریدایرکت کنید
            if ($cartContents->totalQuantity === 0 || !empty($validationIssues)) {
                // می‌توانید به صفحه سبد خرید ریدایرکت کنید
                return redirect()->route('cart.index')->with('error', 'سبد خرید شما خالی است یا مشکلات اعتبارسنجی دارد.');
            }

            // دریافت آدرس‌های کاربر و آدرس پیش‌فرض
            $addresses = collect([]);
            $defaultAddress = null;

            if ($user) {
                // فرض می‌کنیم کاربر دارای رابطه 'addresses' است
                $addresses = $user->addresses;
                $defaultAddress = $addresses->where('is_default', true)->first();
                if (!$defaultAddress && $addresses->isNotEmpty()) {
                    $defaultAddress = $addresses->first(); // اگر پیش‌فرض نبود، اولین آدرس را انتخاب کنید
                }
            }

            // ارسال داده‌ها به ویو
            return view('checkout', [
                'cartItems' => $cartContents->items,
                'cart' => $cart,
                'totalQuantity' => $cartContents->totalQuantity,
                'cartTotalPrice' => $cartTotals->totalPrice, // اضافه شده: برای استفاده در Blade
                'cartTotals' => $cartTotals,
                'validationIssues' => $validationIssues,
                'addresses' => $addresses, // اضافه شده: لیست آدرس‌ها
                'defaultAddress' => $defaultAddress, // اضافه شده: آدرس پیش‌فرض
            ]);

        } catch (\Throwable $e) {
            Log::error('Error displaying checkout page: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'session_id' => Session::getId(),
                'exception' => $e->getTraceAsString()
            ]);
            // در صورت بروز خطا، به صفحه اصلی یا صفحه خطا ریدایرکت کنید
            return redirect()->route('home')->with('error', 'خطا در بارگذاری صفحه تسویه حساب. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * Handle placing an order.
     * مدیریت ثبت سفارش.
     * این متد درخواست AJAX از checkout.js را مدیریت می‌کند.
     *
     * @param PlaceOrderRequest $request // استفاده از Form Request برای اعتبارسنجی
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder(PlaceOrderRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // دریافت سبد خرید فعلی
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            // اگر سبد خرید خالی است، اجازه ثبت سفارش ندهید
            if ($this->cartService->getCartContents($cart)->totalQuantity === 0) {
                return response()->json(['message' => 'سبد خرید شما خالی است و نمی‌توانید سفارش ثبت کنید.'], 400);
            }

            // ثبت سفارش از طریق OrderService
            $order = $this->orderService->createOrder($request->validated(), $cart, $user);

            // پاکسازی سبد خرید پس از ثبت موفق سفارش
            $this->cartService->clearCart($cart);

            Log::info('Order placed successfully.', ['order_id' => $order->id, 'user_id' => $user ? $user->id : null]);

            return response()->json([
                'message' => 'سفارش شما با موفقیت ثبت شد.',
                'orderId' => $order->id, // این orderId توسط checkout.js استفاده می‌شود
                'redirectUrl' => route('orders.show', $order->id) // مثال: مسیر صفحه تایید سفارش
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error placing order: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'session_id' => Session::getId(),
                'request_data' => $request->all(),
                'exception' => $e->getTraceAsString()
            ]);

            // برگرداندن پاسخ خطا
            return response()->json(['message' => 'خطا در ثبت سفارش. لطفاً بعداً تلاش کنید.'], 500);
        }
    }
}
