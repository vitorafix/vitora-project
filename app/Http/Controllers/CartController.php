<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem; // همچنان برای Route Model Binding نیاز است

// Contracts
use App\Services\Contracts\CartServiceInterface;

// Form Requests (شما باید مطمئن شوید اینها وجود دارند و به درستی تعریف شده‌اند)
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
// use App\Http\Requests\Cart\UpdateMultipleCartItemsRequest; // اگر متد updateMultipleCartItems را استفاده می‌کنید

// Custom Exceptions
use App\Exceptions\BaseCartException; // برای مدیریت متمرکز Exceptionها

class CartController extends Controller
{
    protected CartServiceInterface $cartService; // تغییر نوع به اینترفیس

    /**
     * Constructor for CartController.
     * سازنده کنترلر CartController.
     *
     * @param CartServiceInterface $cartService
     */
    public function __construct(CartServiceInterface $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display the cart page.
     * صفحه سبد خرید را نمایش می‌دهد.
     * این متد برای رندر کردن ویو Blade استفاده می‌شود.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $cartContents = $this->cartService->getCartContents($cart);
            $cartTotals = $this->cartService->calculateCartTotals($cart);
            $cartValidationIssues = $this->cartService->validateCartItems($cart);

            return view('cart', [
                'cartItems' => $cartContents->items,
                'cart' => $cart,
                'totalQuantity' => $cartContents->totalQuantity,
                'totalPrice' => $cartContents->totalPrice,
                'cartTotals' => $cartTotals,
                'validationIssues' => $cartValidationIssues,
            ]);
        } catch (BaseCartException $e) {
            Log::error('Error displaying cart page: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            // اگر این متد برای API هم استفاده می‌شود، JSON برگردانید. در غیر این صورت، به view یا redirect برگردانید.
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error displaying cart page: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در بارگذاری سبد خرید.'], 500);
        }
    }

    /**
     * Get cart contents for display in frontend (API endpoint).
     * محتویات سبد خرید را برای نمایش در فرانت‌اند (API) دریافت می‌کند.
     * این متد توسط درخواست‌های AJAX از cart.js فراخوانی می‌شود.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contents(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $cartContents = $this->cartService->getCartContents($cart); // این متد CartContentsResponse را برمی‌گرداند

            // CartContentsResponse دارای متد toArray() است.
            // این متد شامل 'items', 'totalQuantity', 'totalPrice' است که cart.js انتظار دارد.
            return response()->json($cartContents->toArray());
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in contents method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in contents method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در دریافت محتویات سبد خرید.'], 500);
        }
    }

    /**
     * Add product to cart or update quantity (API endpoint).
     * محصول را به سبد خرید اضافه یا تعداد آن را به‌روزرسانی می‌کند.
     * از AddToCartRequest برای اعتبارسنجی استفاده می‌کند.
     *
     * @param AddToCartRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(AddToCartRequest $request): \Illuminate\Http\JsonResponse
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');

        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $response = $this->cartService->addOrUpdateCartItem($cart, $productId, $quantity);

            // CartOperationResponse دارای متدهای isSuccess(), getMessage(), getData(), getCode() است.
            // متد jsonSerialize() در CartOperationResponse وجود ندارد، از getCode() و getMessage()/getData() استفاده می‌کنیم.
            if ($response->isSuccess()) {
                // پس از موفقیت، تعداد کل آیتم‌ها در سبد خرید را دوباره دریافت کنید
                // این برای به‌روزرسانی مینی‌کارت در فرانت‌اند ضروری است.
                $updatedCart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
                $updatedCartContents = $this->cartService->getCartContents($updatedCart);

                return response()->json([
                    'message' => $response->getMessage(),
                    'data' => $response->getData(),
                    'totalQuantity' => $updatedCartContents->totalQuantity // ارسال تعداد کل آیتم‌ها
                ], $response->getCode() ?: 200); // استفاده از getCode() برای وضعیت HTTP
            } else {
                return response()->json(['message' => $response->getMessage()], $response->getCode() ?: 400);
            }
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در افزودن محصول به سبد خرید.'], 500);
        }
    }

    /**
     * Update quantity of a specific cart item (API endpoint).
     * تعداد یک آیتم خاص در سبد خرید را به‌روزرسانی می‌کند.
     * از UpdateCartItemRequest برای اعتبارسنجی استفاده می‌کند.
     * از Route Model Binding برای CartItem استفاده می‌کند.
     *
     * @param UpdateCartItemRequest $request
     * @param CartItem $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(UpdateCartItemRequest $request, CartItem $cartItem): \Illuminate\Http\JsonResponse
    {
        $newQuantity = $request->input('quantity');

        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // استفاده از متد updateCartItemQuantity در سرویس
            $response = $this->cartService->updateCartItemQuantity($cartItem, $newQuantity, $user, $sessionId);

            if ($response->isSuccess()) {
                return response()->json(['message' => $response->getMessage(), 'data' => $response->getData()], $response->getCode() ?: 200);
            } else {
                return response()->json(['message' => $response->getMessage()], $response->getCode() ?: 400);
            }
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در به‌روزرسانی تعداد محصول.'], 500);
        }
    }

    /**
     * Remove a specific cart item (API endpoint).
     * یک آیتم خاص را از سبد خرید حذف می‌کند.
     * از Route Model Binding برای CartItem استفاده می‌کند.
     *
     * @param CartItem $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCartItem(CartItem $cartItem): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // استفاده از متد removeCartItem در سرویس
            $response = $this->cartService->removeCartItem($cartItem, $user, $sessionId);

            if ($response->isSuccess()) {
                return response()->json(['message' => $response->getMessage(), 'data' => $response->getData()], $response->getCode() ?: 200);
            } else {
                return response()->json(['message' => $response->getMessage()], $response->getCode() ?: 400);
            }
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in removeCartItem method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in removeCartItem method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در حذف محصول از سبد خرید.'], 500);
        }
    }

    /**
     * Clear all items from the cart (API endpoint).
     * همه آیتم‌ها را از سبد خرید پاک می‌کند.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $response = $this->cartService->clearCart($cart);

            if ($response->isSuccess()) {
                return response()->json(['message' => $response->getMessage()], $response->getCode() ?: 200);
            } else {
                return response()->json(['message' => $response->getMessage()], $response->getCode() ?: 400);
            }
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in clear method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in clear method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در خالی کردن سبد خرید.'], 500);
        }
    }

    // متد updateQuantity (دوم) و getContents (دوم) که تکراری بودند، حذف شدند.
    // منطق متد getContents به متد contents منتقل شد تا با routes/api.php هماهنگ باشد.
}
