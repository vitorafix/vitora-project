<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreColumnsToAuditLogsTable extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('failure_reason')->nullable()->after('description');
            $table->string('session_id')->nullable()->after('user_agent');
            $table->integer('attempt_number')->nullable()->after('session_id');
            $table->string('request_source')->nullable()->after('attempt_number');
            $table->json('geo_location')->nullable()->after('request_source');
            $table->boolean('ip_is_blacklisted')->default(false)->after('geo_location');
            $table->text('device_info')->nullable()->after('ip_is_blacklisted');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'failure_reason',
                'session_id',
                'attempt_number',
                'request_source',
                'geo_location',
                'ip_is_blacklisted',
                'device_info',
            ]);
        });
    }
}
