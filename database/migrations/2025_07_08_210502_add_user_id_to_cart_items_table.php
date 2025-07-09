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
        Schema::table('cart_items', function (Blueprint $table) {
            // اضافه کردن ستون user_id
            // این ستون nullable است زیرا سبد خرید می‌تواند برای مهمانان (بدون user_id) نیز باشد.
            // اگر همیشه انتظار دارید user_id وجود داشته باشد، nullable(false) را حذف کنید.
            $table->foreignId('user_id')
                  ->nullable() // اجازه null برای کاربران مهمان
                  ->after('product_variant_id') // قرار دادن بعد از product_variant_id
                  ->constrained('users') // ایجاد کلید خارجی به جدول users
                  ->onDelete('cascade'); // اگر کاربر حذف شد، آیتم‌های سبد خرید او نیز حذف شوند
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // حذف کلید خارجی قبل از حذف ستون
            $table->dropConstrainedForeignId('user_id');
            // حذف ستون user_id
            $table->dropColumn('user_id');
        });
    }
};
