<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem; // همچنان برای Route Model Binding نیاز است

// Contracts
use App\Services\Contracts\CartServiceInterface; // ← اصلاح شده به فضای نام صحیح

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
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error displaying cart page: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در بارگذاری سبد خرید.'], 500);
        }
    }

    public function add(AddToCartRequest $request)
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');

        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $response = $this->cartService->addOrUpdateCartItem($cart, $productId, $quantity);

            return response()->json($response->jsonSerialize(), $response->statusCode);
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در افزودن محصول به سبد خرید.'], 500);
        }
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $newQuantity = $request->input('quantity');

        try {
            $response = $this->cartService->updateCartItemQuantity($cartItem, $newQuantity, Auth::user(), Session::getId());
            return response()->json($response->jsonSerialize(), $response->statusCode);
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in update method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in update method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در به‌روزرسانی تعداد محصول.'], 500);
        }
    }

    public function remove(CartItem $cartItem)
    {
        try {
            $response = $this->cartService->removeCartItem($cartItem, Auth::user(), Session::getId());
            return response()->json($response->jsonSerialize(), $response->statusCode);
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in remove method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in remove method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در حذف محصول از سبد خرید.'], 500);
        }
    }

    public function clear()
    {
        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $response = $this->cartService->clearCart($cart);
            return response()->json($response->jsonSerialize(), $response->statusCode);
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in clear method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in clear method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در خالی کردن سبد خرید.'], 500);
        }
    }

    public function getContents()
    {
        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $cartContents = $this->cartService->getCartContents($cart);

            return response()->json($cartContents->jsonSerialize());
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in getContents method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in getContents method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در دریافت محتویات سبد خرید.'], 500);
        }
    }

    public function updateQuantity(UpdateCartItemRequest $request): \Illuminate\Http\JsonResponse
    {
        $cartItemId = $request->item_id;
        $newQuantity = $request->quantity;

        try {
            $cartItem = CartItem::find($cartItemId);
            if (!$cartItem) {
                return response()->json(['success' => false, 'message' => 'آیتم سبد خرید یافت نشد.'], 404);
            }

            $response = $this->cartService->updateCartItemQuantity($cartItem, $newQuantity, Auth::user(), Session::getId());

            return response()->json($response->jsonSerialize(), $response->statusCode);
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItemId, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItemId, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در به‌روزرسانی تعداد محصول.'], 500);
        }
    }
}
