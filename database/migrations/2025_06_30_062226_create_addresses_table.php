<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // کلید خارجی به جدول users
            $table->string('title')->nullable(); // عنوان آدرس (مثلاً: خانه، محل کار)
            $table->string('province'); // استان
            $table->string('city');     // شهر
            $table->string('address');  // آدرس دقیق
            $table->string('postal_code')->nullable(); // کد پستی
            $table->string('phone_number')->nullable(); // شماره تلفن (اختیاری، اگر آدرس مربوط به شخص دیگری باشد)
            $table->boolean('is_default')->default(false); // آیا این آدرس پیش‌فرض است؟
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addresses');
    }
};

