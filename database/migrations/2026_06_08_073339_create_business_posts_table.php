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
        Schema::create('business_posts', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->string('image')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);

            $table->boolean('is_pinned')->default(0);
            $table->timestamp('pinned_at')->nullable();

            $table->timestamp('published_at')->useCurrent();
            $table->timestamp('scheduled_for')->nullable();
            $table->boolean('is_draft')->default(false);

            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->timestamps();

            $table->index('shop_id');
            $table->index('published_at');
            $table->index('likes_count');
            $table->index('comments_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_posts');
    }
};
