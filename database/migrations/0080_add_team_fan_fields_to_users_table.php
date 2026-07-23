<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The team a user has picked to show as "fan of" on their
            // public profile, along with one of that team's own tags
            // (App\Models\Team::tags). Nulled if the team is deleted.
            $table->foreignId('team_id')->nullable()->after('username')->constrained()->nullOnDelete();
            $table->string('team_tag')->nullable()->after('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn('team_tag');
        });
    }
};
