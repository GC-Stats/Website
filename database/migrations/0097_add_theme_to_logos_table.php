<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logos', function (Blueprint $table) {
            $table->string('theme')->nullable()->after('until');
        });
    }

    public function down(): void
    {
        Schema::table('logos', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
