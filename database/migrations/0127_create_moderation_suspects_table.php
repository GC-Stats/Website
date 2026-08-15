<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_suspects', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // 'blocked' (rejected outright, never created) or 'flagged' (posted, queued for review)
            // Null for 'blocked' — the message never got created, so there is
            // nothing to point to; 'flagged' rows point at the ForumMessage.
            $table->nullableMorphs('subject');
            $table->foreignId('thread_id')->nullable()->constrained('forum_threads')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('matched_term');
            // Immutable snapshot of the offending text — the only record at
            // all for a 'blocked' row, and stays accurate for a 'flagged'
            // row even if the message is later edited/deleted.
            $table->text('body_snapshot');
            $table->string('status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_suspects');
    }
};
