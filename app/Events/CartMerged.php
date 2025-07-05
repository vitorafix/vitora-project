<?php
namespace App\Events;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // اضافه شده برای Broadcasting
use InvalidArgumentException; // اضافه شده برای اعتبارسنجی
use Carbon\Carbon; // اضافه شده برای استفاده از now()

/**
 * رویداد CartMerged زمانی فعال می‌شود که دو سبد خرید (معمولاً سبد مهمان و سبد کاربر) ترکیب شوند
 *
 * @package App\Events
 */
class CartMerged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * سبد خریدی که از آن ترکیب شده (مثلاً سبد مهمان)
     *
     * @var \App\Models\Cart
     */
    public Cart $fromCart;

    /**
     * سبد خریدی که به آن ترکیب شده (مثلاً سبد کاربر)
     *
     * @var \App\Models\Cart
     */
    public Cart $toCart;

    /**
     * کاربر مرتبط با عملیات ترکیب
     *
     * @var \App\Models\User
     */
    public User $user;

    /**
     * تاریخ و زمان ترکیب سبدها
     *
     * @var string
     */
    public string $mergedAt;

    /**
     * تعداد آیتم‌های منحصر به فرد ترکیب شده در زمان رویداد
     *
     * @var int
     */
    public int $mergedItemsCount;

    /**
     * سازنده کلاس
     *
     * @param \App\Models\Cart $fromCart سبد خریدی که از آن ترکیب شده
     * @param \App\Models\Cart $toCart سبد خریدی که به آن ترکیب شده
     * @param \App\Models\User $user کاربر مرتبط با عملیات ترکیب
     * @throws \InvalidArgumentException اگر سبدها یکسان باشند
     */
    public function __construct(Cart $fromCart, Cart $toCart, User $user)
    {
        // اعتبارسنجی: اطمینان از اینکه سبدها یکسان نیستند (نمی‌توان سبد را با خودش ترکیب کرد)
        if ($fromCart->id === $toCart->id) {
            throw new InvalidArgumentException('Cannot merge a cart into itself.');
        }
        // اعتبارسنجی: اطمینان از اینکه سبد مقصد متعلق به کاربر است (اختیاری، معمولاً در لایه سرویس انجام می‌شود)
        // if ($toCart->user_id !== $user->id) {
        //     throw new InvalidArgumentException('Target cart does not belong to the specified user.');
        // }

        $this->fromCart = $fromCart;
        $this->toCart = $toCart;
        $this->user = $user;
        $this->mergedAt = Carbon::now()->toDateTimeString(); // استفاده از Carbon
        // محاسبه تعداد آیتم‌های منحصر به فرد از سبد مبدا در زمان ترکیب
        $this->mergedItemsCount = $fromCart->items()->count();
    }

    /**
     * کانال‌هایی که رویداد باید روی آن‌ها برودکست شود.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [
            'cart-updates.' . $this->user->id,
            'user-notifications.' . $this->user->id
        ];
    }

    /**
     * نام رویداد برای Broadcasting.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'cart.merged';
    }

    /**
     * داده‌های ارسالی در Broadcasting.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'message' => 'Your cart has been successfully merged.', // پیام به انگلیسی برای Broadcasting
            'merged_items_count' => $this->mergedItemsCount,
            'total_items_in_new_cart' => $this->toCart->items()->count(), // تعداد فعلی آیتم‌ها در سبد جدید
            'merged_at' => $this->mergedAt,
            'to_cart_id' => $this->toCart->id,
            'from_cart_id' => $this->fromCart->id,
            'user_id' => $this->user->id
        ];
    }

    /**
     * اطلاعات خلاصه عملیات ترکیب سبد خرید را برای لاگ‌گیری و تحلیل برمی‌گرداند.
     *
     * @return array
     */
    public function getMergedSummary(): array
    {
        return [
            'user_id' => $this->user->id,
            'from_cart_id' => $this->fromCart->id,
            'to_cart_id' => $this->toCart->id,
            'merged_items_count' => $this->mergedItemsCount,
            'merged_at' => $this->mergedAt,
            'total_items_in_new_cart' => $this->toCart->items()->count(),
        ];
    }

    /**
     * جزئیات آیتم‌هایی که از سبد مبدا ترکیب شده‌اند را برمی‌گرداند.
     * نکته: این متد در صورت عدم eager loading آیتم‌ها، کوئری دیتابیس را اجرا خواهد کرد.
     *
     * @return array
     */
    public function getMergedItemsDetails(): array
    {
        $details = [];
        // اطمینان حاصل کنید که رابطه 'items' در مدل Cart تعریف شده است.
        // همچنین، برای جلوگیری از N+1، آیتم‌ها باید eager load شوند (e.g., $fromCart->load('items.product')).
        foreach ($this->fromCart->items as $item) {
            $details[] = [
                'cart_item_id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'product_title' => $item->product->title ?? 'N/A', // فرض بر وجود رابطه 'product' در CartItem
            ];
        }
        return $details;
    }
}
