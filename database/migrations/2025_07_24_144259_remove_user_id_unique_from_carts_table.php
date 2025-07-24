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
        Schema::table('carts', function (Blueprint $table) {
            // این خط را فقط در صورتی اضافه کنید که UNIQUE constraint روی user_id وجود دارد
            // و می خواهید آن را حذف کنید.
            // اگر قبلا حذف شده، این خط را حذف کنید.
            $table->dropUnique('carts_user_id_unique'); // نام پیش‌فرض لاراول برای unique constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // در متد down، اگر نیاز دارید، می توانید constraint را دوباره اضافه کنید.
            // اما اگر user_id برای مهمانان همیشه 'guest' است، اضافه کردن unique constraint منطقی نیست.
            // $table->unique('user_id'); // این خط را فقط در صورتی اضافه کنید که user_id واقعاً یکتا باشد
        });
    }
};