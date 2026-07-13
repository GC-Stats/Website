<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('role')->default(new Expression('(JSON_OBJECT())'));
            $table->json('bio')->nullable();
            $table->string('photo_url')->nullable();
            $table->json('socials')->default(new Expression('(JSON_OBJECT())'));
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_team_members');
    }
};
