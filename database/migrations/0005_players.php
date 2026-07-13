<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('handle');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('country_code', 3)->nullable();
            $table->text('bio')->nullable();
            $table->json('socials')->default(new Expression('(JSON_OBJECT())'));
            $table->string('discord_id')->nullable()->unique();
            $table->string('val_id')->nullable()->unique();
            $table->integer('vlr_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
