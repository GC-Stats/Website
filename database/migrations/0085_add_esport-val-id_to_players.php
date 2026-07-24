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
        if (! Schema::hasColumn('players', 'esports_val_id')) {
            Schema::table('players', function (Blueprint $table) {
                $table->string('esports_val_id')->nullable()->unique()->after('val_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('players', 'esports_val_id')) {
            Schema::table('players', function (Blueprint $table) {
                $table->dropColumn('esports_val_id');
            });
        }
    }
};
