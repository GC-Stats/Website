<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_map_rounds', function (Blueprint $table) {
            $table->foreignId('atk_team')->nullable()->constrained(table: 'teams', column: 'id')->onDelete('cascade');
            $table->foreignId('def_team')->nullable()->constrained(table: 'teams', column: 'id')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('game_map_rounds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atk_team');
            $table->dropConstrainedForeignId('def_team');
        });
    }
};
