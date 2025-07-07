<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToCartsTable extends Migration
{
    public function up()
    {
        Schema::table('carts', function (Blueprint $table) {
            if (!Schema::hasColumn('carts', 'coupon_id')) {
                $table->unsignedBigInteger('coupon_id')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('carts', function (Blueprint $table) {
            if (Schema::hasColumn('carts', 'coupon_id')) {
                $table->dropColumn('coupon_id');
            }
        });
    }
}
