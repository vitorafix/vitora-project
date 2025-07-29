<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\AnalyticsEvent;
use Illuminate\Validation\Rule; // اضافه کردن Rule برای اعتبارسنجی UUID

class AnalyticsController extends Controller
{
    public function track(Request $request)
    {
        // اعتبارسنجی برای دریافت آرایه‌ای از رویدادها
        $validatedData = $request->validate([
            'events' => 'required|array|max:500', // محدودیت تعداد events در هر batch
            'events.*.guest_uuid' => [
                'required',
                'string',
                // اضافه کردن اعتبارسنجی UUID. این regex فرمت استاندارد UUID v4 را بررسی می‌کند.
                // اگر UUID شما فرمت دیگری دارد، regex را تغییر دهید.
                // این به جلوگیری از Invalid UUID, Path Traversal و XSS در این فیلد کمک می‌کند.
                'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            ],
            'events.*.eventName' => 'required|string|max:255', // محدودیت طول رشته
            'events.*.eventData' => 'nullable|array',
            'events.*.screenData' => 'nullable|array',
            'events.*.trafficSource' => 'nullable|string|max:255',
            'events.*.screenViews' => 'nullable|integer|min:0', // اضافه کردن min:0 برای جلوگیری از مقادیر منفی
            'events.*.screenTime' => 'nullable|integer|min:0', // اضافه کردن min:0 برای جلوگیری از مقادیر منفی
            'events.*.sessionTime' => 'nullable|integer|min:0', // اضافه کردن min:0 برای جلوگیری از مقادیر منفی
            'events.*.currentUrl' => 'required|string|url', // اضافه کردن url برای اعتبارسنجی فرمت URL
            'events.*.pageTitle' => 'required|string|max:500',
            'events.*.scrollDepth' => 'nullable|integer|min:0|max:100',
            'events.*.deviceInfo' => 'nullable|array',
            'events.*.performanceMetrics' => 'nullable|array',
            'events.*.interactionDetails' => 'nullable|array',
            'events.*.searchQuery' => 'nullable|string|max:500',
            'events.*.user_id' => 'nullable|integer|exists:users,id', // اضافه کردن integer
            'events.*.timestamp' => 'required|date',
        ]);

        // بررسی Global Idempotency Key (اختیاری - برای backward compatibility)
        $globalIdempotencyKey = $request->header('X-Idempotency-Key');
        if ($globalIdempotencyKey) {
            $globalCacheKey = 'analytics_global_idempotency:' . $globalIdempotencyKey;
            if (Cache::has($globalCacheKey)) {
                return response()->json([
                    'message' => 'درخواست قبلاً پردازش شده است.',
                    'events_processed' => 0,
                    'total_events_in_batch' => count($validatedData['events']),
                    'duplicates' => count($validatedData['events'])
                ], 200);
            }
        }

        // مرحله 1: بررسی Per-Event Idempotency و آماده‌سازی داده‌ها
        $newEvents = [];
        $duplicateCount = 0;
        
        foreach ($validatedData['events'] as $eventPayload) {
            // تولید کلید منحصر به فرد برای هر event
            $eventKey = $this->generateEventKey($eventPayload);
            $cacheKey = "analytics_event:{$eventKey}";
            
            // بررسی اینکه این event قبلاً پردازش شده یا نه
            if (Cache::has($cacheKey)) {
                $duplicateCount++;
                Log::info('AnalyticsController: Duplicate event detected', [
                    'event_key' => $eventKey,
                    'event_name' => $eventPayload['eventName']
                ]);
                continue;
            }
            
            // آماده‌سازی داده برای bulk insert
            $newEvents[] = [
                'cache_key' => $cacheKey,
                'data' => [
                    'user_id' => $eventPayload['user_id'] ?? null,
                    'guest_uuid' => $eventPayload['guest_uuid'],
                    'event_name' => $eventPayload['eventName'],
                    'event_data' => $eventPayload['eventData'] ?? [],
                    'screen_data' => $eventPayload['screenData'] ?? [],
                    'traffic_source' => $eventPayload['trafficSource'] ?? null, // اطمینان از null بودن در صورت عدم وجود
                    'screen_views' => $eventPayload['screenViews'] ?? 0,
                    'screen_time' => $eventPayload['screenTime'] ?? 0,
                    'session_time' => $eventPayload['sessionTime'] ?? 0,
                    'current_url' => $eventPayload['currentUrl'],
                    'page_title' => $eventPayload['pageTitle'],
                    'scroll_depth' => $eventPayload['scrollDepth'] ?? 0,
                    'device_info' => $eventPayload['deviceInfo'] ?? [],
                    'performance_metrics' => $eventPayload['performanceMetrics'] ?? [],
                    'interaction_details' => $eventPayload['interactionDetails'] ?? [],
                    'search_query' => $eventPayload['searchQuery'] ?? null,
                    'created_at' => Carbon::parse($eventPayload['timestamp']),
                    'updated_at' => now(),
                ]
            ];
        }

        // اگر همه events duplicate هستند
        if (empty($newEvents)) {
            // ست کردن global cache key در صورت وجود
            if ($globalIdempotencyKey) {
                Cache::put($globalCacheKey, true, now()->addHours(2));
            }
            
            return response()->json([
                'message' => 'تمام رویدادها قبلاً پردازش شده‌اند.',
                'events_processed' => 0,
                'total_events_in_batch' => count($validatedData['events']),
                'duplicates' => $duplicateCount
            ], 200);
        }

        // مرحله 2: Bulk Insert با Fallback Strategy
        $insertedCount = 0;
        $failedEvents = [];

        try {
            // آماده‌سازی داده‌ها برای bulk insert
            $bulkData = array_column($newEvents, 'data');
            
            // تلاش برای bulk insert
            $result = AnalyticsEvent::insert($bulkData);
            
            if ($result) {
                $insertedCount = count($bulkData);
                
                // ست کردن cache keys برای events موفق
                foreach ($newEvents as $event) {
                    Cache::put($event['cache_key'], true, now()->addHours(24));
                }
                
                Log::info('AnalyticsController: Bulk insert successful', [
                    'inserted_count' => $insertedCount,
                    'duplicates' => $duplicateCount
                ]);
            }
            
        } catch (\Throwable $e) {
            Log::error('AnalyticsController: Bulk insert failed, using fallback', [
                'error' => $e->getMessage(),
                'events_count' => count($newEvents)
            ]);

            // Fallback: Individual Insert با دقت بیشتر
            foreach ($newEvents as $event) {
                try {
                    $result = AnalyticsEvent::create($event['data']);
                    if ($result) {
                        $insertedCount++;
                        Cache::put($event['cache_key'], true, now()->addHours(24));
                    }
                } catch (\Throwable $individualError) {
                    $failedEvents[] = $event['data'];
                    Log::error('AnalyticsController: Individual event insert failed', [
                        'event_name' => $event['data']['event_name'],
                        'guest_uuid' => $event['data']['guest_uuid'],
                        'error' => $individualError->getMessage()
                    ]);
                }
            }
        }

        // ست کردن global cache key در صورت موفقیت
        if ($globalIdempotencyKey && $insertedCount > 0) {
            Cache::put($globalCacheKey, true, now()->addHours(2));
        }

        // گزارش نهایی
        $responseData = [
            'message' => 'داده‌های تحلیلی دسته‌ای با موفقیت پردازش شد.',
            'events_processed' => $insertedCount,
            'total_events_in_batch' => count($validatedData['events']),
            'duplicates' => $duplicateCount,
            'failed' => count($failedEvents)
        ];

        // اگر events fail شده‌اند، جزئیات بیشتری اضافه کن
        if (!empty($failedEvents)) {
            Log::warning('AnalyticsController: Some events failed to insert', [
                'failed_count' => count($failedEvents),
                'success_rate' => ($insertedCount / count($newEvents)) * 100
            ]);
        }

        return response()->json($responseData, 200);
    }

    /**
     * تولید کلید منحصر به فرد برای هر event بر اساس ویژگی‌های کلیدی
     * این متد همان منطق شما را حفظ می‌کند اما برای تشخیص duplicate دقیق‌تر است
     */
    private function generateEventKey(array $eventPayload): string
    {
        // ترکیب فیلدهایی که یک event را منحصر به فرد می‌کنند
        $keyComponents = [
            $eventPayload['user_id'] ?? $eventPayload['guest_uuid'],
            $eventPayload['eventName'],
            $eventPayload['currentUrl'],
            $eventPayload['timestamp'],
            // hash کردن eventData برای کاهش حجم کلید
            md5(json_encode($eventPayload['eventData'] ?? []))
        ];
        
        $uniqueString = implode('|', $keyComponents);
        return hash('sha256', $uniqueString);
    }
}
