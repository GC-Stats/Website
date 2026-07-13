<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The Rust API now authenticates against key_hash exclusively — the
        // plaintext key_value and the unused key_prefix can be dropped.
        Schema::table('api_key', function (Blueprint $table) {
            $table->dropColumn(['key_value', 'key_prefix']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_key', function (Blueprint $table) {
            $table->string('key_value')->nullable()->after('id');
            $table->string('key_prefix', 12)->nullable()->after('key_hash');
        });
    }
};
