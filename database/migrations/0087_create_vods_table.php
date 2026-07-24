<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('game_map_id')->nullable()->constrained('game_maps')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('news_publishers')->nullOnDelete();
            $table->string('url');
            $table->string('language_code', 5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vods');
    }
};
