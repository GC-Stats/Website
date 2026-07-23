<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emotes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('image_path');
            // Storage subfolder this image lives under (e.g. "twemoji",
            // "teams", or whatever an admin typed at upload time — see
            // Admin\EmoteController::resolveImagePath) — a real column
            // rather than parsed from image_path, so it stays a plain,
            // indexable, portable filter/sort key.
            $table->string('source', 40)->default('custom')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emotes');
    }
};
