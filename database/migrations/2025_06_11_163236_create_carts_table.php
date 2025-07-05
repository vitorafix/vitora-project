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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            // کلید خارجی برای ارتباط با جدول 'users' (اگر کاربر احراز هویت شده باشد)
            // nullable() یعنی سبد خرید می‌تواند برای کاربر مهمان هم باشد.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id')->nullable()->unique(); // برای سبد خرید مهمان
            $table->timestamps();

            // اضافه شدن: یک کاربر فقط یک سبد خرید می‌تواند داشته باشد
            // این تضمین می‌کند که برای هر user_id، فقط یک ردیف منحصر به فرد در جدول carts وجود داشته باشد.
            $table->unique(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
