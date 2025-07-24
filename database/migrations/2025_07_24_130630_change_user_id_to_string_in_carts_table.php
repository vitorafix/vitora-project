<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // حذف foreign key (اگر وجود داشته باشد)
        try {
            DB::statement("ALTER TABLE carts DROP FOREIGN KEY carts_user_id_foreign");
        } catch (\Throwable $e) {
            info('Foreign key carts_user_id_foreign not found: ' . $e->getMessage());
        }

        // تغییر نوع ستون user_id به string
        DB::statement("ALTER TABLE carts MODIFY user_id VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // برگرداندن نوع ستون به BIGINT
        DB::statement("ALTER TABLE carts MODIFY user_id BIGINT UNSIGNED NULL");

        // تعریف مجدد foreign key
        Schema::table('carts', function (Blueprint $table) { // استفاده از Blueprint برای متد foreign
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
