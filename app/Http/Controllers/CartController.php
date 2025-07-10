<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem; // مدل CartItem برای آیتم‌های سبد خرید
use App\Models\Product; // مدل Product برای محصولات

// Contracts (اینترفیس‌های سرویس‌ها)
use App\Services\Contracts\CartServiceInterface; // اینترفیس سرویس مدیریت سبد خرید
use App\Contracts\Services\CouponService; // اینترفیس سرویس مدیریت کوپن تخفیف (اینجا نگه داشته می‌شود اما کمتر مستقیم استفاده می‌شود)
use App\Services\CartCalculationService; // اضافه شده: برای استفاده مستقیم از سرویس محاسبات سبد خرید

// Form Requests (کلاس‌های اعتبارسنجی درخواست‌ها)
use App\Http\Requests\Cart\AddToCartRequest; // برای اعتبارسنجی درخواست افزودن به سبد
use App\Http\Requests\Cart\UpdateCartItemRequest; // برای اعتبارسنجی درخواست به‌روزرسانی آیتم
use App\Http\Requests\Cart\ApplyCouponRequest; // برای اعتبارسنجی درخواست اعمال کوپن

// Custom Exceptions (استثنائات سفارشی پروژه)
use App\Exceptions\BaseCartException; // کلاس پایه برای استثنائات سبد خرید
use App\Exceptions\Cart\CartLimitExceededException; // استثنا برای تجاوز از محدودیت سبد
use App\Exceptions\Cart\InsufficientStockException; // استثنا برای موجودی ناکافی
use App\Exceptions\ProductNotFoundException; // استثنا برای یافت نشدن محصول
use App\Exceptions\CartOperationException; // استثنا برای خطاهای عمومی عملیات سبد خرید
use App\Exceptions\UnauthorizedCartAccessException; // استثنا برای دسترسی غیرمجاز به سبد خرید
use App\Exceptions\EmptyCartException; // اضافه شده: برای مدیریت استثنا سبد خرید خالی
use App\Http\Resources\CartResource; // Assuming CartResource exists and is needed

class CartController extends Controller
{
    protected CartServiceInterface $cartService;
    protected CartCalculationService $cartCalculationService; // اضافه شده: برای تزریق سرویس محاسبات

    public function __construct(CartServiceInterface $cartService, CartCalculationService $cartCalculationService)
    {
        $this->cartService = $cartService;
        $this->cartCalculationService = $cartCalculationService; // تزریق سرویس محاسبات
    }

    /**
     * نمایش محتویات سبد خرید در صفحه اصلی سبد خرید.
     * این متد داده‌های سبد خرید را از سرویس دریافت کرده و به View ارسال می‌کند.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $cartContents = $this->cartService->getCartContents($cart); // این متد باید شامل آیتم‌ها باشد

            // cartContents اکنون همیشه یک CartContentsResponse معتبر است، حتی اگر سبد خالی باشد.
            // بنابراین، نیازی به بررسی empty() یا try-catch برای EmptyCartException در اینجا نیست.
            // CartContentsResponse باید شامل cartTotals باشد که در صورت خالی بودن، مقادیر صفر دارد.

            return view('cart', [ // اصلاح شده: 'cart.index' به 'cart' تغییر یافت
                'cartItems' => $cartContents->items, // فرض بر این است که $cartContents->items آیتم‌های سبد خرید را برمی‌گرداند
                'cartTotals' => $cartContents->cartTotals, // از cartTotals موجود در پاسخ سرویس استفاده کنید
                'isEmpty' => empty($cartContents->items), // اصلاح شده: از empty() برای آرایه استفاده کنید
                'totalQuantity' => $cartContents->totalQuantity,
                'totalPrice' => $cartContents->totalPrice,
            ]);

        } catch (\Exception $e) {
            // برای هر خطای غیرمنتظره دیگر
            Log::error('Error loading cart page: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'خطایی در بارگذاری سبد خرید رخ داد. لطفاً دوباره تلاش کنید.');
        }
    }

    /**
     * دریافت محتویات فعلی سبد خرید به صورت JSON.
     * این متد توسط جاوااسکریپت فرانت‌اند برای نمایش آیتم‌های سبد خرید فراخوانی می‌شود.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContents(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // دریافت سبد خرید
            $user = Auth::user();
            $sessionId = Session::getId();
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            // CartService::getCartContents اکنون همیشه یک CartContentsResponse معتبر را برمی‌گرداند
            $cartContentsResponse = $this->cartService->getCartContents($cart);
            
            // استفاده از CartResource برای تبدیل CartContentsResponse به یک ساختار JSON بهینه‌سازی شده برای API
            // CartResource شامل منطق فرمت‌بندی، اضافه کردن ابرداده و بهینه‌سازی برای موبایل است.
            // متد with() در CartResource برای افزودن فیلدهای 'success' و 'message' استفاده می‌شود.
            return (new CartResource($cartContentsResponse))->response()->setStatusCode(200);

        } catch (\Exception $e) {
            Log::error('Error fetching cart contents', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در بارگذاری سبد خرید',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * اضافه کردن محصول به سبد خرید یا به‌روزرسانی تعداد آن.
     *
     * @param  \App\Models\Product  $product  // لاراول به صورت خودکار مدل Product را بر اساس پارامتر مسیر تزریق می‌کند.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Product $product, Request $request) // تغییر اعمال شده: ترتیب پارامترها و نوع Product
    {
        try {
            // اعتبارسنجی درخواست (مثلاً quantity)
            // 'quantity' می‌تواند nullable باشد تا اگر ارسال نشد، به صورت پیش‌فرض 1 در نظر گرفته شود.
            $request->validate([
                'quantity' => 'nullable|integer|min:1',
            ]);

            $quantity = $request->input('quantity', 1);

            // dd($product, $quantity);  // <-- دستور dd() برای دیباگ اضافه شد

            // دریافت یا ایجاد سبد خرید
            $currentCart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());

            // فراخوانی سرویس CartService برای افزودن یا به‌روزرسانی آیتم
            // این متد باید منطق اصلی افزودن/به‌روزرسانی (بررسی موجودی، افزودن به جدول cart_items) را انجام دهد.
            $response = $this->cartService->addOrUpdateCartItem(
                $currentCart, // استفاده از نمونه مدل Cart
                $product->id, // استفاده از ID محصول تزریق شده
                $quantity
            );

            if ($response->isSuccess()) {
                // پس از عملیات موفق، محتویات به‌روز شده سبد خرید را دریافت کنید
                $updatedCart = $currentCart->fresh(); // دریافت آخرین وضعیت سبد خرید از دیتابیس
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart); // دریافت CartContentsResponse از سرویس

                // استفاده از CartResource برای فرمت‌بندی پاسخ
                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'محصول با موفقیت به سبد خرید اضافه شد.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // خطاهای اعتبارسنجی (مثلاً quantity نامعتبر)
            Log::error('Validation error adding item to cart: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی ورودی. لطفاً تعداد معتبری وارد کنید.',
                'errors' => $e->errors(),
            ], 422); // 422 Unprocessable Entity
        } catch (ProductNotFoundException $e) { // اضافه شدن ProductNotFoundException
            Log::error('Product not found for cart add operation: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'محصول مورد نظر یافت نشد.',
            ], 404); // 404 Not Found
        } catch (CartOperationException $e) {
            // خطاهای مربوط به عملیات سبد خرید (مثلاً موجودی ناکافی، محدودیت سبد)
            Log::error('Cart operation error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(), // پیام از خود استثنا گرفته می‌شود
            ], $e->getCode() ?: 500); // استفاده از کد استثنا یا 500 به عنوان پیش‌فرض
        } catch (\Throwable $e) {
            // هر خطای غیرمنتظره دیگری که در حین اجرای متد رخ دهد
            Log::error('Unexpected error adding item to cart: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'خطای سیستمی در اضافه کردن محصول به سبد خرید. لطفاً با پشتیبانی تماس بگیرید.',
            ], 500);
        }
    }

    /**
     * به‌روزرسانی تعداد یک آیتم در سبد خرید.
     *
     * @param Request $request
     * @param CartItem $cartItem // مدل CartItem به صورت خودکار از URL تزریق می‌شود.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request, CartItem $cartItem)
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // اطمینان از اینکه کاربر فعلی مالک آیتم سبد خرید است.
            // این بررسی امنیتی بسیار مهم است.
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId)) {
                throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این آیتم سبد خرید را ندارید.');
            }

            $request->validate([
                'quantity' => 'required|integer|min:0', // اجازه 0 برای حذف آیتم
            ]);

            $newQuantity = $request->input('quantity');

            // فراخوانی سرویس برای به‌روزرسانی تعداد آیتم
            $response = $this->cartService->updateCartItemQuantity($cartItem, $newQuantity, $user, $sessionId);

            if ($response->isSuccess()) {
                // دریافت سبد خرید مرتبط با آیتم به صورت صریح برای محاسبه مجموع کل‌ها
                // این کار از خطاهای احتمالی در دسترسی به رابطه 'cart' جلوگیری می‌کند.
                $cart = $this->cartService->getCartById($cartItem->cart_id, $user, $sessionId);
                if (!$cart) {
                    throw new CartOperationException('سبد خرید مرتبط با آیتم یافت نشد.', 404);
                }
                $cartContentsResponse = $this->cartService->getCartContents($cart->fresh()); // دریافت CartContentsResponse از سرویس

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'تعداد آیتم سبد خرید با موفقیت به‌روزرسانی شد.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating cart item quantity: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی ورودی. لطفاً تعداد معتبری وارد کنید.',
                'errors' => $e->errors(),
            ], 422);
        } catch (UnauthorizedCartAccessException $e) {
            Log::error('Unauthorized cart access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403); // 403 Forbidden
        } catch (CartOperationException $e) {
            Log::error('Cart operation error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Throwable $e) {
            Log::error('Error updating cart item quantity: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در به‌روزرسانی تعداد آیتم سبد خرید. لطفاً با پشتیبانی تماس بگیرید.'], 500);
        }
    }

    /**
     * حذف یک آیتم از سبد خرید.
     *
     * @param Request $request
     * @param CartItem $cartItem // مدل CartItem به صورت خودکار از URL تزریق می‌شود.
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCartItem(Request $request, CartItem $cartItem)
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // اطمینان از اینکه کاربر فعلی مالک آیتم سبد خرید است.
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId)) {
                throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این آیتم سبد خرید را ندارید.');
            }

            // فراخوانی سرویس برای حذف آیتم از سبد خرید
            $response = $this->cartService->removeCartItem($cartItem, $user, $sessionId);

            if ($response->isSuccess()) {
                // پس از عملیات موفق، محتویات به‌روز شده سبد خرید را دریافت کنید
                $updatedCart = $cartItem->cart->fresh(); // دریافت آخرین وضعیت سبد خرید از دیتابیس
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart); // دریافت CartContentsResponse از سرویس

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'آیتم با موفقیت از سبد خرید حذف شد.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (UnauthorizedCartAccessException $e) {
            Log::error('Unauthorized cart access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (CartOperationException $e) {
            Log::error('Cart operation error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Throwable $e) {
            Log::error('Error removing item from cart: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در حذف آیتم از سبد خرید. لطفاً با پشتیبانی تماس بگیرید.'], 500);
        }
    }

    /**
     * پاک کردن تمامی آیتم‌ها از سبد خرید.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart(Request $request)
    {
        $user = Auth::user();
        $sessionId = Session::getId();

        try {
            // فراخوانی سرویس برای پاک کردن سبد خرید
            $cart = $this->cartService->getOrCreateCart($user, $sessionId); // دریافت سبد خرید فعلی
            $response = $this->cartService->clearCart($cart); // فراخوانی متد clearCart با نمونه Cart

            if ($response->isSuccess()) {
                // پس از عملیات موفق، محتویات به‌روز شده سبد خرید را دریافت کنید (که اکنون خالی است)
                $updatedCart = $cart->fresh(); // دریافت آخرین وضعیت سبد خرید از دیتابیس
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart); // دریافت CartContentsResponse از سرویس

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'سبد خرید با موفقیت پاک شد.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Throwable $e) {
            Log::error('Error clearing cart: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطای سیستمی در پاک کردن سبد خرید. لطفاً با پشتیبانی تماس بگیرید.'], 500);
        }
    }

    /**
     * اعمال کوپن تخفیف به سبد خرید.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyCoupon(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'coupon_code' => 'required|string|max:255',
            ]);

            $user = Auth::user();
            $sessionId = Session::getId();
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            $couponCode = $request->input('coupon_code');
            // تغییر: فراخوانی سرویس CartService برای اعمال کوپن
            $response = $this->cartService->applyCoupon($cart, $couponCode);

            if ($response->isSuccess()) {
                // پس از عملیات موفق، محتویات به‌روز شده سبد خرید را دریافت کنید
                $updatedCart = $cart->fresh(); // دریافت آخرین وضعیت سبد خرید از دیتابیس
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart); // دریافت CartContentsResponse از سرویس

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'کد تخفیف با موفقیت اعمال شد.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error applying coupon: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی کد تخفیف. لطفاً کد معتبری وارد کنید.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error applying coupon: ' . $e->getMessage(), ['coupon_code' => $couponCode, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در اعمال کد تخفیف. لطفاً با پشتیبانی تماس بگیگیرد.'], 500);
        }
    }

    /**
     * حذف کوپن تخفیف از سبد خرید.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCoupon(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            // تغییر: فراخوانی سرویس CartService برای حذف کوپن
            $response = $this->cartService->removeCoupon($cart);

            if ($response->isSuccess()) {
                // پس از عملیات موفق، محتویات به‌روز شده سبد خرید را دریافت کنید
                $updatedCart = $cart->fresh(); // دریافت آخرین وضعیت سبد خرید از دیتابیس
                $cartContentsResponse = $this->cartService->getCartContents($updatedCart); // دریافت CartContentsResponse از سرویس

                return (new CartResource($cartContentsResponse))
                    ->additional([
                        'success' => true,
                        'message' => 'کد تخفیف با موفقیت حذف شد.',
                    ])
                    ->response()
                    ->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Throwable $e) {
            Log::error('Error removing coupon: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در حذف کد تخفیف. لطفاً با پشتیبانی تماس بگیرید.'], 500);
        }
    }
}