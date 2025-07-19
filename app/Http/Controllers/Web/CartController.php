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
use App\Exceptions\ProductNotFoundException; // Added for consistency

class CartController extends Controller
{
    protected CartServiceInterface $cartService;
    protected CartCalculationService $cartCalculationService;

    public function __construct(CartServiceInterface $cartService, CartCalculationService $cartCalculationService)
    {
        $this->cartService = $cartService;
        $this->cartCalculationService = $cartCalculationService;
    }

    /**
     * Display the cart contents on the web cart page.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();
            // Pass both user and sessionId to getOrCreateCart
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
            Log::error('Error loading web cart page: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Error loading cart. Please try again.');
        }
    }

    /**
     * Add a product to the cart for web requests (redirects).
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function add(Request $request, Product $product)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);
        $quantity = $request->input('quantity', 1);

        try {
            // Pass both Auth::user() and Session::getId()
            $cart = $this->cartService->getOrCreateCart(Auth::user(), Session::getId());
            $response = $this->cartService->addOrUpdateCartItem($cart, $product->id, $quantity);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'Product successfully added to cart.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (ProductNotFoundException $e) { // Added ProductNotFoundException
            return back()->with('error', 'The requested product was not found.');
        } catch (CartOperationException $e) { // Added for general cart operation errors
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Web add to cart error: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'System error adding product to cart.');
        }
    }

    /**
     * Update the quantity of a cart item for web requests (redirects).
     *
     * @param Request $request
     * @param CartItem $cartItem
     * @return \Illuminate\Http\RedirectResponse
     */
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
                throw new UnauthorizedCartAccessException('You do not have permission to access this cart item.');
            }

            $response = $this->cartService->updateCartItemQuantity($cartItem, $quantity, $user, $sessionId);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'Item quantity updated.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (UnauthorizedCartAccessException $e) {
            return back()->with('error', $e->getMessage());
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        } catch (CartOperationException $e) { // Added for general cart operation errors
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Web update cart item error: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Error updating cart.');
        }
    }

    /**
     * Remove an item from the cart for web requests (redirects).
     *
     * @param Request $request
     * @param CartItem $cartItem
     * @return \Illuminate\Http\RedirectResponse
     */
    public function remove(Request $request, CartItem $cartItem)
    {
        $user = Auth::user();
        $sessionId = Session::getId();

        try {
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId)) {
                throw new UnauthorizedCartAccessException('You do not have permission to access this cart item.');
            }

            $response = $this->cartService->removeCartItem($cartItem, $user, $sessionId);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'Item successfully removed.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (UnauthorizedCartAccessException $e) {
            return back()->with('error', $e->getMessage());
        } catch (CartOperationException $e) { // Added for general cart operation errors
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Web remove cart item error: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Error removing item.');
        }
    }

    /**
     * Clear all items from the cart for web requests (redirects).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clear(Request $request)
    {
        $user = Auth::user();
        $sessionId = Session::getId();

        try {
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $response = $this->cartService->clearCart($cart);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'Cart cleared.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error('Web clear cart error: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Error clearing cart.');
        }
    }

    /**
     * Apply a coupon to the cart for web requests (redirects).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $sessionId = Session::getId();
        // Pass both user and sessionId
        $cart = $this->cartService->getOrCreateCart($user, $sessionId);
        $couponCode = $request->input('coupon_code');

        try {
            $response = $this->cartService->applyCoupon($cart, $couponCode);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'Coupon applied.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error('Web apply coupon error: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Error applying coupon.');
        }
    }

    /**
     * Remove a coupon from the cart for web requests (redirects).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeCoupon(Request $request)
    {
        $user = Auth::user();
        $sessionId = Session::getId();

        try {
            // Pass both user and sessionId
            $cart = $this->cartService->getOrCreateCart($user, $sessionId);
            $response = $this->cartService->removeCoupon($cart);

            if ($response->isSuccess()) {
                return redirect()->route('cart.index')->with('success', 'Coupon removed.');
            } else {
                return back()->with('error', $response->getMessage());
            }
        } catch (\Throwable $e) {
            Log::error('Web remove coupon error: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Error removing coupon.');
        }
    }
}
