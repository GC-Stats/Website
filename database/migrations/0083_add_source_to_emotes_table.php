<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A fresh install already has this column via 0081_create_emotes_table
        // — this migration only matters for databases where 0081 ran before
        // the column was added to it.
        if (! Schema::hasColumn('emotes', 'source')) {
            Schema::table('emotes', function (Blueprint $table) {
                $table->string('source', 40)->default('custom')->index()->after('image_path');
            });
        }

        // Backfill rows written before this column existed, by parsing the
        // same "emotes/{source}/{filename}" convention resolveImage() now
        // writes explicitly.
        DB::table('emotes')->where('source', 'custom')->orderBy('id')->chunkById(500, function ($emotes) {
            foreach ($emotes as $emote) {
                $segments = explode('/', $emote->image_path);

                if (count($segments) >= 3 && $segments[1] !== '') {
                    DB::table('emotes')->where('id', $emote->id)->update(['source' => $segments[1]]);
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('emotes', 'source')) {
            Schema::table('emotes', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
