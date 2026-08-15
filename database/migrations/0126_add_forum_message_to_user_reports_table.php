<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_reports', function (Blueprint $table) {
            // A third report shape, alongside reported_user_id and the
            // reactable/emote trio: a single forum message. nullOnDelete
            // (not cascade) — a soft-deleted or later hard-deleted message
            // must not erase the moderation trail, same reasoning as
            // reported_user_id.
            $table->foreignId('reported_message_id')->nullable()->after('reported_user_id')->constrained('forum_messages')->nullOnDelete();

            $table->index(['reported_message_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('user_reports', function (Blueprint $table) {
            $table->dropForeign(['reported_message_id']);
            $table->dropIndex(['reported_message_id', 'status']);
            $table->dropColumn('reported_message_id');
        });
    }
};
