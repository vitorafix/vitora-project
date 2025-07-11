<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// نام کلاس باید با نام فایل Migration (پس از تاریخ) مطابقت داشته باشد
// مثال: YYYY_MM_DD_HHMMSS_RenamePhoneNumberToFixedPhoneInAddressesTable.php -> class RenamePhoneNumberToFixedPhoneInAddressesTable
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'phone_number')) {
                $table->renameColumn('phone_number', 'fixed_phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'fixed_phone')) {
                $table->renameColumn('fixed_phone', 'phone_number');
            }
        });
    }
};
