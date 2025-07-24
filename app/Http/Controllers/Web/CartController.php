<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Str; // دیگر نیازی به این نیست زیرا GuestService مدیریت UUID را انجام می‌دهد
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
     * نمایش محتویات سبد خرید در صفحه سبد خرید وب.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            // Session ID و Guest UUID اکنون توسط CartService از شیء Request استخراج می‌شوند
            // و GuestUuidMiddleware مسئول تنظیم اولیه guest_uuid است.
            // بنابراین، نیازی به دریافت دستی آنها در اینجا نیست.

            // DEBUG LOGs: این لاگ‌ها برای اشکال‌زدایی اولیه مفید بودند، اما اکنون که GuestUuidMiddleware
            // مسئولیت را بر عهده گرفته، می‌توانند حذف شوند یا فقط برای موارد خاص نگه داشته شوند.
            // Log::debug('CartController::index Debug: guest_uuid from attributes: ' . ($request->attributes->get('guest_uuid') ?? 'NULL'));
            // Log::debug('CartController::index Debug: guest_uuid from cookie: ' . ($request->cookie('guest_uuid') ?? 'NULL'));
            // Log::debug('CartController::index Debug: guest_uuid from session: ' . ($request->session()->get('guest_uuid') ?? 'NULL'));

            // DEBUG LOG: Check Auth::user() before calling getOrCreateCart
            Log::debug('CartController::index: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Request present.');

            // Pass Request object and user to getOrCreateCart
            $cart = $this->cartService->getOrCreateCart($request, $user); // <-- تغییر مهم در اینجا
            $cartContents = $this->cartService->getCartContents($cart);

            // ایجاد پاسخ View
            $response = response()->view('cart.index', [
                'cartItems' => $cartContents->items,
                'cartTotals' => $cartContents->cartTotals,
                'isEmpty' => empty($cartContents->items),
                'totalQuantity' => $cartContents->totalQuantity,
                'totalPrice' => $cartContents->totalPrice,
                // Guest UUID اکنون از طریق GuestService و Middleware مدیریت می‌شود.
                // اگر نیاز به نمایش آن در فرانت‌اند دارید، می‌توانید از request()->attributes->get('guest_uuid') استفاده کنید.
                'guestUuidFromBackend' => $request->attributes->get('guest_uuid') // دریافت از attributes
            ]);

            // کوکی guest_uuid توسط GuestService و GuestUuidMiddleware مدیریت و تنظیم می‌شود.
            // نیازی به تنظیم مجدد آن در اینجا نیست، مگر اینکه منطق خاصی برای تمدید عمر آن داشته باشید که
            // GuestService آن را پوشش نمی‌دهد. اما برای پایداری، بهتر است به GuestService اعتماد کنید.
            // $response->cookie('guest_uuid', $guestUuid, 60 * 24 * 30); // 30 روز اعتبار (30 روز * 24 ساعت * 60 دقیقه)
            // Log::debug('CartController::index: Setting/Updating guest_uuid cookie: ' . $guestUuid);

            return $response;

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
            $user = Auth::user();
            // منطق دستی دریافت و تولید guest_uuid حذف شد.
            // GuestUuidMiddleware مسئول تنظیم آن در request->attributes است.

            // DEBUG LOG: Check Auth::user() before calling getOrCreateCart
            Log::debug('CartController::add: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Request present.');

            // Pass Request object and user to getOrCreateCart
            $cart = $this->cartService->getOrCreateCart($request, $user); // <-- تغییر مهم در اینجا
            $response = $this->cartService->addOrUpdateCartItem($cart, $product->id, $quantity);

            if ($response->isSuccess()) {
                // کوکی guest_uuid توسط GuestService و GuestUuidMiddleware مدیریت و تنظیم می‌شود.
                // نیازی به تنظیم مجدد آن در اینجا نیست.
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
        $quantity = $request->input('quantity'); // دریافت مقدار quantity

        $user = Auth::user();
        $sessionId = Session::getId();
        // دریافت guest_uuid از Request attributes (که توسط GuestUuidMiddleware تنظیم شده است)
        // این مقدار برای userOwnsCartItem و updateCartItemQuantity همچنان نیاز است.
        $guestUuid = $request->attributes->get('guest_uuid'); // <-- تغییر در اینجا

        // منطق دستی تولید guestUuid حذف شد.

        // DEBUG LOG: Check Auth::user(), Session::getId(), and guestUuid before calling userOwnsCartItem
        Log::debug('CartController::update: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

        try {
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId, $guestUuid)) { // پاس دادن guestUuid
                throw new UnauthorizedCartAccessException('You do not have permission to access this cart item.');
            }

            $response = $this->cartService->updateCartItemQuantity($cartItem, $quantity, $user, $sessionId, $guestUuid); // پاس دادن guestUuid

            if ($response->isSuccess()) {
                // کوکی guest_uuid توسط GuestService و GuestUuidMiddleware مدیریت و تنظیم می‌شود.
                // نیازی به تنظیم مجدد آن در اینجا نیست.
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
        // دریافت guest_uuid از Request attributes (که توسط GuestUuidMiddleware تنظیم شده است)
        // این مقدار برای userOwnsCartItem و removeCartItem همچنان نیاز است.
        $guestUuid = $request->attributes->get('guest_uuid'); // <-- تغییر در اینجا

        // منطق دستی تولید guestUuid حذف شد.

        // DEBUG LOG: Check Auth::user(), Session::getId(), and guestUuid before calling userOwnsCartItem
        Log::debug('CartController::remove: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Session ID: ' . $sessionId . ', Guest UUID: ' . ($guestUuid ?? 'NULL'));

        try {
            if (!$this->cartService->userOwnsCartItem($cartItem, $user, $sessionId, $guestUuid)) { // پاس دادن guestUuid
                throw new UnauthorizedCartAccessException('You do not have permission to access this cart item.');
            }

            $response = $this->cartService->removeCartItem($cartItem, $user, $sessionId, $guestUuid); // پاس دادن guestUuid

            if ($response->isSuccess()) {
                // کوکی guest_uuid توسط GuestService و GuestUuidMiddleware مدیریت و تنظیم می‌شود.
                // نیازی به تنظیم مجدد آن در اینجا نیست.
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
        // منطق دستی دریافت و تولید guest_uuid حذف شد.

        // DEBUG LOG: Check Auth::user() before calling getOrCreateCart
        Log::debug('CartController::clear: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Request present.');

        try {
            $cart = $this->cartService->getOrCreateCart($request, $user); // <-- تغییر مهم در اینجا
            $response = $this->cartService->clearCart($cart);

            if ($response->isSuccess()) {
                // کوکی guest_uuid توسط GuestService و GuestUuidMiddleware مدیریت و تنظیم می‌شود.
                // نیازی به تنظیم مجدد آن در اینجا نیست.
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
        // منطق دستی دریافت و تولید guest_uuid حذف شد.

        // DEBUG LOG: Check Auth::user() before calling getOrCreateCart
        Log::debug('CartController::applyCoupon: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Request present.');

        // Pass Request object and user
        $cart = $this->cartService->getOrCreateCart($request, $user); // <-- تغییر مهم در اینجا
        $couponCode = $request->input('coupon_code');

        try {
            $response = $this->cartService->applyCoupon($cart, $couponCode);

            if ($response->isSuccess()) {
                // کوکی guest_uuid توسط GuestService و GuestUuidMiddleware مدیریت و تنظیم می‌شود.
                // نیازی به تنظیم مجدد آن در اینجا نیست.
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
        // منطق دستی دریافت و تولید guest_uuid حذف شد.

        // DEBUG LOG: Check Auth::user() before calling getOrCreateCart
        Log::debug('CartController::removeCoupon: Auth User ID: ' . ($user ? $user->id : 'NULL') . ', Request present.');

        try {
            // Pass Request object and user
            $cart = $this->cartService->getOrCreateCart($request, $user); // <-- تغییر مهم در اینجا
            $response = $this->cartService->removeCoupon($cart);

            if ($response->isSuccess()) {
                // کوکی guest_uuid توسط GuestService و GuestUuidMiddleware مدیریت و تنظیم می‌شود.
                // نیازی به تنظیم مجدد آن در اینجا نیست.
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
