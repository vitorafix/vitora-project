<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToAuditLogsTable extends Migration
{
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('request_source');
            $table->string('model_type')->nullable()->after('mobile_hash');
            $table->unsignedBigInteger('model_id')->nullable()->after('model_type');
            $table->string('level')->default('info')->after('model_id');
        });
    }

    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['metadata', 'model_type', 'model_id', 'level']);
        });
    }
}
