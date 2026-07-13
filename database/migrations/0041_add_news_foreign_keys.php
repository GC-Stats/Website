<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->foreign('author_id')->references('id')->on('news_authors')->nullOnDelete();
            $table->foreign('publisher_id')->references('id')->on('news_publishers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropForeign(['publisher_id']);
        });
    }
};
