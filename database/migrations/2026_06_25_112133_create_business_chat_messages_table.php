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
        Schema::create('business_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->boolean('is_read')->default(false);

            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('business_chat_messages')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index('created_at');
            $table->index('reply_to_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_chat_messages');
    }
};
