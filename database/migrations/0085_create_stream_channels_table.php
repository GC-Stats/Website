<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->nullable()->constrained('news_publishers')->nullOnDelete();
            $table->string('name');
            $table->string('platform', 20)->index();
            $table->string('url');
            $table->string('language_code', 5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_channels');
    }
};
