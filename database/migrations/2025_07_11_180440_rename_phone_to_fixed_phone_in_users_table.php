<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// نام کلاس باید با نام فایل Migration (پس از تاریخ) مطابقت داشته باشد
// مثال: YYYY_MM_DD_HHMMSS_RenamePhoneToFixedPhoneInUsersTable.php -> class RenamePhoneToFixedPhoneInUsersTable
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // اطمینان حاصل کنید که فیلد 'phone' وجود دارد قبل از تغییر نام
            if (Schema::hasColumn('users', 'phone')) {
                $table->renameColumn('phone', 'fixed_phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // اطمینان حاصل کنید که فیلد 'fixed_phone' وجود دارد قبل از بازگرداندن نام
            if (Schema::hasColumn('users', 'fixed_phone')) {
                $table->renameColumn('fixed_phone', 'phone');
            }
        });
    }
};
