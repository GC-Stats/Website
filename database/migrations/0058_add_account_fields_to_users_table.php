<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->json('preferences')->default(new Expression('(JSON_OBJECT())'))->after('password');
            $table->timestamp('discord_synced_at')->nullable()->after('preferences');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferences', 'discord_synced_at']);
            $table->string('password')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
