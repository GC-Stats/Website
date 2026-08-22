<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_player_povs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();

            // Null when the detected channel is the team's own channel
            $table->foreignId('player_id')->nullable()->constrained('players')->cascadeOnDelete();
            $table->string('twitch_login');
            $table->string('title');
            $table->string('url');
            $table->timestamp('last_seen_live_at');
            $table->timestamps();

            $table->unique(['match_id', 'twitch_login']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_player_povs');
    }
};
