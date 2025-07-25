<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // برای لاگ کردن داده‌ها
use Illuminate\Support\Facades\DB; // برای ذخیره در دیتابیس
use Illuminate\Support\Str; // برای تولید UUID در صورت نیاز (گرچه در JS تولید می‌شود)
use Carbon\Carbon; // NEW: اضافه کردن Carbon برای تبدیل timestamp
use Illuminate\Support\Facades\Cache; // NEW: اضافه کردن Cache facade برای Idempotency
use App\Models\User; // NEW: اضافه کردن User model برای اعتبارسنجی exists

class AnalyticsController extends Controller
{
    public function track(Request $request)
    {
        // NEW: دریافت Idempotency Key از هدر درخواست
        $idempotencyKey = $request->header('X-Idempotency-Key');

        // NEW: بررسی Idempotency Key برای جلوگیری از پردازش‌های تکراری
        if ($idempotencyKey) {
            $cacheKey = 'analytics_idempotency:' . $idempotencyKey;
            // اگر این کلید قبلاً پردازش شده باشد، پاسخ موفقیت‌آمیز را برمی‌گردانیم
            if (Cache::has($cacheKey)) {
                // Log::info('AnalyticsController: Duplicate request detected via Idempotency Key. Skipping processing.', ['idempotency_key' => $idempotencyKey]); // Removed logging
                return response()->json(['message' => 'درخواست قبلاً پردازش شده است.'], 200);
            }
            // کلید را در کش ذخیره می‌کنیم تا از پردازش‌های تکراری در آینده جلوگیری شود (مثلاً برای 5 دقیقه)
            Cache::put($cacheKey, true, now()->addMinutes(5));
        }

        // اعتبارسنجی برای دریافت آرایه‌ای از رویدادها
        $validatedData = $request->validate([
            'events' => 'required|array', // انتظار دریافت آرایه‌ای از رویدادها
            'events.*.guest_uuid' => 'required|string',
            'events.*.eventName' => 'required|string',
            'events.*.eventData' => 'nullable|array',
            'events.*.screenData' => 'nullable|array',
            'events.*.trafficSource' => 'nullable|string',
            'events.*.screenViews' => 'nullable|integer',
            'events.*.screenTime' => 'nullable|integer',
            'events.*.sessionTime' => 'nullable|integer',
            'events.*.currentUrl' => 'required|string',
            'events.*.pageTitle' => 'required|string',
            'events.*.scrollDepth' => 'nullable|integer|min:0|max:100',
            'events.*.deviceInfo' => 'nullable|array',
            'events.*.performanceMetrics' => 'nullable|array',
            'events.*.interactionDetails' => 'nullable|array',
            'events.*.searchQuery' => 'nullable|string',
            'events.*.user_id' => 'nullable|exists:users,id', // NEW: user_id می‌تواند null باشد یا باید در جدول users وجود داشته باشد
            'events.*.timestamp' => 'required|date', // NEW: timestamp برای ثبت دقیق زمان رویداد
        ]);

        $insertedCount = 0;

        // استفاده از تراکنش دیتابیس برای درج دسته‌ای
        DB::transaction(function () use ($validatedData, &$insertedCount) {
            foreach ($validatedData['events'] as $eventPayload) {
                try {
                    // دریافت user_id از payload رویداد (می‌تواند null باشد)
                    $userId = $eventPayload['user_id'] ?? null;

                    DB::table('analytics_events')->insert([
                        'user_id' => $userId,
                        'guest_uuid' => $eventPayload['guest_uuid'],
                        'event_name' => $eventPayload['eventName'],
                        'event_data' => json_encode($eventPayload['eventData']), // داده‌های رویداد به صورت JSON
                        'screen_data' => json_encode($eventPayload['screenData']),
                        'traffic_source' => $eventPayload['trafficSource'],
                        'screen_views' => $eventPayload['screenViews'],
                        'screen_time' => $eventPayload['screenTime'],
                        'session_time' => $eventPayload['sessionTime'],
                        'current_url' => $eventPayload['currentUrl'],
                        'page_title' => $eventPayload['pageTitle'],

                        // New fields
                        'scroll_depth' => $eventPayload['scrollDepth'] ?? null,
                        'device_info' => json_encode($eventPayload['deviceInfo'] ?? null),
                        'performance_metrics' => json_encode($eventPayload['performanceMetrics'] ?? null),
                        'interaction_details' => json_encode($eventPayload['interactionDetails'] ?? null),
                        'search_query' => $eventPayload['searchQuery'] ?? null,

                        'created_at' => Carbon::parse($eventPayload['timestamp']), // استفاده از timestamp ارسالی از کلاینت
                        'updated_at' => now(), // یا Carbon::parse($eventPayload['timestamp']) اگر updated_at هم از کلاینت می‌آید
                    ]);
                    $insertedCount++;
                } catch (\Throwable $e) {
                    // Log::error کردن خطاهای درج رویدادهای تکی (حتی در صورت Rollback کلی تراکنش)
                    Log::error('AnalyticsController: Failed to insert individual event within batch.', [
                        'event_payload' => $eventPayload,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // ادامه پردازش سایر رویدادها حتی در صورت بروز خطا در یک رویداد
                    // نکته: اگر تراکنش فعال باشد، این خطا باعث Rollback کل بچ خواهد شد.
                    // اگر می‌خواهید بچ ادامه یابد و فقط رویداد خراب نادیده گرفته شود،
                    // باید تراکنش را برای هر رویداد جداگانه مدیریت کنید (که کارایی را کاهش می‌دهد)
                    // یا از یک سیستم صف (Queue) استفاده کنید.
                }
            }
        });

        // Log::info('Analytics batch received and processed', ['events_received' => count($validatedData['events']), 'events_inserted' => $insertedCount, 'idempotency_key' => $idempotencyKey]); // Removed logging

        return response()->json([
            'message' => "داده‌های تحلیلی دسته‌ای با موفقیت پردازش شد.",
            'events_processed' => $insertedCount,
            'total_events_in_batch' => count($validatedData['events'])
        ], 200);
    }
}
