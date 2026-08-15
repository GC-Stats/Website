<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moderation_suspects', function (Blueprint $table) {
            // Auto-moderation is now OpenAI-only (the local word list was
            // removed) — a single, always-'openai' source added nothing.
            $table->dropColumn('source');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_suspects', function (Blueprint $table) {
            $table->string('source')->default('openai');
        });
    }
};
