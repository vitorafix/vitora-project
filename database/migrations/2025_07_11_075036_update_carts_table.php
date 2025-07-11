<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCartsTable extends Migration
{
    public function up()
    {
        Schema::table('carts', function (Blueprint $table) {
            // نمونه: اضافه کردن یک ستون جدید
            // $table->string('new_column')->nullable();

            // یا تغییرات دلخواه مثلا حذف ایندکس، اضافه کردن ایندکس، تغییر ستون و ...
            // $table->dropUnique('carts_user_id_unique');
            // $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::table('carts', function (Blueprint $table) {
            // برگرداندن تغییرات در صورت rollback
            // $table->dropColumn('new_column');
            // $table->dropUnique('user_id');
        });
    }
}
