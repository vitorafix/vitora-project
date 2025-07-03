        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            /**
             * Run the migrations.
             * این متد برای ایجاد جدول 'legal_infos' و تعریف ستون‌های آن استفاده می‌شود.
             */
            public function up(): void
            {
                Schema::create('legal_infos', function (Blueprint $table) {
                    $table->id(); // کلید اصلی (Primary Key) خودکار افزایشی
                    $table->foreignId('user_id')->constrained()->onDelete('cascade'); // کلید خارجی به جدول users

                    // فیلدهای اطلاعات حقوقی
                    $table->string('company_name'); // نام شرکت (الزامی)
                    $table->string('economic_code')->nullable(); // کد اقتصادی (اختیاری)
                    $table->string('legal_national_id'); // شناسه ملی (الزامی)
                    $table->string('registration_number'); // شماره ثبت (الزامی)
                    $table->string('legal_phone'); // تلفن شرکت (الزامی)
                    $table->string('province'); // استان (الزامی)
                    $table->string('legal_city'); // شهر (الزامی)
                    $table->text('legal_address'); // آدرس کامل (الزامی)
                    $table->string('legal_postal_code'); // کد پستی (الزامی)

                    $table->timestamps(); // ستون‌های created_at و updated_at
                });
            }

            /**
             * Reverse the migrations.
             * این متد برای حذف جدول در صورت اجرای 'rollback' استفاده می‌شود.
             */
            public function down(): void
            {
                Schema::dropIfExists('legal_infos');
            }
        };
        