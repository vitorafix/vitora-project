<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem; // همچنان برای Route Model Binding نیاز است

// Contracts
use App\Contracts\Services\CartServiceInterface; // ایمپورت کردن اینترفیس CartService

// Form Requests (شما باید اینها را ایجاد کنید)
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Requests\Cart\UpdateMultipleCartItemsRequest; // اگر متد updateMultipleItems را استفاده می‌کنید

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
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // دریافت سبد خرید از سرویس
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());

            // دریافت محتویات کامل سبد خرید برای نمایش
            $cartContents = $this->cartService->getCartContents($cart);

            // محاسبه مجموع‌ها برای نمایش در Blade
            $cartTotals = $this->cartService->calculateCartTotals($cart);

            // اعتبارسنجی آیتم‌های سبد خرید (مثلاً موجودی)
            $cartValidationIssues = $this->cartService->validateCartItems($cart);

            return view('cart', [
                'cartItems' => $cartContents->items,
                'cart' => $cart, // ارسال آبجکت کامل سبد خرید
                'totalQuantity' => $cartContents->totalQuantity,
                'totalPrice' => $cartContents->totalPrice,
                'cartTotals' => $cartTotals, // ارسال مجموع‌های محاسبه شده
                'validationIssues' => $cartValidationIssues, // ارسال مشکلات اعتبارسنجی
            ]);
        } catch (BaseCartException $e) {
            Log::error('Error displaying cart page: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            // در صورت خطا، می‌توانید به صفحه خطا ریدایرکت کنید یا یک پیام نمایش دهید
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error displaying cart page: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در بارگذاری سبد خرید.'], 500);
        }
    }

    /**
     * Add a product to the cart.
     * محصولی را به سبد خرید اضافه می‌کند.
     *
     * @param  \App\Http\Requests\Cart\AddToCartRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(AddToCartRequest $request) // استفاده از Form Request
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');

        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $response = $this->cartService->addOrUpdateCartItem($cart, $productId, $quantity);

            return response()->json($response->jsonSerialize(), $response->statusCode); // استفاده از CartOperationResponse

        } catch (BaseCartException $e) {
            Log::error('Cart operation error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در افزودن محصول به سبد خرید.'], 500);
        }
    }

    /**
     * Update the quantity of a cart item.
     * تعداد یک آیتم سبد خرید را به‌روزرسانی می‌کند.
     *
     * @param  \App\Http\Requests\Cart\UpdateCartItemRequest  $request
     * @param  \App\Models\CartItem  $cartItem (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCartItemRequest $request, CartItem $cartItem) // استفاده از Form Request
    {
        $newQuantity = $request->input('quantity');

        try {
            // سرویس خودش مالکیت را بررسی می‌کند
            $response = $this->cartService->updateCartItemQuantity(
                $cartItem,
                $newQuantity,
                Auth::user(),
                Session::getId()
            );

            return response()->json($response->jsonSerialize(), $response->statusCode); // استفاده از CartOperationResponse

        } catch (BaseCartException $e) {
            Log::error('Cart operation error in update method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in update method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در به‌روزرسانی تعداد محصول.'], 500);
        }
    }

    /**
     * Remove a product from the cart.
     * محصولی را از سبد خرید حذف می‌کند.
     *
     * @param  \App\Models\CartItem  $cartItem (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(CartItem $cartItem)
    {
        try {
            // سرویس خودش مالکیت را بررسی می‌کند
            $response = $this->cartService->removeCartItem(
                $cartItem,
                Auth::user(),
                Session::getId()
            );

            return response()->json($response->jsonSerialize(), $response->statusCode); // استفاده از CartOperationResponse

        } catch (BaseCartException $e) {
            Log::error('Cart operation error in remove method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in remove method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در حذف محصول از سبد خرید.'], 500);
        }
    }

    /**
     * Clear the entire cart.
     * کل سبد خرید را خالی می‌کند.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear()
    {
        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $response = $this->cartService->clearCart($cart);

            return response()->json($response->jsonSerialize(), $response->statusCode); // استفاده از CartOperationResponse

        } catch (BaseCartException $e) {
            Log::error('Cart operation error in clear method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in clear method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در خالی کردن سبد خرید.'], 500);
        }
    }

    /**
     * Get cart contents for mini-cart display.
     * محتویات سبد خرید را برای نمایش در مینی‌کارت دریافت می‌کند.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContents()
    {
        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $cartContents = $this->cartService->getCartContents($cart);

            return response()->json($cartContents->jsonSerialize()); // CartContentsResponse نیز JsonSerializable است

        } catch (BaseCartException $e) {
            Log::error('Cart operation error in getContents method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in getContents method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در دریافت محتویات سبد خرید.'], 500);
        }
    }

    /**
     * Update the quantity of a cart item via AJAX from checkout page.
     * این متد برای به‌روزرسانی تعداد آیتم از صفحه تسویه حساب استفاده می‌شود.
     *
     * @param UpdateCartItemRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(UpdateCartItemRequest $request): \Illuminate\Http\JsonResponse
    {
        // 'item_id' در اینجا در واقع 'cart_item_id' است.
        // FormRequest اعتبارسنجی می‌کند که این ID وجود دارد و معتبر است.
        $cartItemId = $request->item_id;
        $newQuantity = $request->quantity;

        try {
            // آیتم را پیدا می‌کنیم تا به سرویس پاس دهیم.
            // اطمینان حاصل کنید که این آیتم متعلق به کاربر/سشن فعلی است (سرویس این را بررسی می‌کند).
            $cartItem = CartItem::find($cartItemId);
            if (!$cartItem) {
                return response()->json(['success' => false, 'message' => 'آیتم سبد خرید یافت نشد.'], 404);
            }

            $response = $this->cartService->updateCartItemQuantity(
                $cartItem,
                $newQuantity,
                Auth::user(),
                Session::getId()
            );

            return response()->json($response->jsonSerialize(), $response->statusCode);

        } catch (BaseCartException $e) {
            Log::error('Cart operation error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItemId, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItemId, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در به‌روزرسانی تعداد محصول.'], 500);
        }
    }

    // متد mergeGuestCart از کنترلر حذف شده و فقط در CartService نگهداری می‌شود.
    // public function mergeGuestCart(User $user, string $guestSessionId): void { /* ... */ }
}
