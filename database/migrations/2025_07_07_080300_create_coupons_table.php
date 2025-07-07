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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percentage']); // fixed amount or percentage discount
            $table->decimal('value', 10, 2); // The discount value (e.g., 10.00 or 0.15)
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('max_discount_amount', 10, 2)->nullable(); // Max limit for percentage discounts
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
        Schema::dropIfExists('coupons');
    }
};

