<?php

namespace App\Services\Responses;

use Illuminate\Http\Request;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // بهبود: اضافه شد برای کشینگ
use Illuminate\Http\JsonResponse;
use App\Services\Contracts\CartServiceInterface;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest; // بهبود: اضافه شد برای Form Request
use App\Http\Resources\CartResource;

// ایمپورت کردن Exception های سفارشی از ImprovedCartService
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\UnauthorizedCartAccessException;
use App\Exceptions\CartOperationException;
use App\Exceptions\CartInvalidArgumentException;
use App\Exceptions\CartLimitExceededException;


class CartController extends Controller
{
    protected CartServiceInterface $cartService;

    /**
     * Constructor for CartController.
     * سازنده کنترلر CartController.
     *
     * @param CartServiceInterface $cartService
     */
    public function __construct(CartServiceInterface $cartService)
    {
        $this->cartService = $cartService;

        // بهبود: افزودن Middleware به متدهای کنترلر
        // 'index' و 'getContents' برای کاربران مهمان نیز قابل دسترسی هستند.
        $this->middleware('auth')->except(['index', 'getContents']);
        // نکته: Rate Limiting در سطح کنترلر معمولاً در فایل web.php یا Kernel.php تعریف می‌شود.
        // مثال برای web.php: Route::post('/cart/add', [CartController::class, 'add'])->middleware('throttle:10,1');
    }

    /**
     * متد کمکی خصوصی برای دریافت یا ایجاد سبد خرید فعلی کاربر/مهمان.
     * بهبود: جلوگیری از تکرار منطق دریافت Cart.
     *
     * @return \App\Models\Cart
     */
    private function getCurrentCart(): \App\Models\Cart
    {
        return $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
    }

    /**
     * متد کمکی خصوصی برای مدیریت Exception ها و برگرداندن پاسخ JSON استاندارد.
     * بهبود: Extract کردن Exception Handling.
     *
     * @param \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleCartException(\Exception $e): JsonResponse
    {
        Log::error('Cart operation failed: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);

        return match(get_class($e)) {
            ProductNotFoundException::class => response()->json(['success' => false, 'message' => $e->getMessage()], 404),
            InsufficientStockException::class => response()->json(['success' => false, 'message' => $e->getMessage()], 400),
            UnauthorizedCartAccessException::class => response()->json(['success' => false, 'message' => $e->getMessage()], 403),
            CartInvalidArgumentException::class => response()->json(['success' => false, 'message' => $e->getMessage()], 422),
            CartLimitExceededException::class => response()->json(['success' => false, 'message' => $e->getMessage()], 400),
            CartOperationException::class => response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 500),
            default => response()->json(['success' => false, 'message' => 'خطای سیستم. لطفاً دوباره تلاش کنید.'], 500)
        };
    }

    /**
     * Display the cart page.
     * صفحه سبد خرید را نمایش می‌دهد.
     *
     * @return \Illuminate\View\View
     */
    public function index(): \Illuminate\View\View
    {
        // دریافت سبد خرید از سرویس با استفاده از متد کمکی
        $cartContents = $this->cartService->getCartContents($this->getCurrentCart());

        // CartContentsResponse یک DTO است که شامل items, totalQuantity, totalPrice می‌شود.
        // شما می‌توانید مستقیماً از آن در ویو استفاده کنید یا آن را به آرایه تبدیل کنید.
        $cartItems = $cartContents->items;
        $totalQuantity = $cartContents->totalQuantity;
        $totalPrice = $cartContents->totalPrice;

        return view('cart', compact('cartItems', 'totalQuantity', 'totalPrice'));
    }

    /**
     * Add a product to the cart.
     * محصولی را به سبد خرید اضافه می‌کند.
     *
     * @param  \App\Http\Requests\AddToCartRequest  $request // بهبود: استفاده از Form Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(AddToCartRequest $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');

        try {
            $result = $this->cartService->addOrUpdateCartItem(
                $this->getCurrentCart(),
                $productId,
                $quantity
            );

            return response()->json($result, $result['status_code']);

        } catch (\Exception $e) {
            return $this->handleCartException($e); // بهبود: استفاده از متد کمکی
        }
    }

    /**
     * Update the quantity of a cart item.
     * تعداد یک آیتم سبد خرید را به‌روزرسانی می‌کند.
     *
     * @param  \App\Http\Requests\UpdateCartItemRequest  $request // بهبود: استفاده از Form Request
     * @param  \App\Models\CartItem  $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $newQuantity = $request->input('quantity');

        try {
            // بررسی مالکیت آیتم سبد خرید قبل از به‌روزرسانی
            if (!$this->cartService->userOwnsCartItem($cartItem, Auth::user(), Session::getId())) {
                throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این آیتم سبد خرید را ندارید.');
            }

            $result = $this->cartService->updateCartItemQuantity(
                $cartItem,
                $newQuantity,
                Auth::user(),
                Session::getId()
            );

            return response()->json($result, $result['status_code']);

        } catch (\Exception $e) {
            return $this->handleCartException($e); // بهبود: استفاده از متد کمکی
        }
    }

    /**
     * Remove a product from the cart.
     * محصولی را از سبد خرید حذف می‌کند.
     *
     * @param  \App\Models\CartItem  $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(CartItem $cartItem): JsonResponse
    {
        try {
            // بررسی مالکیت آیتم سبد خرید قبل از حذف
            if (!$this->cartService->userOwnsCartItem($cartItem, Auth::user(), Session::getId())) {
                throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این آیتم سبد خرید را ندارید.');
            }

            $result = $this->cartService->removeCartItem(
                $cartItem,
                Auth::user(),
                Session::getId()
            );

            return response()->json($result, $result['status_code']);

        } catch (\Exception $e) {
            return $this->handleCartException($e); // بهبود: استفاده از متد کمکی
        }
    }

    /**
     * Clear the entire cart.
     * کل سبد خرید را خالی می‌کند.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear(): JsonResponse
    {
        try {
            $result = $this->cartService->clearCart(
                $this->getCurrentCart()
            );

            return response()->json($result, $result['status_code']);

        } catch (\Exception $e) {
            return $this->handleCartException($e); // بهبود: استفاده از متد کمکی
        }
    }

    /**
     * Get cart contents for mini-cart display.
     * محتویات سبد خرید را برای نمایش در مینی‌کارت دریافت می‌کند.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContents(): JsonResponse
    {
        $cacheKey = "cart_contents_" . (Auth::id() ?? Session::getId());

        // بهبود: Cache کردن نتایج
        $cartContents = Cache::remember($cacheKey, 300, function() {
            return $this->cartService->getCartContents($this->getCurrentCart());
        });

        // بهبود: استفاده از Resource Class برای فرمت‌دهی پاسخ
        return CartResource::make($cartContents);
    }

    /**
     * Update the quantity of a cart item via AJAX from checkout page.
     * تعداد یک آیتم سبد خرید را از طریق AJAX در صفحه تسویه حساب به‌روزرسانی می‌کند.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Models\CartItem $cartItem // بهبود: استفاده از Route Model Binding
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request, CartItem $cartItem): JsonResponse
    {
        // بهبود: استفاده از Request Validation به جای Form Request برای این متد
        // اگرچه می‌توانید یک Form Request جداگانه برای آن بسازید.
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $newQuantity = $request->input('quantity');

        try {
            // بهبود: بررسی مالکیت آیتم سبد خرید با استفاده از متد سرویس
            if (!$this->cartService->userOwnsCartItem($cartItem, Auth::user(), Session::getId())) {
                throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این آیتم سبد خرید را ندارید.');
            }

            $result = $this->cartService->updateCartItemQuantity(
                $cartItem,
                $newQuantity,
                Auth::user(),
                Session::getId()
            );

            return response()->json($result, $result['status_code']);

        } catch (\Exception $e) {
            return $this->handleCartException($e); // بهبود: استفاده از متد کمکی
        }
    }
}
