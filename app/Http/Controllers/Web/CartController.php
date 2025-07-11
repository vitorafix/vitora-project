<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\CartItem;
use App\Models\Product;

// Contracts
use App\Services\Contracts\CartServiceInterface;
use App\Services\CartCalculationService;

// Exceptions
use App\Exceptions\UnauthorizedCartAccessException;
use App\Exceptions\CartOperationException;
use App\Exceptions\InsufficientStockException;

class CartController extends Controller
{
    protected CartServiceInterface $cartService;
    protected CartCalculationService $cartCalculationService;

    public function __construct(CartServiceInterface $cartService, CartCalculationService $cartCalculationService)
    {
        $this->cartService = $cartService;
        $this->cartCalculationService = $cartCalculationService;
    }

    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $cartContents = $this->cartService->getCartContents($cart);

            return view('cart.index', [
                'cartItems' => $cartContents->items,
                'cartTotals' => $cartContents->cartTotals,
                'isEmpty' => empty($cartContents->items),
                'totalQuantity' => $cartContents->totalQuantity,
                'totalPrice' => $cartContents->totalPrice,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error loading cart: '.$e->getMessage());
            return back()->with('error', 'خطا در بارگذاری سبد خرید.');
        }
    }

    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);
        $quantity = $request->input('quantity', 1);

        try {
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $response = $this->cartService->addOrUpdateCartItem($cart, $product->id, $quantity);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'محصول با موفقیت اضافه شد.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Add to cart error: '.$e->getMessage());
            return back()->with('error', 'خطای سیستمی در افزودن محصول.');
        }
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $user = Auth::user();
        $sessionId = Session::getId();
        $quantity = $request->input('quantity');

        try {
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId)) {
                throw new UnauthorizedCartAccessException('اجازه دسترسی ندارید.');
            }

            $response = $this->cartService->updateCartItemQuantity($cartItem, $quantity, $user, $sessionId);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'تعداد آیتم به‌روزرسانی شد.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (UnauthorizedCartAccessException $e) {
            return back()->with('error', $e->getMessage());
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Update cart item error: '.$e->getMessage());
            return back()->with('error', 'خطا در به‌روزرسانی سبد.');
        }
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        $user = Auth::user();
        $sessionId = Session::getId();

        try {
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId)) {
                throw new UnauthorizedCartAccessException('اجازه دسترسی ندارید.');
            }

            $response = $this->cartService->removeCartItem($cartItem, $user, $sessionId);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'آیتم با موفقیت حذف شد.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (UnauthorizedCartAccessException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Remove cart item error: '.$e->getMessage());
            return back()->with('error', 'خطا در حذف آیتم.');
        }
    }

    public function clear(Request $request)
    {
        $user = Auth::user();
        $sessionId = Session::getId();

        try {
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $response = $this->cartService->clearCart($cart);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'سبد خرید پاک شد.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error('Clear cart error: '.$e->getMessage());
            return back()->with('error', 'خطا در پاک کردن سبد.');
        }
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $sessionId = Session::getId();
        $couponCode = $request->input('coupon_code');

        try {
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $response = $this->cartService->applyCoupon($cart, $couponCode);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'کد تخفیف اعمال شد.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error('Apply coupon error: '.$e->getMessage());
            return back()->with('error', 'خطا در اعمال کد تخفیف.');
        }
    }

    public function removeCoupon(Request $request)
    {
        $user = Auth::user();
        $sessionId = Session::getId();

        try {
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $response = $this->cartService->removeCoupon($cart);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'کد تخفیف حذف شد.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error('Remove coupon error: '.$e->getMessage());
            return back()->with('error', 'خطا در حذف کد تخفیف.');
        }
    }
}
