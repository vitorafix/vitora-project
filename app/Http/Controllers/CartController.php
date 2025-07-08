<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem; // Still needed for Route Model Binding
use App\Models\Product; // Import Product model for route model binding

// Contracts
use App\Services\Contracts\CartServiceInterface;
use App\Contracts\Services\CouponService; // Import CouponService

// Form Requests (you should ensure these exist and are correctly defined)
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Requests\Cart\ApplyCouponRequest;
// use App\Http\Requests\Cart\UpdateMultipleCartItemsRequest; // If you use updateMultipleCartItems method

// Custom Exceptions
use App\Exceptions\BaseCartException;
use App\Exceptions\Cart\CartLimitExceededException;
use App\Exceptions\Cart\InsufficientStockException;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\CartOperationException;
use App\Exceptions\UnauthorizedCartAccessException; // Ensure this is imported if used

class CartController extends Controller
{
    protected CartServiceInterface $cartService;
    protected CouponService $couponService;

    public function __construct(CartServiceInterface $cartService, CouponService $couponService)
    {
        $this->cartService = $cartService;
        $this->couponService = $couponService;
    }

    /**
     * Display the cart contents.
     * نمایش محتویات سبد خرید.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $sessionId = Session::getId();
        $cart = $this->cartService->getOrCreateCart($user, $sessionId);
        $cartContents = $this->cartService->getCartContents($cart);

        // Calculate totals for the main cart display
        $cartTotals = $this->cartService->calculateCartTotals($cart);

        return view('cart', [
            'cartContents' => $cartContents->getItems(),
            'totalQuantity' => $cartContents->getTotalQuantity(),
            'totalPrice' => $cartContents->getTotalPrice(),
            'cartTotals' => $cartTotals, // Pass cart totals to the view
        ]);
    }

    /**
     * Get the current cart contents as JSON.
     * دریافت محتویات فعلی سبد خرید به صورت JSON.
     * This method is called by the frontend JavaScript to display cart items.
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
            Log::error('Error fetching cart contents: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت محتویات سبد خرید.',
            ], 500);
        }
    }


    /**
     * Add product to cart or update quantity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product  // Laravel will automatically inject the Product model based on the route parameter
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request, Product $product)
    {
        try {
            // Validate the request (e.g., quantity)
            $request->validate([
                'quantity' => 'nullable|integer|min:1',
            ]);

            $quantity = $request->input('quantity', 1); // Get quantity from request body, default to 1

            // Call the CartService to add or update the item
            $response = $this->cartService->addOrUpdateCartItem(
                $this->cartService->getOrCreateCart(Auth::user(), Session::getId()),
                $product->id, // Use the ID from the injected Product model
                $quantity
            );

            if ($response->isSuccess()) {
                $cartTotals = $this->cartService->calculateCartTotals($response->getCart());
                return response()->json([
                    'success' => true,
                    'message' => 'محصول با موفقیت به سبد خرید اضافه شد.',
                    'cartTotals' => $cartTotals,
                    'cart' => $response->getCart()->toArray(), // Return updated cart data
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response->getMessage(),
                ], $response->getStatusCode());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error adding item to cart: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی ورودی.',
                'errors' => $e->errors(),
            ], 422); // Unprocessable Entity
        } catch (CartOperationException $e) { // Use the specific exception
            Log::error('Cart operation error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Throwable $e) {
            Log::error('Unexpected error adding item to cart: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'خطای سیستمی در اضافه کردن محصول به سبد خرید.',
            ], 500);
        }
    }

    /**
     * Update the quantity of a cart item.
     * به‌روزرسانی تعداد یک آیتم در سبد خرید.
     *
     * @param Request $request
     * @param CartItem $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request, CartItem $cartItem)
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // Ensure the user owns the cart item
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId)) {
                throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این آیتم سبد خرید را ندارید.');
            }

            $request->validate([
                'quantity' => 'required|integer|min:0',
            ]);

            $newQuantity = $request->input('quantity');

            $response = $this->cartService->updateCartItemQuantity($cartItem, $newQuantity, $user, $sessionId);

            if ($response->isSuccess()) {
                $cartTotals = $this->cartService->calculateCartTotals($cartItem->cart->fresh());
                return response()->json([
                    'success' => true,
                    'message' => 'تعداد آیتم سبد خرید با موفقیت به‌روزرسانی شد.',
                    'cartTotals' => $cartTotals,
                    'cartItem' => $cartItem->fresh()->toArray(), // Return updated cart item data
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
                'message' => 'خطا در اعتبارسنجی ورودی.',
                'errors' => $e->errors(),
            ], 422);
        } catch (UnauthorizedCartAccessException $e) {
            Log::error('Unauthorized cart access: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403); // Forbidden
        } catch (CartOperationException $e) {
            Log::error('Cart operation error: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Throwable $e) {
            Log::error('Error updating cart item quantity: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در به‌روزرسانی تعداد آیتم سبد خرید.'], 500);
        }
    }

    /**
     * Remove an item from the cart.
     * حذف یک آیتم از سبد خرید.
     *
     * @param Request $request
     * @param CartItem $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeCartItem(Request $request, CartItem $cartItem)
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // Ensure the user owns the cart item
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId)) {
                throw new UnauthorizedCartAccessException('شما اجازه دسترسی به این آیتم سبد خرید را ندارید.');
            }

            $response = $this->cartService->removeCartItem($cartItem, $user, $sessionId);

            if ($response->isSuccess()) {
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
            return response()->json(['success' => false, 'message' => 'خطا در حذف آیتم از سبد خرید.'], 500);
        }
    }

    /**
     * Apply a coupon to the cart.
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
            $response = $this->couponService->applyCoupon($cart, $couponCode);

            if ($response->isSuccess()) {
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
                'message' => 'خطا در اعتبارسنجی کد تخفیف.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error applying coupon: ' . $e->getMessage(), ['coupon_code' => $couponCode, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در اعمال کد تخفیف.'], 500);
        }
    }

    public function removeCoupon(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            // فرض بر این است که removeCoupon یک boolean برمی‌گرداند
            $success = $this->couponService->removeCoupon($cart);

            if ($success) {
                $cartTotals = $this->cartService->calculateCartTotals($cart->fresh());
                return response()->json([
                    'success' => true, // اضافه شدن فیلد success
                    'message' => 'کد تخفیف با موفقیت حذف شد.',
                    'cartTotals' => $cartTotals,
                ], 200);
            } else {
                return response()->json([
                    'success' => false, // اضافه شدن فیلد success
                    'message' => 'کد تخفیفی برای حذف وجود ندارد.'
                ], 400);
            }
        } catch (\Throwable $e) {
            Log::error('Error removing coupon: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در حذف کد تخفیف.'], 500);
        }
    }
}
