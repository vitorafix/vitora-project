<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // اضافه کردن فیلد status به جدول users
            // مقادیر 'active', 'inactive', 'suspended' را می‌پذیرد و پیش‌فرض آن 'active' است.
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('profile_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // حذف فیلد status در صورت rollback
            $table->dropColumn('status');
        });
    }
};
