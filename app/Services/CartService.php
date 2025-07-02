<?php

namespace App\Services;

use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log; // اضافه شد برای لاگ‌گذاری

class CartService
{
    /**
     * Helper method to get the current user's cart or create one if it doesn't exist.
     * این متد هر دو نوع کاربر احراز هویت شده و مهمان را مدیریت می‌کند.
     *
     * @param User|null $user
     * @param string|null $sessionId
     * @return \App\Models\Cart
     * @throws \Exception اگر نه کاربر و نه session_id مشخص شود.
     */
    public function getOrCreateCart(?User $user = null, ?string $sessionId = null): Cart
    {
        if ($user) {
            Log::info('Fetching or creating cart for user', ['user_id' => $user->id]);
            return Cart::firstOrCreate(['user_id' => $user->id]);
        } elseif ($sessionId) {
            Log::info('Fetching or creating cart for guest session', ['session_id' => $sessionId]);
            return Cart::firstOrCreate(['session_id' => $sessionId]);
        }
        // Fallback: This should ideally be called with user or sessionId
        $currentSessionId = Session::getId();
        Log::warning('getOrCreateCart called without user or explicit session_id, using current session_id', ['current_session_id' => $currentSessionId]);
        return Cart::firstOrCreate(['session_id' => $currentSessionId]);
    }

    /**
     * منطق انتقال سبد خرید مهمان (بر اساس session_id) به سبد خرید کاربر لاگین شده.
     * این متد برای زمانی است که کاربر موجود وارد می‌شود.
     *
     * @param string $guestSessionId شناسه سشن کاربر مهمان.
     * @param User $user آبجکت کاربر لاگین شده.
     * @return void
     * @throws \Exception در صورت بروز خطا در تراکنش.
     */
    public function transferGuestCartToUserCart(string $guestSessionId, User $user): void
    {
        try {
            DB::transaction(function () use ($guestSessionId, $user) {
                // با eager loading آیتم‌ها و lockForUpdate برای مدیریت همزمانی
                $guestCart = Cart::where('session_id', $guestSessionId)->with('items')->lockForUpdate()->first();

                if ($guestCart) {
                    Log::info('Guest cart found for transfer', ['guest_session_id' => $guestSessionId, 'user_id' => $user->id]);

                    // پیدا کردن یا ایجاد سبد خرید برای کاربر لاگین شده با lockForUpdate
                    // ابتدا با قفل رکورد را پیدا می‌کنیم
                    $userCart = Cart::where('user_id', $user->id)->lockForUpdate()->first();
                    if (!$userCart) {
                        // اگر پیدا نشد، آن را ایجاد می‌کنیم
                        $userCart = Cart::create(['user_id' => $user->id]);
                        Log::info('Created new user cart during transfer', ['user_id' => $user->id, 'cart_id' => $userCart->id]);
                    } else {
                        Log::info('Found existing user cart for transfer', ['user_id' => $user->id, 'cart_id' => $userCart->id]);
                    }

                    foreach ($guestCart->items as $guestCartItem) {
                        $existingCartItem = $userCart->items()->where('product_id', $guestCartItem->product_id)->first();
                        if ($existingCartItem) {
                            $existingCartItem->quantity += $guestCartItem->quantity;
                            $existingCartItem->save();
                            Log::info('Merged existing cart item quantity', ['user_id' => $user->id, 'product_id' => $guestCartItem->product_id, 'new_quantity' => $existingCartItem->quantity]);
                        } else {
                            // انتقال آیتم مهمان به سبد خرید کاربر
                            $guestCartItem->cart_id = $userCart->id;
                            $guestCartItem->save();
                            Log::info('Transferred new cart item to user cart', ['user_id' => $user->id, 'product_id' => $guestCartItem->product_id]);
                        }
                    }
                    // حذف سبد خرید مهمان از دیتابیس
                    $guestCart->delete();
                    Log::info('Guest cart deleted after transfer', ['guest_session_id' => $guestSessionId]);
                } else {
                    Log::info('No guest cart found for transfer', ['guest_session_id' => $guestSessionId]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error during guest cart transfer to existing user: ' . $e->getMessage(), [
                'guest_session_id' => $guestSessionId,
                'user_id' => $user->id,
                'exception' => $e->getTraceAsString() // اضافه کردن stack trace برای اشکال‌زدایی
            ]);
            throw $e; // پرتاب مجدد استثنا برای مدیریت در سطح بالاتر (کنترلر)
        }
    }

    /**
     * منطق انتقال سبد خرید مهمان (بر اساس session_id) به کاربر جدید (با تغییر user_id سبد خرید).
     * این متد برای زمانی است که کاربر جدید ثبت‌نام می‌کند.
     *
     * @param string $guestSessionId شناسه سشن کاربر مهمان.
     * @param User $newUser آبجکت کاربر جدید.
     * @return void
     * @throws \Exception در صورت بروز خطا در تراکنش.
     */
    public function assignGuestCartToNewUser(string $guestSessionId, User $newUser): void
    {
        try {
            DB::transaction(function () use ($guestSessionId, $newUser) {
                // با lockForUpdate برای مدیریت همزمانی
                $guestCart = Cart::where('session_id', $guestSessionId)->lockForUpdate()->first();
                if ($guestCart) {
                    Log::info('Guest cart found for new user assignment', ['guest_session_id' => $guestSessionId, 'new_user_id' => $newUser->id]);
                    $guestCart->user_id = $newUser->id; // اختصاص user_id به سبد خرید مهمان
                    $guestCart->session_id = null; // پاک کردن session_id
                    $guestCart->save();
                    Log::info('Guest cart assigned to new user and session_id cleared', ['new_user_id' => $newUser->id]);
                    // آیتم‌های CartItem به طور خودکار به این سبد خرید جدید (که اکنون user_id دارد) متصل می‌مانند.
                } else {
                    Log::info('No guest cart found for new user assignment', ['guest_session_id' => $guestSessionId]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error during guest cart assignment to new user: ' . $e->getMessage(), [
                'guest_session_id' => $guestSessionId,
                'new_user_id' => $newUser->id,
                'exception' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * افزودن/به‌روزرسانی آیتم در سبد خرید دیتابیسی.
     * این متد توسط CartController@add برای هر دو کاربر لاگین شده و مهمان فراخوانی می‌شود.
     *
     * @param \App\Models\Cart $cart آبجکت سبد خرید (کاربر لاگین شده یا مهمان).
     * @param int $productId شناسه محصول.
     * @param int $quantity تعداد محصول.
     * @param \App\Models\Product $product آبجکت محصول.
     * @return string پیام وضعیت عملیات.
     * @throws \Exception در صورت بروز خطا در تراکنش.
     */
    public function addOrUpdateCartItem(Cart $cart, int $productId, int $quantity, Product $product): string
    {
        $message = '';
        try {
            DB::transaction(function () use ($cart, $productId, $quantity, $product, &$message) {
                // با lockForUpdate برای مدیریت همزمانی در سطح آیتم سبد خرید
                $cartItem = $cart->items()->where('product_id', $productId)->lockForUpdate()->first();

                if ($cartItem) {
                    $newQuantity = $cartItem->quantity + $quantity;
                    if ($product->stock < $newQuantity) {
                        $message = 'موجودی کافی برای افزودن بیشتر این محصول وجود ندارد.';
                        Log::warning('Insufficient stock for cart item update', ['cart_id' => $cart->id, 'product_id' => $productId, 'requested_quantity' => $newQuantity, 'current_stock' => $product->stock]);
                        return;
                    }
                    $cartItem->quantity = $newQuantity;
                    $cartItem->save();
                    $message = 'تعداد محصول در سبد خرید به‌روزرسانی شد!';
                    Log::info('Cart item quantity updated', ['cart_id' => $cart->id, 'product_id' => $productId, 'new_quantity' => $newQuantity]);
                } else {
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $product->price,
                    ]);
                    $message = 'محصول با موفقیت به سبد خرید اضافه شد!';
                    Log::info('New cart item added', ['cart_id' => $cart->id, 'product_id' => $productId, 'quantity' => $quantity]);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error during add/update cart item: ' . $e->getMessage(), [
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => $quantity,
                'exception' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        return $message;
    }
}
