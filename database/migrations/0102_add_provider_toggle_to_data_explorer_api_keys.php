<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A user can now link both an OpenAI and an Anthropic key and simply
     * flip which one is active, instead of a single key per account — drop
     * the old one-row-per-user constraint in favor of one-row-per-provider,
     * plus an is_active flag for which one requests actually use.
     */
    public function up(): void
    {
        Schema::table('data_explorer_api_keys', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('provider');
            // Add the new composite index before dropping the old one —
            // MySQL won't drop an index the user_id foreign key still
            // depends on unless another index already covers that column.
            $table->unique(['user_id', 'provider']);
        });

        Schema::table('data_explorer_api_keys', function (Blueprint $table) {
            // Kept its name from when the table was still nl_query_api_keys
            // — RENAME TABLE doesn't rename indexes.
            $table->dropUnique('nl_query_api_keys_user_id_unique');
        });

        // Pre-existing rows were the sole (implicitly active) key for their
        // user under the old one-per-account constraint — keep them active.
        DB::table('data_explorer_api_keys')->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_explorer_api_keys', function (Blueprint $table) {
            $table->unique('user_id', 'nl_query_api_keys_user_id_unique');
        });

        Schema::table('data_explorer_api_keys', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'provider']);
            $table->dropColumn('is_active');
        });
    }
};
