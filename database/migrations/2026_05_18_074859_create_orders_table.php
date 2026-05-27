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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('order_number')->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->unsignedTinyInteger('order_status')->default(0); // 0 = pending, 1 = processing, 3 = shipped, 4 = processed.

            $table->unsignedTinyInteger('payment_method')->nullable(); // 0 = mpesa, 1 = stripe
            $table->unsignedTinyInteger('payment_status')->default(0); // 0 = pending, 1 = paid, 2 cancelled
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->text('notes')->nullable();

            // Snapshots of customer info, delivery, pricing at order time (for when user gets anonymized)
            // customer: name, email, phone
            $table->json('customer_details_snapshot')->nullable();
            // delivery location, delivery area, delivery address, phone
            $table->json('delivery_details_snapshot')->nullable();
            // subtotal, shipping, discount, tax, total
            $table->json('pricing_snapshot')->nullable();
            // method, phone, transaction_id
            $table->json('payment_snapshot')->nullable();

            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'order_status']);
            $table->index('order_number');
            $table->index('shop_id');
            $table->index(['order_status', 'payment_status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
