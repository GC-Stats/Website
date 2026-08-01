<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('handle');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('country_code', 3)->nullable();
            $table->text('bio')->nullable();
            $table->json('socials')->default(new Expression('(JSON_OBJECT())'));
            $table->boolean('is_active')->default(true);
            $table->string('liquipedia_link')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
