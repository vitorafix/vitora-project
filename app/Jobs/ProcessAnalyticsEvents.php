<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Facades\DB; // 🔴 حذف: دیگر نیازی به DB facade برای این عملیات نیست
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\AnalyticsEvent; // 🟢 جدید: ایمپورت مدل AnalyticsEvent

class ProcessAnalyticsEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $events;

    /**
     * Create a new job instance.
     *
     * @param array $events An array of analytics event payloads.
     * @return void
     */
    public function __construct(array $events)
    {
        $this->events = $events;
        // 🟢 جدید: لاگ کردن تعداد رویدادهای دریافتی در زمان ساخت Job
        Log::info('ProcessAnalyticsEvents Job: Initialized with ' . count($events) . ' events.');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 🔴 حذف: استفاده از Database Transaction برای MongoDB به این شکل لازم نیست
        // DB::transaction(function () {
            // 🟢 جدید: لاگ کردن شروع پردازش Job
            Log::info('ProcessAnalyticsEvents Job: Starting handle method.');

            foreach ($this->events as $index => $eventPayload) {
                try {
                    // 🟢 جدید: لاگ کردن هر رویداد قبل از درج
                    Log::info('ProcessAnalyticsEvents Job: Processing event ' . ($index + 1) . ' - EventName: ' . ($eventPayload['eventName'] ?? 'N/A') . ', GuestUUID: ' . ($eventPayload['guest_uuid'] ?? 'N/A'));

                    // دریافت user_id از payload رویداد
                    $userId = $eventPayload['user_id'] ?? null;

                    // 🟢 تغییر اصلی: استفاده از مدل AnalyticsEvent برای ذخیره در MongoDB
                    AnalyticsEvent::create([
                        'user_id' => $userId,
                        'guest_uuid' => $eventPayload['guest_uuid'],
                        'event_name' => $eventPayload['eventName'],
                        'event_data' => $eventPayload['eventData'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'screen_data' => $eventPayload['screenData'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'traffic_source' => $eventPayload['trafficSource'] ?? null,
                        'screen_views' => $eventPayload['screenViews'] ?? 0, // 🟢 تغییر: مقدار پیش‌فرض 0 برای integer
                        'screen_time' => $eventPayload['screenTime'] ?? 0,   // 🟢 تغییر: مقدار پیش‌فرض 0 برای integer
                        'session_time' => $eventPayload['sessionTime'] ?? 0, // 🟢 تغییر: مقدار پیش‌فرض 0 برای integer
                        'current_url' => $eventPayload['currentUrl'] ?? null,
                        'page_title' => $eventPayload['pageTitle'] ?? null,
                        'scroll_depth' => $eventPayload['scrollDepth'] ?? 0, // 🟢 تغییر: مقدار پیش‌فرض 0 برای integer
                        'device_info' => $eventPayload['deviceInfo'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'performance_metrics' => $eventPayload['performanceMetrics'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'interaction_details' => $eventPayload['interactionDetails'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'search_query' => $eventPayload['searchQuery'] ?? null,
                        'created_at' => Carbon::parse($eventPayload['timestamp']),
                        'updated_at' => now(),
                    ]);
                    // 🟢 جدید: لاگ کردن موفقیت آمیز بودن درج
                    Log::info('ProcessAnalyticsEvents Job: Successfully inserted event ' . ($index + 1) . '.');

                } catch (\Throwable $e) {
                    // لاگ کردن خطاهای درج رویدادهای تکی
                    Log::error('ProcessAnalyticsEvents Job: Failed to insert individual event.', [
                        'event_payload' => $eventPayload,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        // }); // 🔴 حذف: پایان تراکنش DB
        // Log::info('ProcessAnalyticsEvents Job: Batch processed successfully.', ['events_count' => count($this->events)]); // 🔴 حذف: لاگینگ به دلیل تکرار زیاد و حجم بالا
        // 🟢 جدید: لاگ کردن اتمام پردازش Job
        Log::info('ProcessAnalyticsEvents Job: Finished processing all ' . count($this->events) . ' events.');
    }
}
