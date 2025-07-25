<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Make sure this is imported

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            // افزودن ایندکس‌ها برای بهینه‌سازی عملکرد کوئری‌ها
            // قبل از افزودن ایندکس‌ها، بررسی می‌کنیم که آیا ایندکس از قبل وجود دارد یا خیر.
            // این کار از خطای "Duplicate key name" جلوگیری می‌کند.

            // Helper function to check if an index exists using raw SQL
            // این کوئری برای MySQL طراحی شده است.
            $hasIndex = function ($tableName, $indexName) {
                $sql = "SELECT COUNT(1) AS indexExists FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?";
                $result = DB::select($sql, [$tableName, $indexName]);
                return $result[0]->indexExists > 0;
            };

            $tableName = 'analytics_events';

            // Index for quick lookup of all activities by a specific guest
            $indexName = 'analytics_events_guest_uuid_index';
            if (!$hasIndex($tableName, $indexName)) {
                $table->index('guest_uuid', $indexName);
            }

            // Index for filtering events by their name (e.g., 'page_load_time', 'add_to_cart')
            $indexName = 'analytics_events_event_name_index';
            if (!$hasIndex($tableName, $indexName)) {
                $table->index('event_name', $indexName);
            }

            // Index for time-based filtering (e.g., events from today, last week)
            $indexName = 'analytics_events_created_at_index';
            if (!$hasIndex($tableName, $indexName)) {
                $table->index('created_at', $indexName);
            }

            // Index for filtering events related to a specific authenticated user
            $indexName = 'analytics_events_user_id_index';
            if (!$hasIndex($tableName, $indexName)) {
                $table->index('user_id', $indexName);
            }

            // Index for filtering events by URL (useful for page-specific analytics)
            $indexName = 'analytics_events_current_url_index';
            if (!$hasIndex($tableName, $indexName)) {
                $table->index('current_url', $indexName);
            }

            // Composite index for common queries involving guest activity over time
            // Laravel generates a name like 'table_columns_index' for composite indexes
            $compositeIndexName = 'analytics_events_guest_uuid_created_at_index';
            if (!$hasIndex($tableName, $compositeIndexName)) {
                $table->index(['guest_uuid', 'created_at'], $compositeIndexName);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('analytics_events', function (Blueprint $table) {
            // Helper function to check if an index exists (re-defined for down method scope)
            $hasIndex = function ($tableName, $indexName) {
                $sql = "SELECT COUNT(1) AS indexExists FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?";
                $result = DB::select($sql, [$tableName, $indexName]);
                return $result[0]->indexExists > 0;
            };

            $tableName = 'analytics_events';

            $indexNames = [
                'analytics_events_guest_uuid_index',
                'analytics_events_event_name_index',
                'analytics_events_created_at_index',
                'analytics_events_user_id_index',
                'analytics_events_current_url_index',
                'analytics_events_guest_uuid_created_at_index'
            ];

            foreach ($indexNames as $indexName) {
                if ($hasIndex($tableName, $indexName)) {
                    $table->dropIndex($indexName);
                }
            }
        });
    }
};
