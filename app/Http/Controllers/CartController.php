<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session; // برای استفاده از Session (برای کاربران مهمان)

class CartController extends Controller
{
    /**
     * Helper method to get the current user's cart or create one if it doesn't exist.
     * این متد هر دو نوع کاربر احراز هویت شده و مهمان را مدیریت می‌کند.
     */
    private function getOrCreateCart()
    {
        if (Auth::check()) {
            // برای کاربران لاگین شده: سبد خرید بر اساس user_id
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        } else {
            // برای کاربران مهمان: سبد خرید بر اساس session_id
            $sessionId = Session::getId();
            $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        }
        return $cart;
    }

    /**
     * Display the cart page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $cart = $this->getOrCreateCart();
        $cartItems = $cart->items()->with('product')->get(); // آیتم‌ها و اطلاعات محصول مرتبط را لود می‌کند

        return view('cart', compact('cartItems')); // فرض می‌کنیم فایل view 'cart.blade.php' را خواهیم ساخت
    }

    /**
     * Add a product to the cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($request->product_id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        if ($product->stock < $request->quantity) {
            return response()->json(['message' => 'Not enough stock available.'], 400);
        }

        $cart = $this->getOrCreateCart();

        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            // اگر محصول قبلاً در سبد خرید بود، تعداد را افزایش بده
            $newQuantity = $cartItem->quantity + $request->quantity;
            if ($product->stock < $newQuantity) {
                return response()->json(['message' => 'Adding this quantity exceeds available stock.'], 400);
            }
            $cartItem->quantity = $newQuantity;
            $cartItem->save();
        } else {
            // اگر محصول جدید است، به سبد خرید اضافه کن
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'price' => $product->price, // قیمت لحظه افزودن به سبد
            ]);
        }

        // تعداد کل آیتم‌ها در سبد خرید (برای نمایش در Mini Cart)
        $totalItemsInCart = $cart->items->sum('quantity');

        return response()->json([
            'message' => 'Product added to cart successfully!',
            'cartItem' => $cartItem->load('product'), // لود کردن اطلاعات محصول برای نمایش در فرانت‌اند
            'totalItemsInCart' => $totalItemsInCart
        ]);
    }

    /**
     * Update product quantity in the cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CartItem  $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, CartItem $cartItem)
    {
        // اطمینان حاصل کنید که آیتم متعلق به سبد خرید کاربر فعلی است
        $cart = $this->getOrCreateCart();
        if ($cartItem->cart_id !== $cart->id) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:0', // 0 برای حذف آیتم
        ]);

        $product = $cartItem->product; // محصول مرتبط با آیتم سبد خرید را بگیرید
        if (!$product) {
            return response()->json(['message' => 'Product not found for this cart item.'], 404);
        }

        if ($request->quantity > 0 && $product->stock < $request->quantity) {
            return response()->json(['message' => 'Updating this quantity exceeds available stock.'], 400);
        }

        if ($request->quantity === 0) {
            $cartItem->delete();
            $message = 'Product removed from cart successfully!';
        } else {
            $cartItem->quantity = $request->quantity;
            $cartItem->save();
            $message = 'Cart updated successfully!';
        }

        $totalItemsInCart = $cart->items()->sum('quantity'); // دوباره محاسبه کنید چون آیتم ممکن است حذف شده باشد

        return response()->json([
            'message' => $message,
            'cartItem' => $cartItem->load('product'), // آیتم سبد خرید بروز شده (برای اطمینان)
            'totalItemsInCart' => $totalItemsInCart
        ]);
    }

    /**
     * Remove a product from the cart.
     *
     * @param  \App\Models\CartItem  $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(CartItem $cartItem)
    {
        $cart = $this->getOrCreateCart();
        if ($cartItem->cart_id !== $cart->id) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $cartItem->delete();

        $totalItemsInCart = $cart->items()->sum('quantity');

        return response()->json([
            'message' => 'Product removed from cart successfully!',
            'totalItemsInCart' => $totalItemsInCart
        ]);
    }

    /**
     * Clear the entire cart.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear()
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete(); // حذف تمام آیتم‌های سبد خرید

        return response()->json([
            'message' => 'Cart cleared successfully!',
            'totalItemsInCart' => 0
        ]);
    }

    /**
     * Get current cart contents and total.
     * توسط app.js برای پر کردن mini-cart و main cart استفاده می‌شود.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContents()
    {
        $cart = $this->getOrCreateCart();
        $cartItems = $cart->items()->with('product')->get();
        $totalPrice = $cart->getTotalPrice();
        $totalItemsInCart = $cart->items->sum('quantity');

        return response()->json([
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice,
            'totalItemsInCart' => $totalItemsInCart
        ]);
    }
}
