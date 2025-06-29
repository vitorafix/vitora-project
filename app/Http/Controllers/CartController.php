<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB; // اضافه کردن DB برای تراکنش

class CartController extends Controller
{
    /**
     * Helper method to get the current user's cart or create one if it doesn't exist.
     * این متد هر دو نوع کاربر احراز هویت شده و مهمان را مدیریت می‌کند.
     *
     * @return \App\Models\Cart
     */
    private function getOrCreateCart(): Cart
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
        // اطمینان از eager loading محصول برای جلوگیری از N+1 problem در view
        $cartItems = $cart->items()->with('product')->get();

        return view('cart', compact('cartItems'));
    }

    /**
     * Add a product to the cart.
     * یک محصول را به سبد خرید اضافه می‌کند.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(Request $request)
    {
        // 1. اعتبارسنجی ورودی
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $productId = $request->product_id;
        $quantity = $request->quantity;

        // 2. پیدا کردن محصول
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['message' => 'محصول یافت نشد.'], 404);
        }

        // 3. بررسی موجودی انبار
        if ($product->stock < $quantity) {
            return response()->json(['message' => 'موجودی کافی برای این محصول وجود ندارد. موجودی فعلی: ' . $product->stock], 400);
        }

        // 4. دریافت یا ایجاد سبد خرید کاربر
        $cart = $this->getOrCreateCart();

        // 5. افزودن یا به‌روزرسانی آیتم در سبد خرید
        $cartItem = $cart->items()->where('product_id', $productId)->first();

        if ($cartItem) {
            // اگر آیتم قبلاً در سبد خرید بود، تعداد آن را افزایش دهید
            $newQuantity = $cartItem->quantity + $quantity;
            if ($product->stock < $newQuantity) {
                return response()->json(['message' => 'موجودی کافی برای افزودن بیشتر این محصول وجود ندارد.'], 400);
            }
            $cartItem->quantity = $newQuantity;
            $cartItem->save();
            $message = 'تعداد محصول در سبد خرید به‌روزرسانی شد!';
        } else {
            // اگر آیتم جدید است، آن را به سبد خرید اضافه کنید
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product->price, // بسیار مهم: قیمت را در زمان افزودن ذخیره کنید
            ]);
            $message = 'محصول با موفقیت به سبد خرید اضافه شد!';
        }

        // 6. محاسبه تعداد کل آیتم‌ها در سبد خرید برای به‌روزرسانی رابط کاربری (Mini-Cart)
        $totalItemsInCart = $cart->items()->sum('quantity');

        return response()->json([
            'message' => $message,
            'totalItemsInCart' => $totalItemsInCart // اضافه کردن totalItemsInCart به پاسخ
        ], 200);
    }

    /**
     * Update the quantity of a product in the cart.
     * تعداد یک محصول را در سبد خرید به‌روزرسانی می‌کند.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CartItem  $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, CartItem $cartItem)
    {
        // 1. اعتبارسنجی ورودی
        $request->validate([
            'quantity' => 'required|integer|min:0', // اجازه 0 برای حذف (یا منطق حذف جداگانه)
        ]);

        $newQuantity = $request->quantity;

        // 2. بررسی اینکه cartItem واقعاً متعلق به سبد خرید فعلی کاربر است
        $cart = $this->getOrCreateCart();
        if ($cartItem->cart_id !== $cart->id) {
            return response()->json(['message' => 'عملیات غیرمجاز.'], 403);
        }

        // 3. پیدا کردن محصول مرتبط
        $product = $cartItem->product;

        if (!$product) {
            return response()->json(['message' => 'محصول مرتبط یافت نشد.'], 404);
        }

        // 4. مدیریت حذف آیتم اگر quantity 0 باشد
        if ($newQuantity === 0) {
            $cartItem->delete();
            $message = 'محصول از سبد خرید حذف شد.';
        } else {
            // 5. بررسی موجودی انبار برای تعداد جدید
            if ($product->stock < $newQuantity) {
                return response()->json(['message' => 'موجودی کافی برای این تعداد وجود ندارد. موجودی فعلی: ' . $product->stock], 400);
            }
            $cartItem->quantity = $newQuantity;
            $cartItem->save();
            $message = 'تعداد محصول در سبد خرید به‌روزرسانی شد.';
        }

        // 6. محاسبه تعداد کل آیتم‌ها در سبد خرید
        $totalItemsInCart = $cart->items()->sum('quantity');

        return response()->json([
            'message' => $message,
            'totalItemsInCart' => $totalItemsInCart
        ], 200);
    }

    /**
     * Remove a product from the cart.
     * یک محصول را از سبد خرید حذف می‌کند.
     *
     * @param  \App\Models\CartItem  $cartItem
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(CartItem $cartItem)
    {
        // 1. بررسی اینکه cartItem واقعاً متعلق به سبد خرید فعلی کاربر است
        $cart = $this->getOrCreateCart();
        if ($cartItem->cart_id !== $cart->id) {
            return response()->json(['message' => 'عملیات غیرمجاز.'], 403);
        }

        // 2. حذف آیتم
        $cartItem->delete();

        // 3. محاسبه تعداد کل آیتم‌ها در سبد خرید
        $totalItemsInCart = $cart->items()->sum('quantity');

        return response()->json([
            'message' => 'محصول با موفقیت از سبد خرید حذف شد!',
            'totalItemsInCart' => $totalItemsInCart
        ], 200);
    }

    /**
     * Clear the entire cart.
     * کل سبد خرید را خالی می‌کند.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear()
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete(); // حذف تمام آیتم‌های سبد خرید

        return response()->json([
            'message' => 'سبد خرید با موفقیت خالی شد!',
            'totalItemsInCart' => 0
        ], 200);
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
        // اطمینان از eager loading محصول برای ارسال به فرانت‌اند
        $cartItems = $cart->items()->with('product')->get();
        $totalPrice = $cart->getTotalPrice(); // فرض می‌کنیم این متد در مدل Cart وجود دارد
        $totalItemsInCart = $cart->items->sum('quantity'); // محاسبه تعداد کل آیتم‌ها

        return response()->json([
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice,
            'totalItemsInCart' => $totalItemsInCart
        ], 200);
    }
}
