<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The GitHub-style review discussion for an organization's news
     * article — a flat chronological thread (mirrors change_request_messages)
     * where each comment may optionally target a specific reviewable field
     * ('title', 'excerpt', 'content', 'image_cover'; null = a general
     * comment not anchored to any one field) rather than a precise
     * character range within the rich HTML body.
     */
    public function up(): void
    {
        Schema::create('news_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field')->nullable();
            $table->text('body');
            $table->string('type')->default('comment');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('news_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_comments');
    }
};
