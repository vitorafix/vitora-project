<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * این متد برای ایجاد جدول 'users' و تعریف ستون‌های آن استفاده می‌شود.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // کلید اصلی (Primary Key) خودکار افزایشی
            $table->string('name')->nullable(); // نام (اختیاری در زمان ثبت‌نام اولیه)
            $table->string('lastname')->nullable(); // نام خانوادگی (اختیاری در زمان ثبت‌نام اولیه)

            // شماره موبایل: یکتا و اجباری برای ورود با OTP
            // اگر کاربر فقط با موبایل وارد می‌شود، این فیلد نباید null باشد.
            $table->string('mobile_number')->unique();

            $table->string('email')->nullable()->unique(); // ایمیل (یکتا و اختیاری)
            $table->timestamp('email_verified_at')->nullable();
            // $table->string('password'); // حذف شد: احراز هویت با OTP است
            $table->rememberToken();
            
            // وضعیت تکمیل پروفایل (پیش‌فرض: false)
            // این فیلد نشان می‌دهد که آیا کاربر اطلاعات تکمیلی پروفایل خود را وارد کرده است یا خیر.
            $table->boolean('profile_completed')->default(false); 

            $table->timestamps(); // ستون‌های created_at و updated_at
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     * این متد برای حذف جداول در صورت اجرای 'rollback' استفاده می‌شود.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

