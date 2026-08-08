<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization', function (Blueprint $table) {
            $table->string('liquipedia_link')->nullable()->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('organization', function (Blueprint $table) {
            $table->dropColumn('liquipedia_link');
        });
    }
};
