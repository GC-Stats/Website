<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_player_stats', function (Blueprint $table) {
            $table->string('val_name')->nullable()->after('agent_name');
        });
    }

    public function down(): void
    {
        Schema::table('game_player_stats', function (Blueprint $table) {
            $table->dropColumn('val_name');
        });
    }
};
