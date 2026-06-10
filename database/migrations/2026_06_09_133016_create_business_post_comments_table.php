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
        Schema::create('business_post_comments', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->text('content');
            $table->unsignedInteger('likes_count')->default(0);

            $table->foreignId('business_post_id')->constrained('business_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('business_post_comments')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['business_post_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_post_comments');
    }
};
