<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('news_id')->nullable()->constrained('news')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('news_authors')->nullOnDelete();
            $table->timestamps();

            $table->index('news_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_images');
    }
};
