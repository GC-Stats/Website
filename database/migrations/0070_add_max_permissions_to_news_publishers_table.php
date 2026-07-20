<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_publishers', function (Blueprint $table) {
            $table->json('max_permissions')->nullable()->after('socials');
        });
    }

    public function down(): void
    {
        Schema::table('news_publishers', function (Blueprint $table) {
            $table->dropColumn('max_permissions');
        });
    }
};
