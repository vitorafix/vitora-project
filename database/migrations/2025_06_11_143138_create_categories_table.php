<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * این متد برای ایجاد جدول 'categories' و تعریف ستون‌های آن استفاده می‌شود.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); // کلید اصلی (Primary Key) خودکار افزایشی
            $table->string('name')->unique(); // نام دسته‌بندی (یکتا و رشته)
            $table->string('slug')->unique(); // اسلاگ برای URLهای دوستانه (یکتا و رشته)
            $table->text('description')->nullable(); // توضیحات دسته‌بندی (متن بلند، اختیاری)
            $table->timestamps(); // ستون‌های created_at و updated_at برای زمان‌بندی
        });
    }

    /**
     * Reverse the migrations.
     *
     * این متد برای حذف جدول 'categories' در صورت اجرای 'rollback' استفاده می‌شود.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

