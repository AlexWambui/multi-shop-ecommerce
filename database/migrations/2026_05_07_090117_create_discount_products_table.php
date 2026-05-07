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
        Schema::create('discount_products', function (Blueprint $table) {
            $table->id();
            $table->boolean('exclude_from_category_discounts')->default(false);
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('product_id')->constraned('products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['discount_id', 'product_id']);

            $table->index(['discount_id', 'product_id']);
            $table->index(['discount_id', 'exclude_from_category_discounts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_products');
    }
};
