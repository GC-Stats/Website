<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the single string `type` column (from 0110, already applied
     * in production) with the `types` json array the app actually reads —
     * changing 0110 in place isn't visible to `migrate` on environments
     * where it already ran, so this does the conversion as its own step.
     */
    public function up(): void
    {
        Schema::table('organization', function (Blueprint $table) {
            $table->json('types')->default(new Expression('(JSON_ARRAY())'))->after('slug');
        });

        DB::table('organization')->whereNotNull('type')->where('type', '!=', '')->orderBy('id')->each(function ($organization) {
            DB::table('organization')
                ->where('id', $organization->id)
                ->update(['types' => json_encode([$organization->type])]);
        });

        Schema::table('organization', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('organization', function (Blueprint $table) {
            $table->string('type')->default('')->after('slug');
        });

        DB::table('organization')->orderBy('id')->each(function ($organization) {
            $types = json_decode($organization->types, true) ?: [];
            DB::table('organization')->where('id', $organization->id)->update(['type' => $types[0] ?? '']);
        });

        Schema::table('organization', function (Blueprint $table) {
            $table->dropColumn('types');
        });
    }
};
