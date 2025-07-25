// database/migrations/YYYY_MM_DD_create_analytics_events_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // اگر کاربر لاگین کرده باشد
            $table->string('guest_uuid')->index(); // برای کاربران مهمان
            $table->string('event_name')->index();
            $table->json('event_data')->nullable(); // داده‌های رویداد به صورت JSON
            $table->json('screen_data')->nullable(); // داده‌های صفحه به صورت JSON
            $table->string('traffic_source')->nullable();
            $table->integer('screen_views')->nullable();
            $table->integer('screen_time')->nullable();
            $table->integer('session_time')->nullable();
            $table->string('current_url')->nullable();
            $table->string('page_title')->nullable();

            // New fields for deeper tracking
            $table->integer('scroll_depth')->nullable(); // درصد اسکرول
            $table->json('device_info')->nullable(); // اطلاعات دستگاه
            $table->json('performance_metrics')->nullable(); // معیارهای عملکرد
            $table->json('interaction_details')->nullable(); // جزئیات تعاملات (کلیک‌ها، فوکس، کپی و...)
            $table->string('search_query')->nullable(); // جستجوهای داخلی

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analytics_events');
    }
};
