<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('users', 'is_admin')) {
                $table->dropColumn('is_admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // اگر نیاز به بازگرداندن این فیلدها در صورت rollback دارید، اینجا اضافه کنید
            // اما توصیه می‌شود که پس از مهاجرت به Spatie، این فیلدها حذف شوند.
            // $table->string('role')->default('user')->after('username');
            // $table->boolean('is_admin')->default(false)->after('status');
        });
    }
};