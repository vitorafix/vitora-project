<?php

namespace App\Providers;

use App\Contracts\Services\OtpServiceInterface; // اینترفیس OTP را ایمپورت کنید
use App\Services\OtpService; // کلاس پیاده‌سازی شده OTP را ایمپورت کنید
use App\Contracts\Services\RateLimitServiceInterface; // اینترفیس Rate Limit را ایمپورت کنید
use App\Services\RateLimitService; // کلاس پیاده‌سازی شده Rate Limit را ایمپورت کنید
use App\Contracts\Services\AuditServiceInterface; // اینترفیس Audit را ایمپورت کنید
use App\Services\AuditService; // کلاس پیاده‌سازی شده Audit را ایمپورت کنید
use Illuminate\Support\ServiceProvider;

class OtpServiceProvider extends ServiceProvider
{
    /**
     * ثبت هر سرویس برنامه‌ی کاربردی.
     */
    public function register(): void
    {
        // اینترفیس OtpServiceInterface را به کلاس OtpService "بیند" می‌کنیم.
        // این به لاراول می‌گوید که وقتی OtpServiceInterface درخواست شد،
        // یک نمونه از OtpService را به عنوان پیاده‌سازی آن ارائه دهد.
        $this->app->bind(OtpServiceInterface::class, OtpService::class);

        // اینترفیس RateLimitServiceInterface را به کلاس RateLimitService "بیند" می‌کنیم.
        // این به لاراول می‌گوید که وقتی RateLimitServiceInterface درخواست شد،
        // یک نمونه از RateLimitService را به عنوان پیاده‌سازی آن ارائه دهد.
        $this->app->bind(RateLimitServiceInterface::class, RateLimitService::class);

        // اینترفیس AuditServiceInterface را به کلاس AuditService "بیند" می‌کنیم.
        // این به لاراول می‌گوید که وقتی AuditServiceInterface درخواست شد،
        // یک نمونه از AuditService را به عنوان پیاده‌سازی آن ارائه دهد.
        $this->app->bind(AuditServiceInterface::class, AuditService::class);
    }

    /**
     * بوت‌استرپ هر سرویس برنامه‌ی کاربردی.
     */
    public function boot(): void
    {
        //
    }
}
