<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Address; // Import Address model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // For logging errors
use Illuminate\View\View; // Import View for proper method return type
use Illuminate\Http\RedirectResponse; // Import RedirectResponse
use Illuminate\Http\JsonResponse; // Import JsonResponse for API methods

use App\Http\Requests\PlaceOrderRequest; // Import new Form Request
use App\Services\OrderService; // Import new OrderService
use App\Services\Contracts\CartServiceInterface; // Added: For CartService injection

// New: Import custom exceptions for more specific error handling
use App\Exceptions\Cart\InsufficientStockException;
use App\Exceptions\BaseCartException; // Ensure this is also caught for general cart errors
use App\Exceptions\ProductNotFoundException; // If OrderService might throw this

// New: Import API Resources
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected CartServiceInterface $cartService; // Added: For CartService access

    /**
     * Constructor for OrderController.
     * سازنده کنترلر OrderController.
     *
     * @param OrderService $orderService
     * @param CartServiceInterface $cartService
     */
    public function __construct(OrderService $orderService, CartServiceInterface $cartService)
    {
        $this->orderService = $orderService;
        $this->cartService = $cartService;
    }

    /**
     * Helper method to get the current user's cart or create one if it doesn't exist.
     * این متد هر دو نوع کاربر احراز هویت شده و مهمان را مدیریت می‌کند.
     *
     * @return \App\Models\Cart
     */
    private function getOrCreateCart(): Cart
    {
        // استفاده از CartService برای دریافت یا ایجاد سبد خرید
        // این متد در CartService به درستی user_id یا session_id را مدیریت می‌کند.
        return $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
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
            
            // استفاده از getCartContents در CartService برای دریافت داده‌های کامل سبد خرید
            $cartContents = $this->cartService->getCartContents($cart);

            if ($cartContents->items->isEmpty()) { // بررسی isEmpty روی کالکشن itemsData
                return redirect()->route('cart.index')->with('error', 'سبد خرید شما خالی است و نمی‌توانید سفارش ثبت کنید.');
            }

            // --- واکشی آدرس‌های کاربر و آدرس پیش‌فرض ---
            $addresses = collect(); // پیش‌فرض یک کالکشن خالی
            $defaultAddress = null;

            if (Auth::check()) {
                $userAddresses = Auth::user()->addresses()->orderBy('is_default', 'desc')->get();
                $addresses = $userAddresses;
                // پیدا کردن آدرس پیش‌فرض یا اولین آدرس اگر پیش‌فرضی تنظیم نشده باشد
                $defaultAddress = $userAddresses->where('is_default', true)->first() ?? $userAddresses->first();
            }
            // --- پایان واکشی آدرس‌ها ---

            // ارسال داده‌های سبد خرید از CartContentsResponse به ویو
            return view('checkout', [
                'cartItems' => $cartContents->items,
                'cartTotalQuantity' => $cartContents->totalQuantity,
                'cartTotalPrice' => $cartContents->totalPrice,
                'addresses' => $addresses,
                'defaultAddress' => $defaultAddress,
            ]);
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
            // OrderService مسئول مدیریت موجودی و کوپن اعمال شده به سبد خرید خواهد بود.
            $order = $this->orderService->placeOrder($validatedData);

            // بازگرداندن پاسخ موفقیت‌آمیز
            return response()->json([
                'message' => 'سفارش شما با موفقیت ثبت شد!',
                'orderId' => $order->id
            ], 200);

        } catch (InsufficientStockException $e) {
            // مدیریت خطای کمبود موجودی
            Log::error('Order Placement Error - Insufficient Stock: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], 400); // 400 Bad Request
        } catch (ProductNotFoundException $e) {
            // مدیریت خطای محصول یافت نشد
            Log::error('Order Placement Error - Product Not Found: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'محصولی در سبد خرید یافت نشد یا نامعتبر است.'], 404); // 404 Not Found
        } catch (BaseCartException $e) {
            // مدیریت خطاهای عمومی سبد خرید که ممکن است توسط OrderService پرتاب شوند
            Log::error('Order Placement Error - Cart Exception: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            // ثبت خطا در لاگ برای اشکال‌زدایی عمومی
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

    /**
     * Get order details for API display.
     * جزئیات یک سفارش خاص را برای نمایش در API دریافت می‌کند.
     * از OrderResource برای فرمت‌بندی پاسخ استفاده می‌کند.
     *
     * @param Order $order
     * @return \App\Http\Resources\OrderResource|\Illuminate\Http\JsonResponse
     */
    public function showApi(Order $order): OrderResource|JsonResponse
    {
        // اطمینان از اینکه کاربر فقط می‌تواند سفارشات خودش را مشاهده کند
        // این منطق باید مشابه showConfirmation باشد
        if (Auth::check() && $order->user_id !== Auth::id()) {
            return response()->json(['message' => 'شما اجازه دسترسی به این سفارش را ندارید.'], 403);
        } elseif (!Auth::check() && $order->session_id !== Session::getId()) {
            return response()->json(['message' => 'شما اجازه دسترسی به این سفارش را ندارید.'], 403);
        }

        // eager loading آیتم‌های سفارش و محصولات مرتبط
        $order->load('items.product');

        // استفاده از OrderResource برای تبدیل مدل Order به فرمت JSON مناسب
        return new OrderResource($order);
    }
}
