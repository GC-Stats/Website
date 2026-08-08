<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('types')->default(new Expression('(JSON_ARRAY())'));
            $table->string('country_code', 3)->nullable();
            $table->json('socials')->default(new Expression('(JSON_OBJECT())'));
            $table->json('max_permissions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization');
    }
};
