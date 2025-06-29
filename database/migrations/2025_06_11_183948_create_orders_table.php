<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * این متد برای ایجاد جدول 'orders' و تعریف ستون‌های آن استفاده می‌شود.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // کلید اصلی (Primary Key) خودکار افزایشی
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // کلید خارجی برای ارتباط با جدول 'users' (اختیاری: برای کاربران مهمان)
            $table->string('session_id')->nullable(); // برای سبد خرید کاربران مهمان
            $table->decimal('total_amount', 10, 2); // مبلغ کل سفارش (10 رقم کلی، 2 رقم اعشار)
            $table->string('status')->default('pending'); // وضعیت سفارش (مثلاً pending, processing, completed, cancelled)

            // فیلدهای جدید برای اطلاعات مشتری
            $table->string('first_name')->nullable(); // نام مشتری
            $table->string('last_name')->nullable();  // نام خانوادگی مشتری
            $table->string('phone_number')->nullable(); // شماره تلفن مشتری

            $table->string('address'); // آدرس کامل ارسال
            $table->string('city'); // شهر
            $table->string('province'); // استان
            $table->string('postal_code'); // کد پستی
            $table->timestamps(); // ستون‌های created_at و updated_at برای زمان‌بندی
        });
    }

    /**
     * Reverse the migrations.
     *
     * این متد برای حذف جدول 'orders' در صورت اجرای 'rollback' استفاده می‌شود.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

