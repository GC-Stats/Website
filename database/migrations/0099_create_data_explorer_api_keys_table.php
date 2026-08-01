<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per (user, provider) — a user may link both an OpenAI and an
     * Anthropic key, but only one is_active at a time, see
     * App\Services\DataExplorerKeyService::activate().
     */
    public function up(): void
    {
        Schema::create('data_explorer_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->boolean('is_active')->default(false);
            $table->text('key_encrypted');
            $table->timestamp('linked_at');
            $table->timestamp('last_validated_at')->nullable();
            $table->string('last_validation_status')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_explorer_api_keys');
    }
};
