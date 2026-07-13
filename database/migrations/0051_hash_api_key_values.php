<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_key', function (Blueprint $table) {
            $table->string('key_hash', 64)->nullable()->unique()->after('key_value');
            $table->string('key_prefix', 12)->nullable()->after('key_hash');
        });

        // Single bulk statement instead of one UPDATE per row — this table
        // may already hold a large number of keys by the time this runs.
        DB::statement(
            'UPDATE api_key SET key_hash = SHA2(key_value, 256), key_prefix = SUBSTRING(key_value, 1, 12) WHERE key_value IS NOT NULL'
        );

        Schema::table('api_key', function (Blueprint $table) {
            $table->string('key_value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_key', function (Blueprint $table) {
            $table->dropColumn(['key_hash', 'key_prefix']);
            $table->string('key_value')->nullable(false)->change();
        });
    }
};
