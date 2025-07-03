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
            Schema::table('users', function (Blueprint $table) {
                // اضافه کردن فیلدهای جدید به جدول users
                // این فیلدها پس از mobile_number قرار می‌گیرند
                $table->string('national_id')->nullable()->unique()->after('mobile_number');
                $table->string('birth_date')->nullable()->after('national_id'); // برای تاریخ شمسی بهتر است string باشد
                $table->string('phone')->nullable()->after('email'); // بعد از ایمیل قرار می‌گیرد
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('users', function (Blueprint $table) {
                // حذف فیلدها در صورت rollback
                $table->dropColumn(['national_id', 'birth_date', 'phone']);
            });
        }
    };
    