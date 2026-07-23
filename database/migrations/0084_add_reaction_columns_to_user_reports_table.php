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
        Schema::table('user_reports', function (Blueprint $table) {
            // A report is either about a user (reported_user_id, existing)
            // or about a reaction — an emote used on a reactable — in which
            // case it concerns every current reactor of that emote rather
            // than one individual (see UserReport::reactingUsers()).
            $table->nullableMorphs('reactable');
            $table->foreignId('emote_id')->nullable()->after('reactable_id')->constrained()->nullOnDelete();

            $table->index(['reactable_type', 'reactable_id', 'emote_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_reports', function (Blueprint $table) {
            $table->dropForeign(['emote_id']);
            $table->dropIndex(['reactable_type', 'reactable_id', 'emote_id', 'status']);
            $table->dropColumn(['reactable_type', 'reactable_id', 'emote_id']);
        });
    }
};
