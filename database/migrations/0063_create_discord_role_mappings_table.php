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
        Schema::create('discord_role_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('discord_role_id')->unique();
            $table->string('discord_role_name')->nullable();
            $table->string('app_role');
            $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discord_role_mappings');
    }
};
