<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Services\Contracts\CartServiceInterface; // اطمینان حاصل کنید که این namespace صحیح است
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request; // برای دسترسی به کوکی
use Illuminate\Support\Facades\Cookie; // اضافه شده برای استفاده از فیساد Cookie

class AssignGuestCartToUserListener
{
    protected CartServiceInterface $cartService;
    protected Request $request;

    /**
     * Create the event listener.
     *
     * این سازنده سرویس سبد خرید و شیء درخواست HTTP را تزریق می‌کند.
     *
     * @param CartServiceInterface $cartService سرویس مدیریت عملیات سبد خرید
     * @param Request $request شیء درخواست HTTP برای دسترسی به کوکی‌ها
     * @return void
     */
    public function __construct(CartServiceInterface $cartService, Request $request)
    {
        $this->cartService = $cartService;
        $this->request = $request;
    }

    /**
     * Handle the event.
     *
     * این متد هنگام رویداد لاگین کاربر فراخوانی می‌شود.
     * وظیفه آن بررسی وجود سبد خرید مهمان (بر اساس guest_uuid در کوکی) و ادغام آن با سبد خرید کاربر لاگین شده است.
     *
     * @param  \Illuminate\Auth\Events\Login  $event رویداد لاگین که شامل اطلاعات کاربر است.
     * @return void
     */
    public function handle(Login $event): void
    {
        $user = $event->user; // کاربر لاگین شده
        $guestUuid = $this->request->cookie('guest_uuid'); // دریافت guest_uuid از کوکی درخواست
        $sessionId = session()->getId(); // دریافت session_id فعلی

        // ثبت اطلاعات لاگ برای ردیابی فرآیند
        Log::info('Login event triggered for user.', [
            'user_id' => $user->id,
            'guest_uuid_from_cookie' => $guestUuid ?? 'NULL', // نمایش guest_uuid (اگر وجود داشته باشد)
            'session_id' => $sessionId, // نمایش session_id
        ]);

        // اگر guest_uuid از کوکی دریافت شد، سعی می‌کنیم سبد خرید مهمان را به کاربر متصل کنیم.
        // متد assignGuestCartToUser در CartService.php قبلاً منطق ادغام را به خوبی مدیریت می‌کند.
        if ($guestUuid) {
            try {
                // فراخوانی سرویس برای اختصاص یا ادغام سبد خرید مهمان به کاربر
                $response = $this->cartService->assignGuestCartToUser($user, $guestUuid);

                if ($response->isSuccess()) {
                    // ثبت موفقیت‌آمیز بودن عملیات
                    Log::info('Guest cart successfully assigned/merged to user upon login.', [
                        'user_id' => $user->id,
                        'guest_uuid' => $guestUuid,
                        'message' => $response->getMessage()
                    ]);

                    // پس از ادغام موفقیت‌آمیز، کوکی guest_uuid را حذف می‌کنیم تا برای کاربر لاگین شده استفاده نشود.
                    // مهم: پارامترهای path، domain و secure باید با زمان تنظیم کوکی در GuestUuidMiddleware مطابقت داشته باشند.
                    // secure بودن کوکی نیز باید بر اساس محیط (production/local) تعیین شود.
                    $secure = app()->environment('production');
                    Cookie::queue(Cookie::forget('guest_uuid', '/', null, $secure));
                } else {
                    // ثبت هشدار در صورت عدم موفقیت عملیات
                    Log::warning('Failed to assign/merge guest cart to user upon login.', [
                        'user_id' => $user->id,
                        'guest_uuid' => $guestUuid,
                        'error_message' => $response->getMessage()
                    ]);
                }
            } catch (\Throwable $e) {
                // ثبت خطا در صورت بروز استثنا در طول عملیات
                Log::error('Error during guest cart assignment/merge on login.', [
                    'user_id' => $user->id,
                    'guest_uuid' => $guestUuid,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            // ثبت اطلاعات در صورت عدم یافتن guest_uuid در کوکی
            Log::info('No guest_uuid found in cookie for user login, skipping cart merge.', ['user_id' => $user->id]);
        }
    }
}

