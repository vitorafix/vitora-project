<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // برای لاگ کردن داده‌ها
use Illuminate\Support\Str; // برای تولید UUID در صورت نیاز (گرچه در JS تولید می‌شود)
use Carbon\Carbon; // برای تبدیل timestamp
use Illuminate\Support\Facades\Cache; // برای Idempotency
use App\Models\User; // برای اعتبارسنجی exists
use App\Models\AnalyticsEvent; // 🟢 جدید: ایمپورت مدل AnalyticsEvent برای MongoDB

class AnalyticsController extends Controller
{
    public function track(Request $request)
    {
        // دریافت Idempotency Key از هدر درخواست
        $idempotencyKey = $request->header('X-Idempotency-Key');

        // بررسی Idempotency Key برای جلوگیری از پردازش‌های تکراری
        if ($idempotencyKey) {
            $cacheKey = 'analytics_idempotency:' . $idempotencyKey;
            // اگر این کلید قبلاً پردازش شده باشد، پاسخ موفقیت‌آمیز را برمی‌گردانیم
            if (Cache::has($cacheKey)) {
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
            'events.*.eventData' => 'nullable|array', // 🟢 تغییر: باید آرایه باشد، نه JSON string
            'events.*.screenData' => 'nullable|array', // 🟢 تغییر: باید آرایه باشد، نه JSON string
            'events.*.trafficSource' => 'nullable|string',
            'events.*.screenViews' => 'nullable|integer',
            'events.*.screenTime' => 'nullable|integer',
            'events.*.sessionTime' => 'nullable|integer',
            'events.*.currentUrl' => 'required|string',
            'events.*.pageTitle' => 'required|string',
            'events.*.scrollDepth' => 'nullable|integer|min:0|max:100',
            'events.*.deviceInfo' => 'nullable|array', // 🟢 تغییر: باید آرایه باشد، نه JSON string
            'events.*.performanceMetrics' => 'nullable|array', // 🟢 تغییر: باید آرایه باشد، نه JSON string
            'events.*.interactionDetails' => 'nullable|array', // 🟢 تغییر: باید آرایه باشد، نه JSON string
            'events.*.searchQuery' => 'nullable|string',
            'events.*.user_id' => 'nullable|exists:users,id',
            'events.*.timestamp' => 'required|date',
        ]);

        $insertedCount = 0;

        // 🔴 حذف: تراکنش DB برای MongoDB به این شکل لازم نیست، Eloquent خودش مدیریت می‌کند
        // DB::transaction(function () use ($validatedData, &$insertedCount) {
            foreach ($validatedData['events'] as $eventPayload) {
                try {
                    // دریافت user_id از payload رویداد (می‌تواند null باشد)
                    $userId = $eventPayload['user_id'] ?? null;

                    // 🟢 تغییر اصلی: استفاده از مدل AnalyticsEvent برای ذخیره در MongoDB
                    AnalyticsEvent::create([
                        'user_id' => $userId,
                        'guest_uuid' => $eventPayload['guest_uuid'],
                        'event_name' => $eventPayload['eventName'],
                        'event_data' => $eventPayload['eventData'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'screen_data' => $eventPayload['screenData'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'traffic_source' => $eventPayload['trafficSource'],
                        'screen_views' => $eventPayload['screenViews'],
                        'screen_time' => $eventPayload['screenTime'],
                        'session_time' => $eventPayload['sessionTime'],
                        'current_url' => $eventPayload['currentUrl'],
                        'page_title' => $eventPayload['pageTitle'],

                        // فیلدهای جدید
                        'scroll_depth' => $eventPayload['scrollDepth'] ?? 0, // 🟢 تغییر: پیش‌فرض 0 برای integer
                        'device_info' => $eventPayload['deviceInfo'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'performance_metrics' => $eventPayload['performanceMetrics'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'interaction_details' => $eventPayload['interactionDetails'] ?? [], // 🟢 تغییر: مستقیماً آرایه را ذخیره کنید
                        'search_query' => $eventPayload['searchQuery'] ?? null,

                        'created_at' => Carbon::parse($eventPayload['timestamp']), // استفاده از timestamp ارسالی از کلاینت
                        'updated_at' => now(), // یا Carbon::parse($eventPayload['timestamp']) اگر updated_at هم از کلاینت می‌آید
                    ]);
                    $insertedCount++;
                } catch (\Throwable $e) {
                    // لاگ کردن خطاهای درج رویدادهای تکی
                    Log::error('AnalyticsController: Failed to insert individual event within batch.', [
                        'event_payload' => $eventPayload,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        // }); // 🔴 حذف: پایان تراکنش DB

        return response()->json([
            'message' => "داده‌های تحلیلی دسته‌ای با موفقیت پردازش شد.",
            'events_processed' => $insertedCount,
            'total_events_in_batch' => count($validatedData['events'])
        ], 200);
    }
}
