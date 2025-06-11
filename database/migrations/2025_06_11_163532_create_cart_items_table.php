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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            // کلید خارجی برای ارتباط با جدول 'carts'
            $table->foreignId('cart_id')->constrained('carts')->onDelete('cascade');
            // کلید خارجی برای ارتباط با جدول 'products'
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->default(1); // تعداد محصول در سبد خرید
            $table->decimal('price', 10, 2); // قیمت محصول در زمان اضافه شدن به سبد خرید
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};

