<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // استفاده از Database Transaction برای اطمینان از اتمیک بودن بچ
        DB::transaction(function () {
            foreach ($this->events as $eventPayload) {
                try {
                    // دریافت user_id از payload رویداد
                    $userId = $eventPayload['user_id'] ?? null;

                    DB::table('analytics_events')->insert([
                        'user_id' => $userId,
                        'guest_uuid' => $eventPayload['guest_uuid'],
                        'event_name' => $eventPayload['eventName'],
                        'event_data' => json_encode($eventPayload['eventData'] ?? null),
                        'screen_data' => json_encode($eventPayload['screenData'] ?? null),
                        'traffic_source' => $eventPayload['trafficSource'] ?? null,
                        'screen_views' => $eventPayload['screenViews'] ?? null,
                        'screen_time' => $eventPayload['screenTime'] ?? null,
                        'session_time' => $eventPayload['sessionTime'] ?? null,
                        'current_url' => $eventPayload['currentUrl'] ?? null,
                        'page_title' => $eventPayload['pageTitle'] ?? null,
                        'scroll_depth' => $eventPayload['scrollDepth'] ?? null,
                        'device_info' => json_encode($eventPayload['deviceInfo'] ?? null),
                        'performance_metrics' => json_encode($eventPayload['performanceMetrics'] ?? null),
                        'interaction_details' => json_encode($eventPayload['interactionDetails'] ?? null),
                        'search_query' => $eventPayload['searchQuery'] ?? null,
                        'created_at' => Carbon::parse($eventPayload['timestamp']),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    // لاگ کردن خطاهای درج رویدادهای تکی
                    Log::error('ProcessAnalyticsEvents Job: Failed to insert individual event.', [
                        'event_payload' => $eventPayload,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // نکته: اگر تراکنش فعال باشد، این خطا باعث Rollback کل بچ خواهد شد.
                    // اگر می‌خواهید بچ ادامه یابد و فقط رویداد خراب نادیده گرفته شود،
                    // باید تراکنش را برای هر رویداد جداگانه مدیریت کنید (که کارایی را کاهش می‌دهد)
                    // یا از یک سیستم صف (Queue) استفاده کنید.
                }
            }
        });

        Log::info('ProcessAnalyticsEvents Job: Batch processed successfully.', ['events_count' => count($this->events)]);
    }
}
