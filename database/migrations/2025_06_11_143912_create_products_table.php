<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * این متد برای ایجاد جدول 'products' و تعریف ستون‌های آن استفاده می‌شود.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // کلید اصلی (Primary Key) خودکار افزایشی
            $table->string('title'); // عنوان محصول (رشته)
            $table->text('description')->nullable(); // توضیحات محصول (متن بلند، اختیاری)
            $table->decimal('price', 10, 2); // قیمت محصول (10 رقم کلی، 2 رقم اعشار)
            $table->integer('stock')->default(0); // موجودی انبار (عدد صحیح، پیش‌فرض 0)
            $table->string('image')->nullable(); // مسیر عکس محصول (رشته، اختیاری)
            // کلید خارجی برای ارتباط با جدول 'categories'
            // 'constrained('categories')' یعنی به جدول 'categories' اشاره کند
            // 'onDelete('cascade')' یعنی اگر یک دسته‌بندی حذف شد، محصولات مرتبط با آن نیز حذف شوند
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->timestamps(); // ستون‌های created_at و updated_at برای زمان‌بندی

            // اضافه کردن unique constraint روی ترکیب title و category_id
            $table->unique(['title', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * این متد برای حذف جدول 'products' در صورت اجرای 'rollback' استفاده می‌شود.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

