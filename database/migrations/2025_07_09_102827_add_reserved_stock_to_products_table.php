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
        Schema::table('products', function (Blueprint $table) {
            // اضافه کردن ستون reserved_stock با مقدار پیش‌فرض 0
            // after('stock') به این معنی است که بعد از ستون 'stock' قرار گیرد (اختیاری)
            $table->integer('reserved_stock')->default(0)->after('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // حذف ستون reserved_stock در صورت Rollback
            $table->dropColumn('reserved_stock');
        });
    }
};