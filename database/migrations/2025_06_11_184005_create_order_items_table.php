<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * این متد برای ایجاد جدول 'cart_items' و تعریف ستون‌های آن استفاده می‌شود.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); // کلید اصلی (Primary Key) خودکار افزایشی
            // کلید خارجی برای ارتباط با جدول 'orders'
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            // کلید خارجی برای ارتباط با جدول 'products'
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity'); // تعداد محصول در این آیتم سفارش
            $table->decimal('price', 10, 2); // قیمت محصول در زمان سفارش (مهم برای حفظ قیمت در زمان خرید)
            $table->timestamps(); // ستون‌های created_at و updated_at برای زمان‌بندی
        });
    }

    /**
     * Reverse the migrations.
     *
     * این متد برای حذف جدول 'cart_items' در صورت اجرای 'rollback' استفاده می‌شود.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
