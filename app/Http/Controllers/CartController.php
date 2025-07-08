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
use App\Http\Requests\Cart\UpdateCartItemRequest; // اصلاح شده: خطای تایپی -> به \
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

    public function __construct(CartServiceInterface $cartService, CouponService $couponService)
    {
        $this->cartService = $cartService;
        $this->couponService = $couponService;
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // getOrCreateCart now handles eager loading of items
            // getOrCreateCart اکنون بارگذاری eager آیتم‌ها را مدیریت می‌کند
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
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error displaying cart page: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['message' => 'خطا در بارگذاری سبد خرید.'], 500);
        }
    }

    // متد getContents برای بازگرداندن محتویات سبد خرید به فرانت‌اند
    public function getContents(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // getOrCreateCart now handles eager loading of items
            // getOrCreateCart اکنون بارگذاری eager آیتم‌ها را مدیریت می‌کند
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $cartContents = $this->cartService->getCartContents($cart);

            // اطمینان از اینکه پاسخ شامل فیلد 'success' و داده‌های مورد انتظار cart.js باشد
            return response()->json([
                'success' => true,
                'message' => 'محتویات سبد خرید با موفقیت دریافت شد.',
                'items' => $cartContents->items,            // آرایه آیتم‌ها
                'total_quantity' => $cartContents->totalQuantity,  // تعداد کل آیتم‌ها
                'total_price' => $cartContents->totalPrice           // مجموع قیمت کل
            ]);
        } catch (BaseCartException $e) {
            Log::error('Error fetching cart contents: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching cart contents: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در دریافت محتویات سبد خرید.'], 500);
        }
    }

    public function add(AddToCartRequest $request): \Illuminate\Http\JsonResponse
    {
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        $variantId = $request->input('product_variant_id');

        try {
            // getOrCreateCart now handles eager loading of items
            // getOrCreateCart اکنون بارگذاری eager آیتم‌ها را مدیریت می‌کند
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $response = $this->cartService->addOrUpdateCartItem($cart, $productId, $quantity, $variantId);

            if ($response->isSuccess()) {
                // Fetch the updated cart again to ensure latest contents are loaded after modification
                // سبد خرید به‌روز شده را دوباره دریافت کنید تا از بارگذاری آخرین محتویات پس از تغییر اطمینان حاصل شود
                $updatedCart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
                $updatedCartContents = $this->cartService->getCartContents($updatedCart);

                return response()->json([
                    'success' => true, // اضافه شدن فیلد success
                    'message' => $response->getMessage(),
                    'data' => $response->getData(),
                    'totalQuantity' => $updatedCartContents->totalQuantity
                ], $response->getCode() ?: 200);
            } else {
                return response()->json([
                    'success' => false, // اضافه شدن فیلد success
                    'message' => $response->getMessage()
                ], $response->getCode() ?: 400);
            }
        } catch (InsufficientStockException $e) {
            Log::error('Insufficient stock error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (CartLimitExceededException $e) {
            Log::error('Cart limit exceeded error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in add method: ' . $e->getMessage(), ['product_id' => $productId, 'quantity' => $quantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در افزودن محصول به سبد خرید.'], 500);
        }
    }

    public function updateQuantity(UpdateCartItemRequest $request, CartItem $cartItem): \Illuminate\Http\JsonResponse
    {
        $newQuantity = $request->input('quantity');

        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            $response = $this->cartService->updateCartItemQuantity($cartItem, $newQuantity, $user, $sessionId);

            if ($response->isSuccess()) {
                return response()->json([
                    'success' => true, // اضافه شدن فیلد success
                    'message' => $response->getMessage(), 
                    'data' => $response->getData()
                ], $response->getCode() ?: 200);
            } else {
                return response()->json([
                    'success' => false, // اضافه شدن فیلد success
                    'message' => $response->getMessage()
                ], $response->getCode() ?: 400);
            }
        } catch (InsufficientStockException $e) {
            Log::error('Insufficient stock error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (CartLimitExceededException $e) {
            Log::error('Cart limit exceeded error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in updateQuantity method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'new_quantity' => $newQuantity, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در به‌روزرسانی تعداد محصول.'], 500);
        }
    }

    public function removeCartItem(CartItem $cartItem): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            $response = $this->cartService->removeCartItem($cartItem, $user, $sessionId);

            if ($response->isSuccess()) {
                return response()->json([
                    'success' => true, // اضافه شدن فیلد success
                    'message' => $response->getMessage(), 
                    'data' => $response->getData()
                ], $response->getCode() ?: 200);
            } else {
                return response()->json([
                    'success' => false, // اضافه شدن فیلد success
                    'message' => $response->getMessage()
                ], $response->getCode() ?: 400);
            }
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in removeCartItem method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in removeCartItem method: ' . $e->getMessage(), ['cart_item_id' => $cartItem->id, 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در حذف محصول از سبد خرید.'], 500);
        }
    }

    public function clearCart(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            // getOrCreateCart now handles eager loading of items
            // getOrCreateCart اکنون بارگذاری eager آیتم‌ها را مدیریت می‌کند
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $response = $this->cartService->clearCart($cart);

            if ($response->isSuccess()) {
                return response()->json([
                    'success' => true, // اضافه شدن فیلد success
                    'message' => $response->getMessage()
                ], $response->getCode() ?: 200);
            } else {
                return response()->json([
                    'success' => false, // اضافه شدن فیلد success
                    'message' => $response->getMessage()
                ], $response->getCode() ?: 400);
            }
        } catch (BaseCartException $e) {
            Log::error('Cart operation error in clear method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode());
        } catch (\Throwable $e) {
            Log::error('Unexpected error in clear method: ' . $e->getMessage(), ['user_id' => Auth::id(), 'session_id' => Session::getId(), 'exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در خالی کردن سبد خرید.'], 500);
        }
    }

    public function applyCoupon(ApplyCouponRequest $request): \Illuminate\Http\JsonResponse
    {
        $couponCode = $request->input('coupon_code');

        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            // getOrCreateCart now handles eager loading of items
            // getOrCreateCart اکنون بارگذاری eager آیتم‌ها را مدیریت می‌کند
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            // فرض بر این است که applyCoupon یک boolean برمی‌گرداند
            $success = $this->couponService->applyCoupon($cart, $couponCode);

            if ($success) {
                // Refresh cart to ensure latest totals are calculated after coupon application
                // سبد خرید را رفرش کنید تا از محاسبه آخرین مجموع‌ها پس از اعمال کوپن اطمینان حاصل شود
                $cartTotals = $this->cartService->calculateCartTotals($cart->fresh());
                return response()->json([
                    'success' => true, // اضافه شدن فیلد success
                    'message' => 'کد تخفیف با موفقیت اعمال شد.',
                    'cartTotals' => $cartTotals,
                ], 200);
            } else {
                return response()->json([
                    'success' => false, // اضافه شدن فیلد success
                    'message' => 'کد تخفیف نامعتبر یا منقضی شده است.'
                ], 400);
            }
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
            // getOrCreateCart now handles eager loading of items
            // getOrCreateCart اکنون بارگذاری eager آیتم‌ها را مدیریت می‌کند
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);

            // فرض بر این است که removeCoupon یک boolean برمی‌گرداند
            $success = $this->couponService->removeCoupon($cart);

            if ($success) {
                // Refresh cart to ensure latest totals are calculated after coupon removal
                // سبد خرید را رفرش کنید تا از محاسبه آخرین مجموع‌ها پس از حذف کوپن اطمینان حاصل شود
                $cartTotals = $this->cartService->calculateCartTotals($cart->fresh());
                return response()->json([
                    'success' => true, // اضافه شدن فیلد success
                    'message' => 'کد تخفیف با موفقیت حذف شد.',
                    'cartTotals' => $cartTotals,
                ], 200);
            } else {
                return response()->json([
                    'success' => false, // اضافه شدن فیلd success
                    'message' => 'کد تخفیفی برای حذف وجود ندارد.'
                ], 400);
            }
        } catch (\Throwable $e) {
            Log::error('Error removing coupon: ' . $e->getMessage(), ['exception' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'خطا در حذف کد تخفیف.'], 500);
        }
    }
}
