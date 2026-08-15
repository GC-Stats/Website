<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_messages', function (Blueprint $table) {
            // Moderation no longer blocks a post from being created — a
            // flagged message is created normally, then immediately hidden
            // pending review (see App\Jobs\ModerateForumMessage). Null =
            // visible.
            $table->timestamp('hidden_at')->nullable()->after('body');
        });

        Schema::table('moderation_suspects', function (Blueprint $table) {
            // Was 'action' (blocked/flagged) back when a match could reject
            // a post outright — now every row corresponds to a real,
            // hidden message, so this column instead records which check
            // caught it ('local', 'openai', or 'both').
            $table->renameColumn('action', 'source');
        });
    }

    public function down(): void
    {
        Schema::table('forum_messages', function (Blueprint $table) {
            $table->dropColumn('hidden_at');
        });

        Schema::table('moderation_suspects', function (Blueprint $table) {
            $table->renameColumn('source', 'action');
        });
    }
};
