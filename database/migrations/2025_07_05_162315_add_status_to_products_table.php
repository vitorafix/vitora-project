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
            // اضافه کردن فیلد status به جدول products
            // مقادیر 'active', 'inactive' را می‌پذیرد و پیش‌فرض آن 'active' است.
            $table->enum('status', ['active', 'inactive'])->default('active')->after('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // حذف فیلد status در صورت rollback
            $table->dropColumn('status');
        });
    }
};
