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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // کلید خارجی به جدول products
            $table->string('name'); // مثلاً "رنگ: قرمز", "سایز: L"
            $table->decimal('price_adjustment', 10, 2)->default(0); // تنظیم قیمت نسبت به محصول اصلی
            $table->integer('stock')->default(0); // موجودی واریانت
            $table->json('attributes')->nullable(); // برای ذخیره ویژگی‌های JSON مانند {'color': 'red', 'size': 'L'}
            $table->timestamps();

            // اضافه کردن unique constraint روی ترکیب product_id و name
            $table->unique(['product_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
