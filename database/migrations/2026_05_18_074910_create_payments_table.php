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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('transaction_id')->unique(); // From payment gateway
            $table->decimal('amount', 12, 2);
            $table->unsignedTinyInteger('payment_status')->default(0); // ENUM: 0 = pending, 1 = paid, 2 = cancelled
            $table->unsignedTinyInteger('payment_method')->nullable(); // ENUM: 0 = mpesa, 1 = stripe
            $table->json('gateway_response')->nullable(); // Store webhook data
            $table->string('failure_reason')->nullable();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->timestamps();

            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
