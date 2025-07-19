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
        Schema::table('carts', function (Blueprint $table) {
            // افزودن ستون guest_uuid
            // این ستون برای شناسایی سبدهای خرید مهمان استفاده می‌شود.
            // nullable() برای سازگاری با سبدهای خرید موجود که guest_uuid ندارند.
            // unique() برای اطمینان از یکتا بودن هر guest_uuid.
            // index() برای بهبود عملکرد جستجو بر اساس guest_uuid.
            $table->uuid('guest_uuid')->nullable()->unique()->after('session_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('carts', function (Blueprint $table) {
            // حذف ستون guest_uuid در صورت بازگرداندن Migration
            $table->dropColumn('guest_uuid');
        });
    }
};
