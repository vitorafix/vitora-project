<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem; // مطمئن شوید این خط اینجا وجود دارد
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Helper method to get the current user's cart or create one if it doesn't exist.
     * This method handles both authenticated users and guest users.
     *
     * @return \App\Models\Cart
     */
    private function getOrCreateCart()
    {
        if (Auth::check()) {
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        } else {
            $sessionId = Session::getId();
            $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
        }
        return $cart;
    }

    /**
     * Display the checkout page with cart contents.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        $cart = $this->getOrCreateCart();
        $cartItems = $cart->items()->with('product')->get();

        // اگر سبد خرید خالی است، به صفحه سبد خرید یا محصولات هدایت کنید
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'سبد خرید شما خالی است.');
        }

        return view('checkout', compact('cartItems', 'cart')); // ارسال $cart به ویو
    }

    /**
     * Process placing an order from the cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder(Request $request)
    {
        // === خط دیباگ: برای لاگ کردن درخواست در ابتدای متد ===
        // این لاگ باید در storage/logs/laravel.log ظاهر شود اگر درخواست به کنترلر برسد.
        Log::info('Entering PlaceOrder method. Request all: ' . json_encode($request->all()));

        try {
            // اعتبار سنجی اطلاعات آدرس و سایر فیلدهای لازم
            $request->validate([
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
            ]);

            $cart = $this->getOrCreateCart();
            $cartItems = $cart->items()->with('product')->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'سبد خرید شما خالی است و نمی‌توانید سفارش ثبت کنید.'], 400);
            }
            
            DB::beginTransaction();

            $totalAmount = $cart->getTotalPrice();

            $order = Order::create([
                'user_id' => Auth::id(),
                'session_id' => Auth::check() ? null : Session::getId(),
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'address' => $request->input('address'),
                'city' => $request->input('city'),
                'province' => $request->input('province'),
                'postal_code' => $request->input('postal_code'),
            ]);

            foreach ($cartItems as $cartItem) {
                $product = Product::find($cartItem->product_id);
                if (!$product || $product->stock < $cartItem->quantity) {
                    DB::rollBack();
                    return response()->json(['message' => 'موجودی کافی برای محصول ' . ($product ? $product->title : 'ناشناس') . ' وجود ندارد.'], 400);
                }

                OrderItem::create([ // اینجا از OrderItem استفاده می‌شود
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                ]);

                $product->decrement('stock', $cartItem->quantity);
            }

            $cart->items()->delete();
            $cart->delete(); 

            DB::commit();

            return response()->json(['message' => 'سفارش شما با موفقیت ثبت شد!', 'orderId' => $order->id], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order Placement Error: ' . $e->getMessage()); // این خطا حتماً باید در لاگ ثبت شود
            return response()->json(['message' => 'خطا در ثبت سفارش. لطفاً دوباره تلاش کنید.'], 500);
        }
    }

    /**
     * Display the order confirmation page.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\View\View
     */
    public function showConfirmation(Order $order)
    {
        $order->load('items.product');
        return view('order-confirmation', compact('order'));
    }
}
