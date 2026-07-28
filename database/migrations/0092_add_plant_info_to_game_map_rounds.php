<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_map_rounds', function (Blueprint $table) {
            $table->string('plant_site')->nullable();
            $table->integer('plant_x')->nullable();
            $table->integer('plant_y')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('game_map_rounds', function (Blueprint $table) {
            $table->dropColumn(['plant_site', 'plant_x', 'plant_y']);
        });
    }
};
