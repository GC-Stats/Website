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
        Schema::create('api_key_reveals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('api_key')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->text('key_value_encrypted');
            $table->timestamp('expires_at')->index();
            $table->timestamp('viewed_at')->nullable();
            // Set when a *different* reveal for the same key superseded this
            // one (e.g. a regenerate() before it was opened) — kept distinct
            // from viewed_at so an audit can tell "seen by a human" apart
            // from "invalidated, never seen".
            $table->timestamp('superseded_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_key_reveals');
    }
};
