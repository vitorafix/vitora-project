<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('name'); // e.g., 'Color', 'Size'
            $table->string('value'); // e.g., 'Red', 'Large'
            $table->decimal('price_adjustment', 10, 2)->default(0.00); // Price difference from base product
            $table->integer('stock')->default(0); // Stock for this specific variant
            $table->timestamps();

            // Add a unique constraint to prevent duplicate variants for the same product
            $table->unique(['product_id', 'name', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variants');
    }
};

