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
use App\Contracts\Services\CouponService; // اینترفیس سرویس مدیریت کوپن تخفیف

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

class CartController extends Controller
{
    protected CartServiceInterface $cartService;
    protected CouponService       $couponService;

    /**
     * سازنده کلاس CartController.
     * سرویس‌های CartService و CouponService از طریق Dependency Injection تزریق می‌شوند.
     *
     * @param CartServiceInterface $cartService
     * @param CouponService $couponService
     */
    public function __construct(CartServiceInterface $cartService, CouponService $couponService)
    {
        $this->cartService = $cartService;
        $this->couponService = $couponService;
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
        // دریافت کاربر احراز هویت شده یا ID سشن برای سبد مهمان
        $user = Auth::user();
        $sessionId = Session::getId();

        // دریافت یا ایجاد سبد خرید از طریق سرویس
        $cart = $this->cartService->getOrCreateCart($user, $sessionId);
        // دریافت محتویات سبد خرید (آیتم‌ها)
        $cartContents = $this->cartService->getCartContents($cart);

        // محاسبه مجموع کل‌های سبد خرید (زیرمجموع، حمل و نقل، مالیات، تخفیف، کل نهایی)
        $cartTotals = $this->cartService->calculateCartTotals($cart);

        // ارسال داده‌ها به View 'cart' (فرض بر این است که شما یک فایل blade به نام 'cart.blade.php' دارید)
        return view('cart', [
            'cartContents' => $cartContents->getItems(),
            'totalQuantity' => $cartContents->getTotalQuantity(),
            'totalPrice' => $cartContents->getTotalPrice(),
            'cartTotals' => $cartTotals, // ارسال مجموع کل‌ها به View
        ]);
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
            $user = Auth::user();
            $sessionId = Session::getId();
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $cartContents = $this->cartService->getCartContents($cart);
            $cartTotals = $this->cartService->calculateCartTotals($cart);

            return response()->json([
                'success' => true,
                'items' => $cartContents->getItems(),
                'totalQuantity' => $cartContents->getTotalQuantity(),
                'totalPrice' => $cartContents->getTotalPrice(),
                'cartTotals' => $cartTotals,
                'message' => 'محتویات سبد خرید با موفقیت دریافت شد.'
            ], 200);
        } catch (\Throwable $e) {
            // لاگ کردن خطای سیستمی برای اشکال‌زدایی عمیق‌تر
            Log::error('Error fetching cart contents: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت محتویات سبد خرید. لطفاً با پشتیبانی تماس بگیرید.',
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

            // دریافت quantity از بدنه درخواست، با مقدار پیش‌فرض 1
            $quantity = $request->input('quantity', 1);

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
                // محاسبه مجموع کل‌های سبد خرید پس از عملیات موفق
                // به جای $response->getCart() از $currentCart->fresh() استفاده می‌شود
                $cartTotals = $this->cartService->calculateCartTotals($currentCart->fresh());
                return response()->json([
                    'success' => true,
                    'message' => 'محصول با موفقیت به سبد خرید اضافه شد.',
                    'cartTotals' => $cartTotals,
                    // بازگرداندن داده‌های به‌روز شده سبد خرید با استفاده از $currentCart->fresh()
                    'cart' => $currentCart->fresh()->toArray(),
                ], 200);
            } else {
                // اگر سرویس عملیات را موفقیت‌آمیز گزارش نکرد
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
                // `cartItem->cart->fresh()` فرض می‌کند که مدل `CartItem` یک رابطه `belongsTo` به مدل `Cart` دارد.
                // و مدل `Cart` نیز یک رابطه `hasMany` به `CartItem` دارد.
                // اگر این روابط به درستی تعریف نشده باشند، این خط ممکن است خطا دهد.
                $cartTotals = $this->cartService->calculateCartTotals($cartItem->cart->fresh());
                return response()->json([
                    'success' => true,
                    'message' => 'تعداد آیتم سبد خرید با موفقیت به‌روزرسانی شد.',
                    'cartTotals' => $cartTotals,
                    'cartItem' => $cartItem->fresh()->toArray(), // بازگرداندن داده‌های به‌روز شده آیتم سبد خرید
                ], 200);
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
                // محاسبه مجموع کل‌های سبد خرید پس از حذف موفق
                // `cartItem->cart->fresh()` نیاز به تعریف صحیح روابط مدل دارد.
                $cartTotals = $this->cartService->calculateCartTotals($cartItem->cart->fresh());
                return response()->json([
                    'success' => true,
                    'message' => 'آیتم با موفقیت از سبد خرید حذف شد.',
                    'cartTotals' => $cartTotals,
                ], 200);
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
            $response = $this->cartService->clearCart($user, $sessionId); // فرض بر وجود متد clearCart در سرویس

            if ($response->isSuccess()) {
                $cartTotals = $this->cartService->calculateCartTotals($response->getCart()); // دریافت سبد خالی
                return response()->json(['success' => true, 'message' => 'سبد خرید با موفقیت پاک شد.', 'cartTotals' => $cartTotals], 200);
            } else {
                return response()->json(['success' => false, 'message' => $response->getMessage()], $response->getStatusCode());
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
            // فراخوانی سرویس CouponService برای اعمال کوپن
            $response = $this->couponService->applyCoupon($cart, $couponCode);

            if ($response->isSuccess()) {
                // محاسبه مجدد مجموع کل‌های سبد خرید پس از اعمال کوپن
                $cartTotals = $this->cartService->calculateCartTotals($cart->fresh());
                return response()->json([
                    'success' => true,
                    'message' => 'کد تخفیف با موفقیت اعمال شد.',
                    'cartTotals' => $cartTotals,
                ], 200);
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

            // فراخوانی سرویس CouponService برای حذف کوپن
            $response = $this->couponService->removeCoupon($cart); // فرض بر این است که این متد یک Response object برمی‌گرداند

            if ($response->isSuccess()) {
                // محاسبه مجدد مجموع کل‌های سبد خرید پس از حذف کوپن
                $cartTotals = $this->cartService->calculateCartTotals($cart->fresh());
                return response()->json([
                    'success' => true,
                    'message' => 'کد تخفیف با موفقیت حذف شد.',
                    'cartTotals' => $cartTotals,
                ], 200);
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
