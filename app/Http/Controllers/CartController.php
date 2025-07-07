<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem; // Still needed for Route Model Binding

// Contracts
use App\Services\Contracts\CartServiceInterface;
use App\Contracts\Services\CouponService; // New: Import CouponService

// Form Requests (you should ensure these exist and are correctly defined)
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Requests\Cart\ApplyCouponRequest; // New: For applying coupons
// use App\Http\Requests\Cart\UpdateMultipleCartItemsRequest; // If you use updateMultipleCartItems method

// Custom Exceptions
use App\Exceptions\BaseCartException; // For centralized exception handling
use App\Exceptions\Cart\CartLimitExceededException; // New: For cart limit exceptions
use App\Exceptions\Cart\InsufficientStockException; // New: For insufficient stock exceptions
use App\Exceptions\ProductNotFoundException; // Already there, but good to confirm

class CartController extends Controller
{
    protected CartServiceInterface $cartService;
    protected CouponService $couponService; // New: Declare CouponService

    /**
     * Constructor for CartController.
     * سازنده کنترلر CartController.
     *
     * @param CartServiceInterface $cartService
     * @param CouponService $couponService // New: Inject CouponService
     */
    public function __construct(CartServiceInterface $cartService, CouponService $couponService)
    {
        $this->cartService = $cartService;
        $this->couponService = $couponService; // New: Assign CouponService
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
            $user = Auth::user();
            $sessionId = Session::getId();

            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
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
            // If this method is also used for API, return JSON. Otherwise, return to view or redirect.
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
            $cartContents = $this->cartService->getCartContents($cart); // This method returns CartContentsResponse

            // CartContentsResponse has a toArray() method.
            // This method includes 'items', 'totalQuantity', 'totalPrice' which cart.js expects.
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
        $variantId = $request->input('product_variant_id'); // New: Get product variant ID

        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            // Pass variantId to the service method if your service supports it
            $response = $this->cartService->addOrUpdateCartItem($cart, $productId, $quantity, $variantId);

            // CartOperationResponse has isSuccess(), getMessage(), getData(), getCode() methods.
            // There is no jsonSerialize() method in CartOperationResponse, we use getCode() and getMessage()/getData().
            if ($response->isSuccess()) {
                // After success, retrieve the total number of items in the cart again
                // This is necessary to update the mini-cart in the frontend.
                $updatedCart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
                $updatedCartContents = $this->cartService->getCartContents($updatedCart);

                return response()->json([
                    'message' => $response->getMessage(),
                    'data' => $response->getData(),
                    'totalQuantity' => $updatedCartContents->totalQuantity // Send total number of items
                ], $response->getCode() ?: 200); // Use getCode() for HTTP status
            } else {
                return response()->json(['message' => $response->getMessage()], $response->getCode() ?: 400);
            }
        } catch (InsufficientStockException $e) { // Catch specific exceptions
            Log::error('Insufficient stock error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], 400); // Custom status for stock
        } catch (CartLimitExceededException $e) { // Catch specific exceptions
            Log::error('Cart limit exceeded error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], 400); // Custom status for limit
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

            // Use updateCartItemQuantity method in the service
            $response = $this->cartService->updateCartItemQuantity($cartItem, $newQuantity, $user, $sessionId);

            if ($response->isSuccess()) {
                return response()->json(['message' => $response->getMessage(), 'data' => $response->getData()], $response->getCode() ?: 200);
            } else {
                return response()->json(['message' => $response->getMessage()], $response->getCode() ?: 400);
            }
        } catch (InsufficientStockException $e) { // Catch specific exceptions
            Log::error('Insufficient stock error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (CartLimitExceededException $e) { // Catch specific exceptions
            Log::error('Cart limit exceeded error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], 400);
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

            // Use removeCartItem method in the service
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

    /**
     * Apply a coupon to the cart (API endpoint).
     * یک کد تخفیف را به سبد خرید اعمال می‌کند.
     *
     * @param ApplyCouponRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function applyCoupon(ApplyCouponRequest $request): \Illuminate\Http\JsonResponse
    {
        $couponCode = $request->input('coupon_code');

        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            $success = $this->couponService->applyCoupon($cart, $couponCode);

            if ($success) {
                // Recalculate cart totals after applying coupon
                $cartTotals = $this->cartService->calculateCartTotals($cart->fresh()); // Use fresh() to get updated cart
                return response()->json([
                    'message' => 'کد تخفیف با موفقیت اعمال شد.',
                    'cartTotals' => $cartTotals,
                ], 200);
            } else {
                return response()->json(['message' => 'کد تخفیف نامعتبر یا منقضی شده است.'], 400);
            }
        } catch (\Throwable $e) {
            Log::error('Error applying coupon: ' . $e->getMessage(), ['coupon_code' => $couponCode, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در اعمال کد تخفیف.'], 500);
        }
    }

    /**
     * Remove a coupon from the cart (API endpoint).
     * یک کد تخفیف را از سبد خرید حذف می‌کند.
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

            $success = $this->couponService->removeCoupon($cart);

            if ($success) {
                // Recalculate cart totals after removing coupon
                $cartTotals = $this->cartService->calculateCartTotals($cart->fresh()); // Use fresh() to get updated cart
                return response()->json([
                    'message' => 'کد تخفیف با موفقیت حذف شد.',
                    'cartTotals' => $cartTotals,
                ], 200);
            } else {
                return response()->json(['message' => 'کد تخفیفی برای حذف وجود ندارد.'], 400);
            }
        } catch (\Throwable $e) {
            Log::error('Error removing coupon: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در حذف کد تخفیف.'], 500);
        }
    }
}
