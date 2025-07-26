<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model; // مهم: از این مدل ارث‌بری کنید

class AnalyticsEvent extends Model
{
    // اسم کالکشن در MongoDB. اگر این رو مشخص نکنید، لاراول از اسم جمع مدل (analytics_events) استفاده می‌کنه.
    protected $collection = 'analytics_events';

    // نام اتصال دیتابیس MongoDB که در config/database.php تعریف کردید
    protected $connection = 'mongodb';

    // فیلدهایی که قابل تخصیص انبوه (mass assignable) هستند
    protected $fillable = [
        'user_id',
        'guest_uuid',
        'event_name',
        'event_data',
        'screen_data',
        'traffic_source',
        'screen_views',
        'screen_time',
        'session_time',
        'current_url',
        'page_title',
        'scroll_depth',
        'device_info',
        'performance_metrics',
        'interaction_details',
        'search_query',
        // created_at و updated_at به صورت خودکار توسط Eloquent هندل می‌شوند
    ];

    // اگر نمیخواهید timestamps (created_at, updated_at) به صورت خودکار مدیریت شوند، این را false کنید.
    public $timestamps = true;
}