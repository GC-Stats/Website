<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            // Null subject = a "general" thread; otherwise one thread per
            // subject (tournament/match/news), enforced by the unique index
            // below — created lazily by ForumService::findOrCreateThreadFor().
            $table->nullableMorphs('subject');
            $table->string('title')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_threads');
    }
};
