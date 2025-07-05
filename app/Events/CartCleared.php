<?php

namespace App\Events;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel; // اضافه شده
use Illuminate\Broadcasting\Channel;     // اضافه شده
use Carbon\Carbon;

/**
 * رویداد CartCleared زمانی فعال می‌شود که تمام آیتم‌های یک سبد خرید پاک شوند.
 *
 * @package App\Events
 */
class CartCleared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * تاریخ و زمان پاکسازی سبد خرید.
     *
     * @var string
     */
    public readonly string $clearedAt;

    /**
     * سازنده کلاس.
     *
     * @param \App\Models\Cart $cart سبد خریدی که پاک شده است.
     * @param \App\Models\User|null $user کاربر مرتبط با عملیات (اختیاری).
     */
    public function __construct(
        public readonly Cart $cart,
        public readonly ?User $user = null // استفاده از readonly و Property Promotion برای user
    ) {
        $this->clearedAt = Carbon::now()->toDateTimeString();
    }

    /**
     * کانال‌هایی که رویداد باید روی آن‌ها برودکست شود.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->user) {
            $channels[] = new PrivateChannel('user.' . $this->user->id); // کانال خصوصی برای کاربر لاگین شده
        }

        // کانال عمومی برای آمار فروشگاه
        $channels[] = new Channel('cart-statistics');

        return $channels;
    }

    /**
     * نام رویداد برای Broadcasting.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'cart.cleared';
    }

    /**
     * داده‌های ارسالی در Broadcasting.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'message' => 'Your cart has been cleared.',
            'cart' => [
                'id' => $this->cart->id,
                'total_items' => $this->cart->items()->count(),
                'total_amount' => $this->cart->total_amount ?? 0, // فرض بر وجود total_amount در مدل Cart
            ],
            'user' => $this->user?->only(['id', 'name', 'email']), // ارسال اطلاعات منتخب کاربر
            'cleared_at' => $this->clearedAt,
        ];
    }

    /**
     * اطلاعات خلاصه عملیات پاکسازی سبد خرید را برای لاگ‌گیری و تحلیل برمی‌گرداند.
     *
     * @return array
     */
    public function getClearedSummary(): array
    {
        return [
            'cart_id' => $this->cart->id,
            'user_id' => $this->user?->id,
            'items_count' => $this->cart->items()->count(),
            'total_amount' => $this->cart->total_amount ?? 0, // فرض بر وجود total_amount در مدل Cart
            'cleared_at' => $this->clearedAt,
        ];
    }

    /**
     * بررسی اینکه آیا کاربر مهمان است یا نه.
     *
     * @return bool
     */
    public function isGuestUser(): bool
    {
        return is_null($this->user);
    }
}
