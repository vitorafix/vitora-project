<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login; // اضافه شدن: برای گوش دادن به رویداد لاگین
use App\Listeners\AssignGuestCartToUserListener; // اضافه شدن: Listener جدید

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * این آرایه نگاشت رویدادها به شنوندگان مربوطه را تعریف می‌کند.
     * هر کلید نام کلاس یک رویداد و مقدار آن آرایه‌ای از کلاس‌های شنونده است که باید به آن رویداد پاسخ دهند.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ثبت AssignGuestCartToUserListener برای رویداد Login
        Login::class => [
            AssignGuestCartToUserListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * این متد برای ثبت هرگونه رویداد خاص برنامه شما استفاده می‌شود.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}

