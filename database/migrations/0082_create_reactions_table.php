<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reactable');
            $table->timestamps();

            // One reaction per user/emote/content — clicking an already-set
            // emote again removes it (toggle) instead of duplicating it.
            $table->unique(['reactable_type', 'reactable_id', 'user_id', 'emote_id'], 'reactions_unique_per_user_emote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
