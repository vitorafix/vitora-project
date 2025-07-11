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
        Schema::table('addresses', function (Blueprint $table) {
            // اضافه کردن فیلدهای نام و نام خانوادگی تحویل گیرنده
            // این فیلدها را بعد از 'fixed_phone' اضافه می‌کنیم تا ترتیب منطقی داشته باشند.
            // اگر 'fixed_phone' nullable است، اینها هم می‌توانند nullable باشند یا بر اساس نیاز شما.
            // فرض می‌کنیم که اینها اجباری هستند، پس nullable نیستند.
            $table->string('recipient_first_name')->after('fixed_phone');
            $table->string('recipient_last_name')->after('recipient_first_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // حذف فیلدها در صورت rollback
            $table->dropColumn(['recipient_first_name', 'recipient_last_name']);
        });
    }
};
